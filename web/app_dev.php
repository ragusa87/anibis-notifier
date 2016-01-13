<?php

// web/index.php
use Anibis\Cache\CacheService;
use Anibis\Criteria\SearchCriteria;
use Anibis\Provider\AnibisProvider;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application([
    'debug' => true
]);

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/../views',
));
/**
 * @var CachedService $app["cache"]
 */
$app["cache"] = $app->share(function(){
    return new CacheService("../Cache/");
});



$app->get('/', function () use ($app) {
    /** @var CacheService $cache */
    $cache = $app["cache"];


    $s = new SearchCriteria();
    $s->setTerm("Louer");
    $p = new AnibisProvider();

    if (($response = $cache->get("anibis.search", 60*20)) === null) {
        $response = $p->fetch($s);
        $cache->save("anibis.search",$response);
    }

    $results = $p->parse($response);
    return $app["twig"]->render("index.html.twig", ["results" => $results]);
});

$app->run();