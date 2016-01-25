<?php
/**
 * Created by PhpStorm.
 * User: laurent
 * Date: 25.01.16
 * Time: 20:34
 */

namespace Anibis\Provider;


use Anibis\Criteria\SearchCriteria;
use Anibis\Result\Result;

/**
 * Interface ProviderInterface
 * @package Anibis\Provider
 */
interface ProviderInterface
{
    /**
     * @param SearchCriteria $search Criteria for search results
     * @param int|null $duration Cache duration in seconds (null = infinite, 0 = bypass cache)
     * @return Result[]
     */
    public function getCachedResults(SearchCriteria $search, $duration = 1200);

    /**
     * @return string provider name ([A-Z]+)
     */
    public function getName();
}