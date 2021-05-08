<?php


namespace Verse\Telegram\Run\Controller;


trait TelegramControllerTrait
{
    public function text_message() : ?TelegramResponse
    {
        return null;
    }

    public function left_chat_member() : ?TelegramResponse
    {
        return null;
    }

    public function new_chat_members() : ?TelegramResponse
    {
        return null;
    }

    public function edited_message() : ?TelegramResponse
    {
        return $this->text_message();
    }

    public function not_supported() : ?TelegramResponse
    {
        return null;
    }

    public function callback_query() : ?TelegramResponse
    {
        return null;
    }

    public function group_chat_created() : ?TelegramResponse
    {
        return null;
    }

    protected function response() : TelegramResponse {
        return new TelegramResponse();
    }

    protected function textResponse(?string $text) : TelegramResponse
    {
        return $this->response()->setText(empty($text) ? "*Empty message error*" : $text);
    }
}