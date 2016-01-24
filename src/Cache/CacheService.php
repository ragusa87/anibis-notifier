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

        if (false === file_exists($this->dir)) {
            $r = mkdir($this->dir, 777);
            if($r === false){
                throw new \RuntimeException("Unable to create cache directory: ".$this->dir);
            }
        }
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
        file_put_contents($this->getFile($key), $data);

    }

    /**
     * @param $key
     * @return string File used to cache $key
     */
    private function getFile($key)
    {
        return $this->dir . md5($key) . ".txt";
    }

    /**
     * @param string $key key
     * @param int|null $maxAge max age in seconds, null for infinite cache duration, 0 to bypass cache
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