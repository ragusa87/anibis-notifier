<?php
/**
 * Created by PhpStorm.
 * User: laurent
 * Date: 24.01.16
 * Time: 17:54
 */

namespace Anibis\Db;

/**
 * Class DbService
 * @package Anibis\Db
 * Store one column value into a file.
 * then you can add/remove or get stored values
 */
class DbService
{
    /**
     * @var string
     */
    private $file;
    /**
     * @var array int
     */
    private $ids;

    /**
     * DbService constructor.
     * @param $file
     */
    public function __construct($file)
    {
        $this->ids = [];
        $this->file = $file;
        // Create file
        if (!file_exists($file)) {
            touch($file);
        }
    }

    /**
     * @param mixed[] $ids
     */
    public function addIds(array $ids)
    {
        $existingIds = $this->getIds();
        foreach ($ids as $id) {
            if (in_array($id, $existingIds)) {
                continue;
            }
            $existingIds[] = $id;
        }
        $this->saveIds($existingIds);
    }

    /**
     * @param mixed $id
     */
    public function addId($id)
    {
        $this->addIds([$id]);
    }

    /**
     * @param mixed $id
     */
    public function removeId($id)
    {
        $this->removeIds([$id]);
    }

    /**
     * @param mixed $id
     * @param bool $strict
     * @return bool
     */
    public function containsId($id, $strict = false)
    {
        $ids = $this->getIds();
        return in_array($id, $ids, $strict);
    }

    /**
     * @return mixed[]
     */
    public function getIds()
    {
        if (!empty($this->ids)) {
            return $this->ids;
        }

        $ids = file_get_contents($this->file);
        if ($ids == false) {
            return [];
        }
        $this->ids = array_map(function ($el) {
            return $el;
        }, explode("\n", $ids));
        return $this->ids;
    }

    /**
     * @param $ids[]
     * @return bool
     */
    private function saveIds(array $ids)
    {
        $this->ids = $ids;
        return file_put_contents($this->file, implode("\n", $ids)) !== false;
    }

    /**
     * @param array $ids
     */
    private function removeIds(array $ids)
    {
        $existing = $this->getIds();
        foreach ($ids as $id) {
            if (false == $this->containsId($id)) {
                continue;
            }
            $index = array_search($id, $existing);
            unset($existing[$index]);
        }
        $this->saveIds($existing);
    }

}