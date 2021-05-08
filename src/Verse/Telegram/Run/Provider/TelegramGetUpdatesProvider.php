<?php


namespace Verse\Telegram\Run\Provider;

use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\User;
use Verse\Run\Spec\HttpRequestMetaSpec;
use Verse\Telegram\Run\ChannelState\TelegramState;
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
        $lastUpdateInfo = $this->updateTrackerStorage->read()->get('last_update', __METHOD__,0);

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

                $this->alreadyReadUpdates[$updateId] = time();

                $method = $this->_getMethod($update);
                $chatId = $update->getChat()->id ?? 0;

                $commandString = '';
                $resource = '/';
                $params = [];

                $reply = 'tg:'.$chatId.':'.MessageType::MESSAGE.':'.($update->getMessage()->messageId ?? '');
                $text = $update->getMessage()->text ?? '';

                if ($method === MessageType::CALLBACK_QUERY) {
                    $commandString = $update->getCallbackQuery()->data;
                    $reply = 'tg:'.$chatId.':'.MessageType::CALLBACK_QUERY.':'.$update->callbackQuery->id;
                } elseif (!empty($text)) {
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

                $this->runtime->debug("Got UPDATE", [
                    'method' => $method,
                    'resource' => $resource,
                    'params' => $params,
                ]);

                $request = new RunRequest($updateId, $resource, $reply);
                $request->params = $params;
                $request->meta[HttpRequestMetaSpec::REQUEST_METHOD] = $method;

                $state = $request->getChannelState();

                $state->set(TelegramState::CHAT_ID, $update->getChat()->id);

                $user = $update->getMessage()->from;
                /* @var $user User */
                $state->set(TelegramState::USER_ID, $user ? $user->id : 0);
                $state->set(TelegramState::USER_USERNAME, $user ? ''.$user->username : '');
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
                        $request->data = $update->getCallbackQuery();
                        break;

                    case MessageType::NEW_CHAT_MEMBERS:
                        $request->data = $update->message[MessageType::NEW_CHAT_MEMBERS];
                        break;

                    case MessageType::LEFT_CHAT_MEMBER:
                        $request->data = $update->message[MessageType::LEFT_CHAT_MEMBER];
                        break;

                    case MessageType::GROUP_CHAT_CREATED:
                        $request->data = $update->getChat()->all();
                        break;

                    default:
                        $this->runtime->debug("Got Message of unsupported type", (array)$update);
                        $reply = 'tg:'.$chatId.':'.MessageType::MESSAGE.':'.$update->message->messageId;
                        $request = new RunRequest($updateId, '/telegram/'.$method, $reply);
                        $request->data = $update->getMessage();
                        $request->meta[HttpRequestMetaSpec::REQUEST_METHOD] = MessageType::NOT_SUPPORTED;
                        break;
                }

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

    private function _detectCommand(string $message)
    {
        // cut message to detect command
        $message = substr(trim($message), 0, 256);

        // replace all white-space characters to detect white spaces;
        $message = strtr($message, ["\t" => ' ',"\n" => ' ',"\r" => ' ', "\0" => ' ',"\x0B" => ' ']);

        if ($message[0] === '/') {
            $pos = strpos($message, ' ');
            if ($pos === false) {
                return $message;
            }

            return substr($message, 0, $pos);
        }
    }

    private function _getMethod(Update $update) : string {
        $keysIdx = array_flip($update->keys()->toArray());

        $method = MessageType::NOT_SUPPORTED;
        foreach (MessageType::TYPES as $typeVar) {
            if ($keysIdx[$typeVar]) {
                $method = $typeVar;
            }
        }

        $this->runtime->runtime(__METHOD__, ['step' => 1,'keys' => $keysIdx, 'method' => $method,]);

        if ($method === MessageType::MESSAGE) {
            $method = MessageType::TEXT_MESSAGE;

            $keysIdx = array_flip($update->getMessage()->keys()->toArray());
            foreach (MessageType::MESSAGE_SUBTYPES as $typeVar) {
                if ($keysIdx[$typeVar]) {
                    $method = $typeVar;
                }
            }

            $this->runtime->runtime(__METHOD__, ['step' => 2,'keys' => $keysIdx, 'method' => $method,]);
        }

        return $method;
    }
}