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
use Requests_Response;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class AnibisProvider
 * @package Anibis\Provider
 * Send a Search request to anibis parse and populate results
 */
class AnibisProvider extends AbstractProvider
{
    private $url = "http://www.anibis.ch/fr/immobilier--16/advertlist.aspx";

    /**
     * @inheritdoc
     */
    protected function fetch(SearchCriteria $searchCriteria)
    {
        $url = $this->url . "?fts=" . urlencode($searchCriteria->getTerm()) . "&loc=" . urlencode($searchCriteria->getLocality()) . "&sdc=10&aidl=15221&sf=dpo&so=d&p=0";
        return Requests::POST($url);
    }

    /**
     * @inheritdoc
     */
    protected function parse(Requests_Response $requests)
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
            $result->setId($this->getName() . "_" . intval($id));
            $result->setUrl("http://www.anibis.ch/" . $relUrl);
            $result->setPrice($c->filter(".price")->text());
            $result->setDescription($c->filter(".details .description")->text());

            $results[] = $result;

        }
        return $results;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return "anibis";
    }
}