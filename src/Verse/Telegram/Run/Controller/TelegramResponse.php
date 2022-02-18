<?php


namespace Verse\Telegram\Run\Controller;


use Verse\Telegram\Run\Channel\Util\MessageRoute;
use Verse\Telegram\Run\Spec\DisplayControl;

class TelegramResponse
{
    private array $keyboard = [];

    private string $text = '';
    /**
     * @return array
     */
    public function getKeyboard(): array
    {
        return $this->keyboard;
    }

    public function hasKeyboard() : bool {
        return count($this->keyboard) > 0;
    }

    /**
     * @param array $keyboard
     * @return TelegramResponse
     */
    public function setKeyboard(array $keyboard): TelegramResponse
    {
        $this->keyboard = $keyboard;
        return $this;
    }

    /**
     * @param string $text
     * @param string $resource
     * @param array $data
     * @param string $appearance
     * @param null $entityId
     * @return $this
     */
    public function addKeyboardKey(string $text,
                                   string $resource,
                                   $data = [],
                                   $appearance = MessageRoute::APPEAR_NEW_MESSAGE,
                                   $entityId = null) : TelegramResponse {
        $data[DisplayControl::PARAM_SET_APPEARANCE] = $appearance;
        if ($entityId) {
            $data[DisplayControl::PARAM_SET_ENTITY] = $entityId;
        }

        $this->keyboard[$text] = $resource.(strpos($resource, '?') !== false ? '&' : '?').http_build_str($data);
        return $this;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param string $text
     * @return TelegramResponse
     */
    public function setText(string $text): TelegramResponse
    {
        $this->text = $text;
        return $this;
    }

    public function __toString()
    {
        return $this->text ?? '';
    }

}