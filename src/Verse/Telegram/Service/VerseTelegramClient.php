<?php


namespace Verse\Telegram\Service;


use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;
use Verse\Di\Env;
use Verse\Run\RunContext;

class VerseTelegramClient
{
    public $token = '';

    protected Api $api;

    public function __construct()
    {
        /** @var RunContext $config */
        $config = Env::getContainer()->bootstrap('config');
        $this->token = $config->get("BOT_TOKEN");
    }


    /**
     * @return Api
     * @throws TelegramSDKException
     */
    public function getApi()
    {
        if (!isset($this->api)) {
            $this->api = new Api($this->token);
        }

        return $this->api;
    }

    /**
     * @return mixed|string|null
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed|string|null $token
     */
    public function setToken($token): void
    {
        $this->token = $token;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return array|Update[]
     * @throws TelegramSDKException
     */
    public function get($offset = 0, $limit = 100)
    {
        $params = [
            'limit' => $limit,
            'offset' => $offset > 0 ? $offset + 1 : null,
            //'allowed_updates' => json_encode(['message', 'callback_query'])
        ];

        return $this->getApi()->getUpdates($params);
    }

    /**
     * @param $chatId
     * @param $text
     * @param array $keyboard
     * @param int $replyToMessageId
     * @return Message
     * @throws TelegramSDKException
     */
    public function post($chatId, $text, $keyboard = [], $replyToMessageId = 0)
    {
        $params = [
            'chat_id' => $chatId,
            'text' => is_string($text) ? $text : json_encode($text),
            #'parse_mode' => 'html',
            'disable_web_page_preview' => '1',
        ];

        if (!empty($keyboard)) {
            $params['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
        }

        if (!empty($replyToMessageId)) {
            $params['reply_to_message_id'] = $replyToMessageId;
        }

        return $this->getApi()->sendMessage($params);
    }

    /**
     * @param $chatId
     * @param $text
     * @param array $keyboard
     * @param int $originalMessageId
     * @return bool|Message
     * @throws TelegramSDKException
     */
    public function edit($chatId, $text, $keyboard = [], $originalMessageId = 0)
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $originalMessageId,
            'text' => is_string($text) ? $text : json_encode($text),
            #'parse_mode' => 'html',
            'disable_web_page_preview' => '1',
        ];

        if (!empty($keyboard)) {
            $params['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
        }

        return $this->getApi()->editMessageText($params);
    }

    /**
     * @param $text
     * @param $callbackId
     * @return bool
     * @throws TelegramSDKException
     */
    public function answerCallback($text, $callbackId)
    {
        return $this->getApi()->answerCallbackQuery([
            'callback_query_id' => $callbackId,
            'text' => $text
        ]);
    }

    /**
     * @param $id
     * @return \Telegram\Bot\Objects\Chat|null
     */
    public function getChat($id)
    {

        try {
            return $this->getApi()->getChat(['chat_id' => $id]);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public function getChats($ids)
    {
        $chats = [];
        foreach ($ids as $id) {
            $chats[$id] = $this->getChat($id);
        }

        return $chats;
    }
}