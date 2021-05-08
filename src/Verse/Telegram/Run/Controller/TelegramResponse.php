<?php


namespace Verse\Telegram\Run\Controller;


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
     * @param string $url
     * @return $this
     */
    public function addKeyboardKey(string $text, string $url) : TelegramResponse {
        $this->keyboard[$text] = $url;
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