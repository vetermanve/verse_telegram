<?php


namespace Verse\Telegram\Run\Spec;


class MessageType
{
    public const MESSAGE = 'message';
    public const EDITED_MESSAGE = 'edited_message';
    public const CHANNEL_POST = 'channel_post';
    public const EDITED_CHANNEL_POST = 'edited_channel_post';
    public const INLINE_QUERY = 'inline_query';
    public const CHOSEN_INLINE_RESULT = 'chosen_inline_result';
    public const CALLBACK_QUERY = 'callback_query';
    public const SHIPPING_QUERY = 'shipping_query';
    public const PRE_CHECKOUT_QUERY = 'pre_checkout_query';
    public const POLL = 'poll';

    /* MESSAGE TYPES */
    public const LEFT_CHAT_MEMBER = 'left_chat_member';
    public const TEXT_MESSAGE = 'text_message';
    public const NEW_CHAT_MEMBERS = 'new_chat_members';
    public const GROUP_CHAT_CREATED = 'group_chat_created';

    /* virtual types */
    public const NOT_SUPPORTED = 'not_supported';
    public const COMMAND = 'command';

    public const TYPES = [
        self::CALLBACK_QUERY,
        self::CHOSEN_INLINE_RESULT,
        self::EDITED_MESSAGE,
        self::INLINE_QUERY,
        self::EDITED_CHANNEL_POST,
        self::CHANNEL_POST,
        self::SHIPPING_QUERY,
        self::PRE_CHECKOUT_QUERY,
        self::POLL,
        self::MESSAGE
    ];

    public const MESSAGE_SUBTYPES = [
        self::LEFT_CHAT_MEMBER,
        self::NEW_CHAT_MEMBERS,
        self::TEXT_MESSAGE,
        self::GROUP_CHAT_CREATED,
    ];
}