<?php


namespace Verse\Telegram\Controller;


use Verse\Telegram\Service\VerseTelegramClient;
use Verse\Run\Controller\SimpleController;

class Test extends SimpleController
{
    function get() {
        $puller = new VerseTelegramClient();

        return $puller->get();
    }
}