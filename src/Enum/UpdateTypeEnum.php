<?php

declare(strict_types=1);

namespace App\Enum;

enum UpdateTypeEnum: string
{
    case Chat = 'chat';
    case GroupChat = 'groupchat';
}
