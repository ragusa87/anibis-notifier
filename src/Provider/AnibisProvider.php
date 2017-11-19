<?php

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
    private $url = "https://www.anibis.ch/fr/advertlist.aspx";

    /**
     * @inheritdoc
     */
    protected function fetch(SearchCriteria $searchCriteria)
    {
        $url = $this->url . "?fts=" . urlencode($searchCriteria->getTerm()) . "&loc=" . urlencode($searchCriteria->getLocality()) . "&sdc=5&aral=834_12_1054&sf=dpo&so=d&p=0";
        return Requests::POST($url);
    }

    /**
     * @inheritdoc
     */
    protected function parse(Requests_Response $requests)
    {
        $crawler = new Crawler();
        $crawler->addContent($requests->body);
        /** @var Crawler $r */
        $r = $crawler->filterXPath('//*[@id="aspnetForm"]/div[1]/main/div/div[2]/div[2]/div[3]/ul/li');
        $results = array();
        /** @var DOMElement $el */

        foreach ($r as $index => $el) {
            try {
                if ($index < 3) {
                    continue;
                }
                $c = new Crawler();
                $c->add($el);

                $tags = [];

                /** @var DOMElement $z */
                foreach ($c->filter(".listing-info li") as $z) {
                    $tags[] = $z->textContent;
                }


                $result = new Result();
                if ($c->filter(".listing-info a")->count() > 0) {
                    $result->setTitle(trim($c->filter(".listing-info a")->text()));
                    $relUrl = $c->filter(".listing-info a")->attr("href");
                }else{
                    // TODO Bad node to log
                    continue;
                }
                $result->setTags($tags);


                $id = explode("--", explode("/", parse_url($relUrl)["path"])[2])[1];

                $result->setId($this->getName() . "_" . intval($id));
                $result->setUrl("http://www.anibis.ch/" . $relUrl);


                $result->setPrice($c->filter(".listing-price")->text());

                $result->setDescription($c->filter(".listing-description")->text());

                $results[] = $result;
            } catch (\Exception $e) {
                throw new \RuntimeException("Unable to parse result " . $index, 0, $e);
            }

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