<?php
/**
 * Created by PhpStorm.
 * User: laurent
 * Date: 25.01.16
 * Time: 20:34
 */

namespace Anibis\Provider;


use Anibis\Criteria\SearchCriteria;

interface ProviderInterface
{
    public function getCachedResults(SearchCriteria $search, $duration = 1200);

    public function getName();
}