<?php
require_once __DIR__ . '/../vendor/autoload.php';

// web/index.php
use Anibis\Cache\CacheService;
use Anibis\Criteria\SearchCriteria;
use Anibis\Db\DbService;
use Anibis\Notify\TelegramService;
use Anibis\Provider\AnibisProvider;
use Anibis\Result\AnibisResult;
use Symfony\Component\HttpFoundation\Request;

/**
 * @var \Silex\Application $app
 */
$app = new \Anibis\App([
    'debug' => false
]);

$app->get('/', function (Request $request) use ($app) {
    /** @var CacheService $cache */
    $cache = $app["cache"];

    $s = new SearchCriteria();
    $p = new AnibisProvider();

    if (($response = $cache->get("anibis.search", 60 * 20)) === null) { // Cached for 20 minutes
        $response = $p->fetch($s);
        $cache->save("anibis.search", $response);
    }
    $results = $p->parse($response);
    $results = $p->filter($s, $results);

    // Process new offers
    /** @var DbService $db */
    $db = $app["db"];
    $newResults = array_filter($results, function (AnibisResult $el) use ($db) {
        if ($db->containsId($el->getId())) {
            return false;
        }
        $db->addId($el->getId());
        return true;
    });
    // if force is set, newOffer = offers
    if (empty($newResults) && $request->query->get("force") !== null) {
        $newResults = $results;
    }

    // Notify via Telegram
    $nbNotifications = 0;
    if (!empty($newResults)) {
        /** @var TelegramService $bot */
        $bot = $app["notify"];
        foreach($newResults as $res){
            $html = $app["twig"]->render("results-simple.html.twig", ["results" => [$res]]);
            $nbNotifications = $bot->notify($html,true);
        }

    }


    // Html content
    /** @var \Symfony\Component\HttpFoundation\Response $response */
    return $app["twig"]->render("index.html.twig", [
        "results"         => $results,
        "newResults"      => $newResults,
        "nbNotifications" => $nbNotifications
    ]);


});

$app->run();