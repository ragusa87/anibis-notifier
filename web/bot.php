<?php
/**
 * Handle bot messages via a webhook
 */
use Anibis\Notify\BotMessage;
use Anibis\Notify\TelegramService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * @var \Silex\Application $app
 */
$app = new \Anibis\App([
    'debug' => false
]);

if (php_sapi_name() == 'cli') {

    if (!isset($argv[1])) {
        die("Please define a bot URL or enter 'delete' to add a hook\n");
    }
    $url = $argv[1];
    if ($url === "delete") {
        $url = "";
    }

    // if run from console, set or delete webhook
    $app["notify"]->send('setWebhook', ['url' => $url]);
    exit;
}
/**
 * Handle bot actions
 */
$app->post("/", function () use ($app) {

    /** @var TelegramService $bot */
    $bot = $app["notify"];

    $bot->setCommand("/start", function (TelegramService $service, BotMessage $message, array $parameters) {
        $service->getDb()->addId($message->getChatId());
        return $service->reply($message, "You are now subscribed (" . $message->getChatId() . ")");
    });
    $bot->setCommand("/stop", function (TelegramService $service, BotMessage $message, array $parameters) {
        $service->getDb()->removeId($message->getChatId());
        return $service->reply($message, "You are now unsubscribed (" . $message->getChatId() . ")");
    });

    $bot->setCommand("/list", function (TelegramService $service, BotMessage $message, array $parameters) {
        $ids = $service->getDb()->getIds();
        if (empty($ids)) {
            return $service->reply($message, "Subscribers: none");
        }
        return $service->reply($message, "Subscribers: " . implode(", ", $ids));
    });
    $bot->setCommand("/echo", function (TelegramService $service, BotMessage $message, array $parameters) {
        if (count($parameters) <= 1) {
            $service->reply($message, "Please add a text, Ex:".$service->getCurrentCommand()." Hello World");
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

// Run bot
$app->run();



