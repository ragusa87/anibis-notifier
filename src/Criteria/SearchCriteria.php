<?php
/**
 * Created by PhpStorm.
 * User: laurent
 * Date: 13.01.16
 * Time: 15:26
 */
namespace Anibis\Criteria;
/**
 * Criteria used to run a search query
 * @package Anibis\Criteria
 */
class SearchCriteria
{
    private $term = "Appartement";
    private $locality = "Lausanne";
    private $min = 1000;
    private $max = 1500;
    private $sizeMin = 2;
    private $sizeMax = 2.5;


    private $titleBlacklist = "cherche";

    /**
     * @return string
     */
    public function getTerm()
    {
        return $this->term;
    }

    /**
     * @param string $term
     */
    public function setTerm($term)
    {
        $this->term = $term;
    }

    /**
     * @return int
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * @param int $min
     */
    public function setMin($min)
    {
        $this->min = $min;
    }

    /**
     * @return int
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * @param int $max
     */
    public function setMax($max)
    {
        $this->max = $max;
    }

    /**
     * @return string
     */
    public function getLocality()
    {
        return $this->locality;
    }

    /**
     * @param string $locality
     */
    public function setLocality($locality)
    {
        $this->locality = $locality;
    }

    /**
     * @return string
     */
    public function getTitleBlacklist()
    {
        return $this->titleBlacklist;
    }

    /**
     * @param string $titleBlacklist
     */
    public function setTitleBlacklist($titleBlacklist)
    {
        $this->titleBlacklist = $titleBlacklist;
    }

    /**
     * @return int
     */
    public function getSizeMin()
    {
        return $this->sizeMin;
    }

    /**
     * @param int $sizeMin
     */
    public function setSizeMin($sizeMin)
    {
        $this->sizeMin = $sizeMin;
    }

    /**
     * @return float
     */
    public function getSizeMax()
    {
        return $this->sizeMax;
    }

    /**
     * @param float $sizeMax
     */
    public function setSizeMax($sizeMax)
    {
        $this->sizeMax = $sizeMax;
    }
}