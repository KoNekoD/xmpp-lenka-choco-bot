<?php

declare(strict_types=1);

namespace App\Enum;

enum UpdateUserTypeEnum: string
{
    case Chat = 'chat';
    case GroupChat = 'group_chat';
}
