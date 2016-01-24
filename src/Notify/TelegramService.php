<?php
/**
 * Created by PhpStorm.
 * User: laurent
 * Date: 24.01.16
 * Time: 18:23
 */

namespace Anibis\Notify;

use Anibis\Db\DbService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;


/**
 * Class NotifiyService
 * Notify by sending message via telegram
 * @package Anibis\Notify
 */
class TelegramService
{
    /**
     * @var string
     */
    private $apiKey;
    /**
     * @var DbService $db ;
     */
    private $db;

    /**
     * @var \Closure[] Closure indexed by command name
     */
    private $commands;

    /**
     * @var string|null
     */
    private $currentCommand = null;

    /**
     * NotifiyService constructor.
     * @param $apiKey
     * @param DbService $db
     */
    public function __construct($apiKey, DbService $db)
    {
        if($apiKey === null){
            throw new \RuntimeException("Invalid api key");
        }
        $this->apiKey = $apiKey;
        $this->db = $db;
        $this->commands = ["/help" => function (TelegramService $bot, BotMessage $message, array $arguments = array()) {
            return $bot->reply(
                $message,
                "Commands:\n" . implode("\n", array_keys($bot->commands)) . ".");
        }];
    }

    /**
     * Run a CURL request
     * @param mixed $handle curl_exec options
     * @return Response Status is 500 on error
     */
    private function exec_curl_request($handle)
    {
        $response = curl_exec($handle);

        if ($response === false) {
            $errno = curl_errno($handle);
            $error = curl_error($handle);
            curl_close($handle);
            return new Response("Curl returned error $errno: $error\n", 500);

        }

        $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
        curl_close($handle);

        if ($http_code != 200) {
            // Wait to avoid DDOS server if something goes wrong
            sleep(2);
            return new Response($response, $http_code);
        }
        $content = json_decode($response, true);
        $response = new JsonResponse($content);
        if ($content["ok"] !== true) {
            $response->setStatusCode(500);
        }
        return $response;
    }

    /**
     * @param string $method API Method
     * @param array $parameters
     * @return bool true on success
     * @throws \RuntimeException On bad request
     */
    public function send($method, array $parameters = array())
    {
        $parameters["method"] = $method;

        $handle = curl_init('https://api.telegram.org/bot' . $this->apiKey . '/');
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($handle, CURLOPT_TIMEOUT, 60);
        curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
        curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

        $response = $this->exec_curl_request($handle);
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException("Error sending request: " . $response->getContent());
        }
        return true;

    }

    /**
     * @param BotMessage $message
     * @return bool Handle messages
     */
    public function handle(BotMessage $message)
    {
        $rawText = $message->getText();
        if (empty($rawText)) {
            $rawText = " ";
        }
        $params = explode(" ", $rawText);
        $command = strtolower(array_shift($params));
        // Run command
        if (isset($this->commands[$command])) {
            $this->currentCommand = $command;
            return call_user_func($this->commands[$command], $this, $message, $params);
        }
        // Default command
        $this->currentCommand = null;
        return $this->reply($message, "Invalid command, type /help for more infos");
    }

    /**
     * Add a command
     * @param string $name command name starting with /
     * @param \Closure $closure Closuse, get multiple params (bot,message,arguments)
     */

    public function setCommand($name, \Closure $closure)
    {
        $this->commands[$name] = $closure;
    }

    /**
     * Send a message back to user
     * @param BotMessage $source
     * @param string $text text message
     * @param array $options
     * @throws RuntimeException
     * @return bool success
     */
    public function reply(BotMessage $source, $text, array $options = array())
    {
        return $this->send("sendMessage", array_merge([
            "chat_id" => $source->getChatId(),
            "text"    => $text
        ], $options));
    }

    /**
     * @return DbService
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Send html message to all subscribers.
     * @param $content
     * @param bool $debug throw execption on error if true
     * @throws RuntimeException
     * @return int number of notification sent
     */
    public function notify($content,$debug = false)
    {
        $ids = $this->db->getIds();
        if (empty($ids)) {
            return 0;
        }
        $nb = 0;
        foreach ($ids as $messageId) {
            try{
                $this->send("sendMessage", [
                    "chat_id"    => $messageId,
                    "text"       => $content,
                    "parse_mode" => "HTML"
                ]);
                $nb += 1;
            }catch(\RuntimeException $re){
                if($debug){
                    throw $re;
                }
            }
        }
        return $nb;
    }

    /**
     * Name of the current command
     * @return string|null
     */
    public function getCurrentCommand()
    {
        return $this->currentCommand;
    }
}