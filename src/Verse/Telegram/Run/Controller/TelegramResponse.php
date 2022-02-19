<?php


namespace Verse\Telegram\Run\Controller;


use Exception;
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

    public function hasKeyboard(): bool
    {
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
     * @return TelegramResponse
     * @throws Exception
     */
    public function upendKeyboardKey(string $text,
                                     string $resource,
                                     $data = [],
                                     $appearance = MessageRoute::APPEAR_NEW_MESSAGE,
                                     $entityId = null
    ) : TelegramResponse
    {
        return $this->addKeyboardKey(
            $text,
            $resource,
            $data,
            $appearance,
            true
            );
    }

    /**
     * @param string $buttonText
     * @param string $resource
     * @param array $data
     * @param string $appearance
     * @param bool $appendToPrevious
     * @return $this
     * @throws Exception
     */
    public function addKeyboardKey(string $buttonText,
                                   string $resource,
                                   $data = [],
                                   $appearance = MessageRoute::APPEAR_NEW_MESSAGE,
                                   $appendToPrevious = false
    ): TelegramResponse
    {
        if (!is_array($data)) {
            trigger_error('Data is not an array. Type "'.gettype($data).'"', E_USER_WARNING);
            $data = [];
        }

        $data [DisplayControl::PARAM_SET_APPEARANCE] = $appearance;

        $dataString = $resource . (strpos($resource, '?') !== false ? '&' : '?') . http_build_query($data);
        if (($dataLen = strlen($dataString)) > 64) {
            throw new Exception('Keyboard data is too long: ' . $dataLen . ' (' . $dataString . ')');
        }

        $buttonRow = sizeof($this->keyboard) > 0 ? array_key_last($this->keyboard) : 0;
        if (!$appendToPrevious) {
            $buttonRow++;
        }

        $this->keyboard[$buttonRow][$buttonText] = $dataString;

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