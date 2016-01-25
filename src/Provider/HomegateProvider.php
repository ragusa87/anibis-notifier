<?php
/**
 * Created by PhpStorm.
 * User: laurent
 * Date: 13.01.16
 * Time: 15:23
 */
namespace Anibis\Provider;

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
class HomegateProvider extends AbstractProvider
{
    private $url = "http://m.homegate.ch/fr?0-1.IFormSubmitListener-search-searchForm";

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    protected function parse(Requests_Response $requests)
    {
        $crawler = new Crawler();
        $crawler->addContent($requests->body);

        $r = $crawler->filter("#page > main > section > div > div.result-item-list article a > .box-row");
        $results = array();
        /** @var DOMElement $el */
        foreach ($r as $el) {
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
     * @inheritdoc
     */
    public function getName()
    {
        return "homegate";
    }
}