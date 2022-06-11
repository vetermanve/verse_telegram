<?php


namespace Verse\Telegram\Run\Provider;

use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\User;
use Verse\Run\Spec\HttpRequestMetaSpec;
use Verse\Telegram\Run\Channel\Util\MessageRoute;
use Verse\Telegram\Run\ChannelState\TelegramState;
use Verse\Telegram\Run\Spec\DisplayControl;
use Verse\Telegram\Run\Spec\MessageType;
use Verse\Telegram\Run\Storage\PullUpdatesStorage;
use Verse\Telegram\Service\VerseTelegramClient;
use Verse\Run\Provider\RequestProviderProto;
use Verse\Run\RunRequest;

class TelegramGetUpdatesProvider extends RequestProviderProto
{

    private VerseTelegramClient $puller;

    private $alreadyReadUpdates = [];

    private $lastUpdateId = 0;

    private PullUpdatesStorage $updateTrackerStorage;

    public function prepare()
    {
        $this->puller = new VerseTelegramClient();
        $this->updateTrackerStorage = new PullUpdatesStorage();
    }

    public function run()
    {
        $lastUpdateInfo = $this->updateTrackerStorage->read()->get('last_update', __METHOD__, 0);

        $this->lastUpdateId = (double)$lastUpdateInfo['offset'] ?? 0;
        $this->runtime->debug('TELEGRAM_PULL_START', ['offsetId' => $this->lastUpdateId]);

        while (true) {
            $updates = $this->puller->get($this->lastUpdateId);
            $this->runtime->debug('TELEGRAM_PULL_UPDATES', ['count' => count($updates), 'offset' => $this->lastUpdateId]);

            foreach ($updates as $index => $update) {
                $this->runtime->runtime('TELEGRAM_PULL_UPDATES', $update->all());

                $updateId = $update->updateId;

                $this->lastUpdateId = $updateId;
                if ($this->lastUpdateId > 0) {
                    $this->updateTrackerStorage->write()->update('last_update', ['offset' => $this->lastUpdateId], __METHOD__);
                }

                if (isset($this->alreadyReadUpdates[$updateId])) {
                    continue;
                }

                $request = $this->processUpdate($updateId, $update);

                if ($request) {
                    $request->data['update'] = $update;
                    $this->core->process($request);
                } else {
                    $this->runtime->warning('Skipping message', [$update]);
                }
            }

            if (empty($updates)) {
                sleep(1);
            }
        }
    }

