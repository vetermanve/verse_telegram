<?php


namespace Verse\Telegram\Run\Channel\Util;


class MessageRoute
{
    public const CHANNEL = 'tg';

    public const APPEAR_NEW_MESSAGE = 'new_msg';
    public const APPEAR_CALLBACK_ANSWER = 'callback';
    public const APPEAR_EDIT_MESSAGE = 'edit';

    private const DELIMITER = ':';

    protected $channel = self::CHANNEL;
    protected $chatId = 0;
    protected $appear = self::APPEAR_NEW_MESSAGE;
    protected $originEntity = '';

    /**
     * MessageRoute constructor.
     * @param string $routeString
     */
    public function __construct($routeString = '')
    {
        if ($routeString) {
            $this->unpackString($routeString);
        }
    }

    public function packString() {
        return implode(self::DELIMITER, [
            $this->channel,
            $this->chatId,
            $this->appear,
            $this->originEntity
        ]);
    }

    public function unpackString($routeString) {
        [
            $this->channel,
            $this->chatId,
            $this->appear,
            $this->originEntity
        ] = explode(self::DELIMITER, $routeString);
    }

    /**
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     */
    public function setChannel(string $channel): void
    {
        $this->channel = $channel;
    }

    /**
     * @return int
     */
    public function getChatId(): int
    {
        return $this->chatId;
    }

    /**
     * @param int $chatId
     */
    public function setChatId(int $chatId): void
    {
        $this->chatId = $chatId;
    }

    /**
     * @return string
     */
    public function getAppear(): string
    {
        return $this->appear;
    }

    /**
     * @param string $appear
     */
    public function setAppear(string $appear): void
    {
        $this->appear = $appear;
    }

    /**
     * @return mixed
     */
    public function getOriginEntity()
    {
        return $this->originEntity;
    }

    /**
     * @param mixed $originEntity
     */
    public function setOriginEntity($originEntity): void
    {
        $this->originEntity = $originEntity;
    }

}