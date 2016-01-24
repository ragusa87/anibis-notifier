<?php
namespace Anibis\Result;
/**
 * Anibis result
 */
class AnibisResult
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $url;
    /**
     * @var null|string
     */
    private $img = null;
    /**
     * @var null|string
     */
    private $description;
    /**
     * @var array
     */
    private $tags = [];
    /**
     * @var string
     */
    private $price;
    /**
     * @var string
     */
    private $title;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param array $tags
     * @return $this
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param null|string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getImg()
    {
        return $this->img;
    }

    /**
     * @param null|string $img
     * @return $this
     */
    public function setImg($img)
    {
        $this->img = $img;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function setPrice($prix)
    {
        $this->price = $prix;
    }

    /**
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}