    /**
     * @param int $updateId
     * @param Update $update
     * @return RunRequest
     */
    protected function processUpdate(int $updateId, Update $update): RunRequest
    {
        $this->alreadyReadUpdates[$updateId] = time();

        $method = $this->_getMethod($update);
        $chatId = $update->getChat()->id ?? 0;

        $commandString = '';
        $resource = '/';
        $params = [];


        $replyRoute = new MessageRoute();
        $replyRoute->setChatId($chatId);
        $replyRoute->setOriginEntity($update->getMessage()->messageId ?? '');

        $text = $update->getMessage()->text ?? '';

        // defining command string for different types of messages
        if ($method === MessageType::CALLBACK_QUERY) {
            $commandString = $update->getCallbackQuery()->data;
        } else if (!empty($text)) {
            $commandString = $this->_detectCommand($text);
        }

        if ($commandString !== '') {
            $resource = parse_url($commandString, PHP_URL_PATH);
            $paramsSting = parse_url($commandString, PHP_URL_QUERY);
            if ($paramsSting) {
                parse_str($paramsSting, $params);
            }

            $text = trim(str_replace($commandString, '', $text));
        }

        // if appearance (how message appear in user chat was set remotely
        if (isset($params[DisplayControl::PARAM_SET_APPEARANCE])) {

            $messageId = $update->getMessage()->get('message_id');

            if ($params[DisplayControl::PARAM_SET_APPEARANCE] === MessageRoute::APPEAR_CALLBACK_ANSWER) {
                $replyRoute->setAppear(MessageRoute::APPEAR_CALLBACK_ANSWER);
                $replyRoute->setOriginEntity($update->callbackQuery->id);
            } elseif ($params[DisplayControl::PARAM_SET_APPEARANCE] === MessageRoute::APPEAR_EDIT_MESSAGE && $messageId) {
                $replyRoute->setAppear(MessageRoute::APPEAR_EDIT_MESSAGE);
                $replyRoute->setOriginEntity($messageId);
            } else {
                $replyRoute->setAppear(MessageRoute::APPEAR_NEW_MESSAGE);
            }

            unset($params[DisplayControl::PARAM_SET_APPEARANCE]);

        // if it's a native callback query - answer as callback answer
        } else if ($method === MessageType::CALLBACK_QUERY) {
            $replyRoute->setOriginEntity($update->callbackQuery->id);
            $replyRoute->setAppear(MessageRoute::APPEAR_CALLBACK_ANSWER);

        // if this originally edited message - will try to edit message right behind
        } else if ($method === MessageType::EDITED_MESSAGE) {
            $replyRoute->setAppear(MessageRoute::APPEAR_EDIT_MESSAGE);
            $replyRoute->setOriginEntity($replyRoute->getOriginEntity() + 1); // suggesting that our response was right after user message
        }

        $this->runtime->debug("Got UPDATE", [
            'method' => $method,
            'resource' => $resource,
            'params' => $params,
        ]);

        $request = new RunRequest($updateId, $resource, $replyRoute->packString());
        $request->params = $params;
        $request->meta[HttpRequestMetaSpec::REQUEST_METHOD] = $method;

        $state = $request->getChannelState();

        $state->set(TelegramState::CHAT_ID, $update->getChat()->id);

        $user = $update->getMessage()->from;
        /* @var $user User */
        $state->set(TelegramState::USER_ID, $user ? $user->id : 0);
        $state->set(TelegramState::USER_USERNAME, $user ? '' . $user->username : '');
        $state->set(TelegramState::USER_FIRST_NAME, $user ? $user->firstName : '');
        $state->set(TelegramState::USER_LAST_NAME, $user ? $user->lastName : '');
        $state->set(TelegramState::USER_IS_BOT, $user ? $user->isBot : false);
        $state->set(TelegramState::USER_LANGUAGE_CODE, $user ? $user->id : 'en');
//                $state->set('user.id', $user ? $user->id : 0);

        switch ($method) {
            case MessageType::TEXT_MESSAGE:
                $request->data = $update->message->all();
                $request->data['text'] = $text;
                break;

            case MessageType::EDITED_MESSAGE:
                $request->data = $update->editedMessage->all();
                $request->data['text'] = $text;
                break;

            case MessageType::CALLBACK_QUERY:
                $request->data = $update->callbackQuery->all();
                break;

            case MessageType::NEW_CHAT_MEMBERS:
                $request->data = $update->message->get(MessageType::NEW_CHAT_MEMBERS)->all();
                break;

            case MessageType::LEFT_CHAT_MEMBER:
                $request->data = $update->message->get(MessageType::LEFT_CHAT_MEMBER)->all();
                break;

            case MessageType::GROUP_CHAT_CREATED:
                $request->data = $update->getChat()->all();
                break;

            default:
                $this->runtime->debug("Got Message of unsupported type", (array)$update);

                $replyRoute->setAppear(MessageRoute::APPEAR_NEW_MESSAGE);
                $request = new RunRequest($updateId, '/telegram/' . $method, $replyRoute->packString());
                $request->data = $update->getMessage();
                $request->meta[HttpRequestMetaSpec::REQUEST_METHOD] = MessageType::NOT_SUPPORTED;
                break;
        }

        return $request;
    }


    private function _detectCommand(string $message) : string
    {
        // cut message to detect command
        $message = substr(trim($message), 0, 256);

        // replace all white-space characters to detect white spaces;
        $message = strtr($message, ["\t" => ' ', "\n" => ' ', "\r" => ' ', "\0" => ' ', "\x0B" => ' ']);

        if ($message[0] === '/') {
            $pos = strpos($message, ' ');
            if ($pos === false) {
                return $message;
            }

            return substr($message, 0, $pos);
        }

        return '';
    }

    private function _getMethod(Update $update): string
    {
        $keysIdx = array_flip($update->keys()->toArray());

        $method = MessageType::NOT_SUPPORTED;
        foreach (MessageType::TYPES as $typeVar) {
            if (isset($keysIdx[$typeVar])) {
                $method = $typeVar;
            }
        }

        $this->runtime->runtime(__METHOD__, ['step' => 1, 'keys' => $keysIdx, 'method' => $method,]);

        if ($method === MessageType::MESSAGE) {
            $method = MessageType::TEXT_MESSAGE;

            $keysIdx = array_flip($update->getMessage()->keys()->toArray());
            foreach (MessageType::MESSAGE_SUBTYPES as $typeVar) {
                if (isset($keysIdx[$typeVar])) {
                    $method = $typeVar;
                }
            }

            $this->runtime->runtime(__METHOD__, ['step' => 2, 'keys' => $keysIdx, 'method' => $method,]);
        }

        return $method;
    }
}