<?php


namespace Verse\Telegram\Run\Processor;


use Verse\Run\ChannelMessage\ChannelMsg;
use Verse\Run\Controller\BaseControllerProto;
use Verse\Run\Interfaces\RequestRouterInterface;
use Verse\Run\Processor\RunRequestProcessorProto;
use Verse\Run\RequestWrapper\RunHttpRequestWrapper;
use Verse\Run\RunRequest;
use Verse\Telegram\Run\Channel\TelegramReplyChannel;
use Verse\Telegram\Run\Controller\TelegramResponse;
use Verse\Telegram\Run\RequestRouter\TelegramRouterByMessageType;

class TelegramUpdateProcessor extends RunRequestProcessorProto
{
    /**
     * @var TelegramRouterByMessageType
     */
    protected TelegramRouterByMessageType $requestRouter;

    public function prepare()
    {
        if (!isset($this->requestRouter)) {
            $this->requestRouter = new TelegramRouterByMessageType();
        }
    }

    public function process(RunRequest $request)
    {
        $response = new ChannelMsg();
        $response->setUid($request->getUid());
        $response->setBody("Not clear.");
        $response->setDestination($request->getReply());
        $response->setChannelState($request->getChannelState());

        $class = $this->requestRouter->getClassByRequest($request);
        $this->runtime->runtime('Got Class ' . $class, ['meta' => $request->meta,]);

        if (!class_exists($class)) {
            $response->setBody('Cannot process: Class missing');
            $this->sendResponse($response, $request);
            return;
        }

        $controller = new $class;
        if (!$controller instanceof BaseControllerProto) {
            $response->setBody('Cannot process: Class missing');
            $this->sendResponse($response, $request);
            return;
        }

        $runRequestWrapper = new RunHttpRequestWrapper();
        $runRequestWrapper->setRequest($request);

        $controller->setRequestWrapper($runRequestWrapper);
        $controller->setMethod($runRequestWrapper->getMethod());

        if (!$controller->validateMethod()) {
            $response->setBody('Method is not valid');
            $this->sendResponse($response, $request);
            return;
        }

        $responseData = $controller->run();

        if ($responseData !== null) {
            if ($responseData instanceof TelegramResponse) {
                $response->body = $responseData->getText();

                if ($responseData->hasKeyboard()) {
                    $keyboard = [];
                    foreach ($responseData->getKeyboard() as $rowId => $keyboardItems) {
                        $keyboardRow = [];
                        foreach ($keyboardItems as $keyTitle => $keyCallbackData) {
                            $keyboardRow[] =
                                [
                                    "text" => $keyTitle,
                                    "callback_data" => $keyCallbackData,
                                ];
                        }
                        $keyboard[] = $keyboardRow;
                    }

                    $response->setMeta(TelegramReplyChannel::KEYBOARD, $keyboard);
                }

            } else if (is_string($responseData)) {
                $response->body = $responseData;
            } else {
                $response->body = 'Error in response data type';
            }

            $this->sendResponse($response, $request);
        }
    }

    /**
     * @return RequestRouterInterface
     */
    public function getRequestRouter(): RequestRouterInterface
    {
        return $this->requestRouter;
    }

    /**
     * @param RequestRouterInterface $requestRouter
     */
    public function setRequestRouter(RequestRouterInterface $requestRouter): void
    {
        $this->requestRouter = $requestRouter;
    }

}