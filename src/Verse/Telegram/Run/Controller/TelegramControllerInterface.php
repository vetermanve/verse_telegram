<?php


namespace Verse\Telegram\Run\Controller;


interface TelegramControllerInterface
{
    public function text_message() : ?TelegramResponse;

    public function left_chat_member() : ?TelegramResponse;

    public function new_chat_members() : ?TelegramResponse;

    public function edited_message() : ?TelegramResponse;

    public function not_supported() : ?TelegramResponse;

    public function callback_query() : ?TelegramResponse;

    public function group_chat_created() : ?TelegramResponse;
}