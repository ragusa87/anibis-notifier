<?php
/**
 * Created by PhpStorm.
 * User: laurent
 * Date: 24.01.16
 * Time: 19:04
 */

namespace Anibis\Notify;

/**
 * BotMessage JSON message from Telegram Api
 * @package Anibis\Notify
 */
class BotMessage
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
        if (false == isset($data["message_id"])) {
            throw new \RuntimeException("Message is invalid");
        }
    }
    public function getChatId()
    {
        return isset($this->data['chat']['id']) ? $this->data['chat']['id'] : null;
    }

    public function getMessageId()
    {
        return isset($this->data["message_id"]) ? $this->data["message_id"] : null;

    }

    public function getText()
    {
        return isset($this->data["text"]) ? $this->data["text"] : null;
    }
}