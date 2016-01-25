<?php
/**
 * Created by PhpStorm.
 * User: laurent
 * Date: 25.01.16
 * Time: 22:57
 */

namespace Anibis\Provider;


use Anibis\Cache\CacheService;
use Anibis\Criteria\SearchCriteria;
use Anibis\Result\Result;
use Requests_Response;

/**
 * Class AbstractProvider
 * @package Anibis\Provider
 */
abstract class AbstractProvider implements ProviderInterface
{
    /**
     * @var CacheService
     */
    private $cache;

    /**
     * AbstractProvider constructor.
     * @param CacheService $cache
     */
    public function __construct(CacheService $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @inheritdoc
     */
    public function getCachedResults(SearchCriteria $search, $duration = 1200)
    {
        if (($response = $this->cache->get($this->getName() . ".search", $duration)) === null) { // Cached for 20 minutes
            $response = $this->fetch($search);
            $this->cache->save($this->getName() . ".search", $response);
        }
        $results = $this->parse($response);
        return $this->filter($search, $results);
    }

    /**
     * @param SearchCriteria $searchCriteria
     * @return Requests_Response
     */
    protected abstract function fetch(SearchCriteria $searchCriteria);

    /**
     * @param Requests_Response $requests
     * @return Result[]
     */
    protected abstract function parse(Requests_Response $requests);

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

}