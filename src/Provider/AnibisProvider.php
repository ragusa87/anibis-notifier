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
use Requests_Response;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class AnibisProvider
 * @package Anibis\Provider
 * Send a Search request to anibis parse and populate results
 */
class AnibisProvider implements ProviderInterface
{
    private $url = "http://www.anibis.ch/fr/immobilier--16/advertlist.aspx";
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
        if (($response = $this->cache->get("anibis.search", $duration)) === null) { // Cached for 20 minutes
            $response = $this->fetch($search);
            $this->cache->save("anibis.search", $response);
        }
        $results = $this->parse($response);
        return $this->filter($search, $results);
    }

    /**
     * @param SearchCriteria $searchCriteria
     * @return Requests_Response
     */
    private function fetch(SearchCriteria $searchCriteria)
    {
        $url = $this->url . "?fts=" . urlencode($searchCriteria->getTerm()) . "&loc=" . urlencode($searchCriteria->getLocality()) . "&sdc=10&aidl=15221&sf=dpo&so=d&p=0";
        return Requests::POST($url);
    }

    /**
     * @param Requests_Response $requests
     * @return Result[]
     */

    private function parse(Requests_Response $requests)
    {
        $crawler = new Crawler();
        $crawler->addContent($requests->body);
        $r = $crawler->filterXPath('//*[@id="content"]/div/div[2]/div[1]/div[1]/ul/li');
        $results = array();
        /** @var DOMElement $el */
        foreach ($r as $el) {
            $c = new Crawler();
            $c->add($el);

            $tags = [];
            /** @var DOMElement $z */
            foreach ($c->filter(".horizontal-separated-list li") as $z) {
                $tags[] = $z->textContent;
            }

            $result = new Result();
            $result->setTitle(trim($c->filter(".details a")->text()));
            $result->setTags($tags);
            $relUrl = $c->filter(".details a")->attr("href");

            $id = explode("--", explode("/", parse_url($relUrl)["path"])[2])[1];
            $result->setId($this->getName()."_".intval($id));
            $result->setUrl("http://www.anibis.ch/" . $relUrl);
            $result->setPrice($c->filter(".price")->text());
            $result->setDescription($c->filter(".details .description")->text());


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
        return "anibis";
    }
}