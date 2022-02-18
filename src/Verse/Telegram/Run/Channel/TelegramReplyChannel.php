<?php


namespace Verse\Telegram\Run\Channel;


use Telegram\Bot\Exceptions\TelegramSDKException;
use Verse\Telegram\Run\Channel\Util\MessageRoute;
use Verse\Telegram\Service\VerseTelegramClient;
use Verse\Run\Channel\DataChannelProto;
use Verse\Run\ChannelMessage\ChannelMsg;
use Verse\Run\RunContext;

class TelegramReplyChannel extends DataChannelProto
{
    const KEYBOARD = 'keyboard';

    /**
     * @var VerseTelegramClient
     */
    private $telegramClient;

    private $identity;

    public function prepare()
    {
        $this->telegramClient = new VerseTelegramClient();
        $this->identity = $this->context->get(RunContext::IDENTITY);
    }

    public function send(ChannelMsg $msg)
    {
        $keyboard = $msg->getMeta(self::KEYBOARD, []);

        $route = new MessageRoute($msg->getDestination());

        if ($route->getChannel() !== MessageRoute::CHANNEL) {
            $this->runtime->error('Channel massage came with wrong destination', ['dest' => $msg->getDestination()]);
            return false;
        }

        $wasSent = false;

        switch ($route->getAppear()) {
            case MessageRoute::APPEAR_CALLBACK_ANSWER:
                $this->telegramClient->answerCallback($msg->getBody(), $route->getOriginEntity());
                $this->runtime->debug('TELEGRAM_REPLY_SENT:CALLBACK', ['request_id' => $msg->getUid(), 'to' => $msg->getDestination()]);
                $wasSent = true;
                break;
            case MessageRoute::APPEAR_NEW_MESSAGE:
                $this->telegramClient->post($route->getChatId(), $msg->getBody(), $keyboard, $route->getOriginEntity());
                $this->runtime->debug('TELEGRAM_REPLY_SENT:MESSAGE', ['request_id' => $msg->getUid(), 'to' => $msg->getDestination()]);
                $wasSent = true;
                break;
            case MessageRoute::APPEAR_EDIT_MESSAGE:
                try {
                    $this->telegramClient->edit($route->getChatId(), $msg->getBody(), $keyboard, $route->getOriginEntity());
                    $this->runtime->debug('TELEGRAM_REPLY_SENT:EDIT', ['request_id' => $msg->getUid(), 'to' => $msg->getDestination()]);
                    $wasSent = true;
                } catch (TelegramSDKException $exception) {
                    $this->runtime->debug('TELEGRAM_REPLY: Has edit mode exception:' . $exception->getMessage(),
                        ['request_id' => $msg->getUid(), 'to' => $msg->getDestination()]);
                    $this->telegramClient->post($route->getChatId(), $msg->getBody(), $keyboard);
                    $this->runtime->debug('TELEGRAM_REPLY_SENT:MESSAGE', ['request_id' => $msg->getUid(), 'to' => $msg->getDestination()]);
                    $wasSent = true;
                }

                break;
        }

        if (!$wasSent) {
            $this->runtime->error('TELEGRAM_REPLY_NOT_SENT UNKNOWN MESSAGE REPLY TYPE', ['request_id' => $msg->getUid(), 'to' => $msg->getDestination()]);
        }

        return $wasSent;
    }
}