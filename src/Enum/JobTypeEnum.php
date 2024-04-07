<?php

declare(strict_types=1);

namespace App\Enum;

enum JobTypeEnum: string
{
    case Ping = 'ping';
    case GetRoster = 'get_roster';
    case GetChats = 'get_chats';
    case Subscribe = 'subscribe';
    case SendMessage = 'send_message';
    case MuteUser = 'mute_user';
    case Custom = 'custom';
}
