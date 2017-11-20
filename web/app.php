<?php
require_once __DIR__ . '/../vendor/autoload.php';

// web/index.php
use Anibis\Criteria\SearchCriteria;
use Anibis\Db\DbService;
use Anibis\Notify\BotMessage;
use Anibis\Notify\TelegramService;
use Anibis\Result\Result;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @var \Silex\Application $app
 */
$app = new \Anibis\App([
    'debug' => getenv("APP_DEBUG")
]);

$app->get('/', function (Request $request) use ($app) {

    $s = new SearchCriteria();
    $providers = ["anibis"]; // FIXME "homegate" is not working anymore.

    $results = [];
    foreach ($providers as $name) {
        try {
            $provider = $app[$name];
            $results = array_merge($results, call_user_func([$provider, "getCachedResults"], $s));
        } catch (Requests_Exception $re) {
            if ($app['debug']) {
                throw $re;
            } else {
                $app["notify"]->notify("Error while getting results for " . $name . " :" . $re->getMessage(), false);
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
        "results" => $results,
        "newResults" => $newResults,
        "nbNotifications" => $nbNotifications
    ]);


});

/**
 * Handle bot actions
 */
$app->get("/bot", function () use ($app) {
    $url = htmlentities("https://core.telegram.org/bots/api");
    return new Response(sprintf('See doc on <a href="%s">%s</a>', $url, $url));
});

$app->post("/bot", function () use ($app) {

    /** @var TelegramService $bot */
    $bot = $app["notify"];

    $bot->setCommand(" / start", function (TelegramService $service, BotMessage $message, array $parameters) {
        $service->getDb()->addId($message->getChatId());
        return $service->reply($message, "You are now subscribed(" . $message->getChatId() . ")");
    });
    $bot->setCommand(" / stop", function (TelegramService $service, BotMessage $message, array $parameters) {
        $service->getDb()->removeId($message->getChatId());
        return $service->reply($message, "You are now unsubscribed(" . $message->getChatId() . ")");
    });

    $bot->setCommand(" / list", function (TelegramService $service, BotMessage $message, array $parameters) {
        $ids = $service->getDb()->getIds();
        if (empty($ids)) {
            return $service->reply($message, "Subscribers: none");
        }
        return $service->reply($message, "Subscribers: " . implode(", ", $ids));
    });
    $bot->setCommand(" /echo ", function (TelegramService $service, BotMessage $message, array $parameters) {
        if (count($parameters) <= 1) {
            $service->reply($message, "Please add a text, Ex:" . $service->getCurrentCommand() . " Hello World");
            return true;
        }
        $txt = implode(" ", $parameters);
        return $service->reply($message, $txt);
    });

    // Get JSON Content
    $content = file_get_contents("php://input");
    $update = json_decode($content, true);

    // Store content
    if ($app["debug"] === true) {
        file_put_contents(__DIR__ . "/../var/cache/log.dump", $content);
    }

    // Pass command to Bot and handle result
    if ($update !== false && isset($update["message"])) {
        $response = $bot->handle(new BotMessage($update["message"]));
        if (!$response instanceof Response) {
            if (is_bool($response)) {
                return new Response($response ? "Success" : "Error", $response ? 200 : 500);
            }
            return new JsonResponse("Bot must return a Response object or true", 500);
        }
        return $response;
    }
    throw new BadRequestHttpException(
        "Please post a valid json-encoded Message\n" .
        "See https://core.telegram.org/bots/api");
});

// Command to set bot webhook.
if (php_sapi_name() == 'cli') {

    if (!isset($argv[1])) {
        die("Please define a bot URL to add a hook or enter 'delete' to delete existing hook\n");
    }
    $url = $argv[1];
    if ($url === "delete") {
        $url = "";
    }

    // Get hook from env.
    if ($url === "auto" && false !== getenv("BOT_WEB_HOOK")) {
        $url = getenv("BOT_WEB_HOOK") . "/bot";
    }

    // if run from console, set or delete webhook
    $app["notify"]->send('setWebhook', ['url' => $url]);
    exit;
}

$app->run();