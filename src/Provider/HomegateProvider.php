<?php
/**
 * Created by PhpStorm.
 * User: laurent
 * Date: 13.01.16
 * Time: 15:23
 */
namespace Anibis\Provider;

use Anibis\Cache\CacheService;
use Anibis\Criteria\SearchCriteria;
use Anibis\Result\Result;
use DOMElement;
use Requests;
use Requests_Cookie_Jar;
use Requests_Response;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class HomegateProvider
 * @package Anibis\Provider
 * Send a Search request to homegate parse and populate results
 */
class HomegateProvider implements ProviderInterface
{
    private $url = "http://m.homegate.ch/fr?0-1.IFormSubmitListener-search-searchForm";
    /**
     * @var CacheService
     */
    private $cache;

    public function __construct(CacheService $cache)
    {

        $this->cache = $cache;
    }

    /**
     * @param SearchCriteria $search
     * @param int|null $duration @see CacheService#get
     * @return \Anibis\Result\Result[]
     */
    public function getCachedResults(SearchCriteria $search, $duration = 1200)
    {
        if (($response = $this->cache->get("homegate.search", $duration)) === null) { // Cached for 20 minutes
            $response = $this->fetch($search);
            $this->cache->save("homegate.search", $response);
        }
        $results = $this->parse($response);
        return $this->filter($search, $results);
    }

    /**
     * @param SearchCriteria $searchCriteria
     * @return Requests_Response
     */
    public function fetch(SearchCriteria $searchCriteria)
    {
        $agent = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36";
        // Get form cookies
        $jar = new \Requests_Cookie_Jar();
        $url = "http://m.homegate.ch/fr";
        Requests::GET($url, [
            "User-Agent" => $agent
        ], ["cookies" => $jar]);


        $response = Requests::POST($this->url, [
            "Referer"    => $url,
            "User-Agent" => $agent
        ], [
            "searchForm_hf_0"                  => "",
            "offerType"                        => "radio31",
            "searchIn"                         => $searchCriteria->getLocality(),
            "searchObjectCategory"             => "APARTMENT",
            "priceRangeField:minField"         => $searchCriteria->getMin(),
            "priceRangeField:maxField"         => $searchCriteria->getMax(),
            "roomRangeField:minField"          => $searchCriteria->getSizeMin(),
            "roomRangeField:maxField"          => $searchCriteria->getSizeMax(),
            "searchButtonUpper"                => "",
            "peripheryField:periphery"         => "",
            "surfaceLivingRangeField:minField" => "",
            "surfaceLivingRangeField:maxField" => "",
            "yearBuiltRangeField:minField"     => "",
            "yearBuiltRangeField:maxField"     => "",
            "floorField:floor"                 => "",
            "availableFromField:availableFrom" => "",
        ], ["cookies" => $jar]);

        return $response;
    }

    /**
     * @param Requests_Response $requests
     * @return Result[]
     */

    private function parse(Requests_Response $requests)
    {
        $crawler = new Crawler();
        $crawler->addContent($requests->body);

        $r = $crawler->filter("#page > main > section > div > div.result-item-list article a > .box-row");
        // $r = $r->filter("article");
        $results = array();
        /** @var DOMElement $el */
        $i = 0;
        foreach ($r as $el) {
            $i++;
            $c = new Crawler();
            $c->add($el);


            $tags = [];
            /** @var DOMElement $z */
            foreach ($c->filter(".box-row ul.box-row-item-attribute-list li") as $z) {
                if ($z->childNodes !== null && $z->childNodes->length >= 4) {
                    $tags[] = $z->childNodes->item(1)->nodeValue . ": " . $z->childNodes->item(3)->nodeValue;
                }
            }

            $addressB = $c->filter(".item-title--street");
            $address = $addressB->text() . " " . $addressB->siblings()->text();
            $tags[] = "Adresse: " . $address;

            $result = new Result();
            $result->setTags($tags);
            $result->setTitle(trim($c->filter("h2")->text()));

            if ($c->filter("item-description p")->valid()) {
                $result->setDescription($c->filter("item-description p")->text());
            }


            $link = ($el->parentNode->attributes->getNamedItem("href")->nodeValue);
            $result->setId($this->getName() . "_" . explode("/", $link)[2]);
            $result->setUrl("http://m.homegate.ch/" . $link);

            $results[] = $result;

        }

        return $results;
    }

    /**
     * Remove bad results
     * @param SearchCriteria $searchCriteria
     * @param Result[] $result
     * @return Result[]
     */
    private function filter(SearchCriteria $searchCriteria, array $result)
    {
        return array_filter($result, function (Result $e) use ($searchCriteria) {
            $blacklist = explode(" ", $searchCriteria->getTitleBlacklist());
            foreach ($blacklist as $term) {
                if (strpos(strtolower($e->getTitle()), strtolower($term)) !== FALSE) {
                    return false;
                }
            }
            return true;
        });
    }

    public function getName()
    {
        return "homegate";
    }
}