<?php
/**
 * Created by PhpStorm.
 * User: laurent
 * Date: 24.01.16
 * Time: 18:28
 */

namespace Anibis;


use Anibis\Cache\CacheService;
use Anibis\Db\DbService;
use Anibis\Notify\TelegramService;
use Anibis\Provider\AnibisProvider;
use Anibis\Provider\HomegateProvider;
use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\Yaml\Exception\RuntimeException;
use Symfony\Component\Yaml\Parser;

/**
 *
 * Silex Application with services defined
 * @package Anibis
 */
class App extends Application
{
    private $parameters = null;

    public function __construct(array $values = array())
    {
        parent::__construct($values);

        $this->loadParameters();

        $this->register(new TwigServiceProvider(), array(
            'twig.path' => __DIR__ . '/../views',
        ));


        $this["cache"] = function () {
            return new CacheService(__DIR__ . "/../var/cache/");
        };


        $this["db"] = function () {
            return new DbService(__DIR__ . "/../var/cache/db-results.txt");
        };

        $this["notify"] = function () {
            return new TelegramService(
                $this->parameters["telegram_bot_key"],
                new DbService(__DIR__ . "/../var/cache/db-subscribers.txt")
            );
        };

        $this["anibis"] = function () {
            return new AnibisProvider($this["cache"]);
        };

        $this["homegate"] = function () {
            return new HomegateProvider($this["cache"]);
        };
    }

    private function loadParameters()
    {
        $file = __DIR__."/../parameters.yml";
        if(false == file_exists($file)){
            throw new RuntimeException(sprintf("You must create a parameter.yml file: %s",$file));
        }
        $yaml = new Parser();
        $this->parameters = $yaml->parse(file_get_contents($file));
    }

}