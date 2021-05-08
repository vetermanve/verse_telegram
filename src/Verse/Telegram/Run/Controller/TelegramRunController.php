<?php


namespace Verse\Telegram\Run\Controller;


use Psr\Log\LoggerInterface;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Update;
use Verse\Di\Env;
use Verse\Run\Controller\SimpleController;

/**
 *  Class methods are should be @var \Verse\Telegram\Run\Spec\MessageType constants
 */
abstract class TelegramRunController extends SimpleController
{
    use TelegramControllerTrait;

    public function index() : string {
        return $this->text_message()->getText();
    }

    public function get() : array {
        return [ "text" => $this->text_message() ];
    }

    public function getUpdate() : ?Update
    {
        return $this->requestWrapper->getParam('update');
    }

    public function log($text, $data = [])
    {
        Env::getContainer()->bootstrap(LoggerInterface::class)->debug($text, $data);
    }

}