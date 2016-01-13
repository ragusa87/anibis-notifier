<?php
/**
 * Created by PhpStorm.
 * User: laurent
 * Date: 13.01.16
 * Time: 17:01
 */
namespace Anibis\Cache;

class CacheService
{
    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    /**
     * Save an object to cache
     * @param string $key cache key
     * @param mixed $value Object to cache
     */
    public function save($key, $value)
    {
        $data = serialize([
            "content" => $value,
            "date"    => time()
        ]);
        file_put_contents($this->dir . $key, $data);

    }

    /**
     * @param $key
     * @return string File used to cache $key
     */
    private function getFile($key)
    {
        return $this->dir . md5($key);
    }

    /**
     * @param string $key key
     * @param int|null $maxAge max age in seconds, null for infinite
     * @return mixed Cached object or null
     */
    public function get($key, $maxAge = null)
    {
        if ($maxAge === 0 || false == file_exists($this->getFile($key))) {
            return null;
        }

        $temp = unserialize(file_get_contents($this->getFile($key)));
        $date = \DateTime::createFromFormat("t", $temp["date"]);

        if ($maxAge === null || ($date !== false && $date >= new \DateTime($maxAge . " seconds ago"))) {
            return $temp["content"];
        }
        return null;

    }
}