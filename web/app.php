<?php
require_once __DIR__ . '/../vendor/autoload.php';

// web/index.php
use Anibis\Criteria\SearchCriteria;
use Anibis\Db\DbService;
use Anibis\Notify\TelegramService;
use Anibis\Result\Result;
use Symfony\Component\HttpFoundation\Request;

/**
 * @var \Silex\Application $app
 */
$app = new \Anibis\App([
    'debug' => strpos($_SERVER['SCRIPT_NAME'], "_dev.php") !== false
]);

$app->get('/', function (Request $request) use ($app) {

    $s = new SearchCriteria();
    $providers = ["anibis", "homegate"];

    $results = [];
    foreach ($providers as $name) {
        try {
            $provider = $app[$name];
            $results = array_merge($results, call_user_func([$provider, "getCachedResults"], $s));
        } catch (Requests_Exception $re) {
            $app["notify"]->notify("Error while getting results for " . $name . " :" . $re->getMessage(), false);
            if ($app["dev"]) {
                throw $re;
            }
        }
    }

    // Process new offers
    /** @var DbService $db */
    $db = $app["db"];
    $newResults = array_filter($results, function (Result $el) use ($db) {
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
        foreach ($newResults as $res) {
            $html = $app["twig"]->render("results-simple.html.twig", ["results" => [$res]]);
            $nbNotifications += $bot->notify($html, true);
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