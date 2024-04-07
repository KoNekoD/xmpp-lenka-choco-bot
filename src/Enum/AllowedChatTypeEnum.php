<?php

declare(strict_types=1);

namespace App\Enum;

enum AllowedChatTypeEnum
{
    case ALL; // Allow all
    case PM; // Only private messages
    case CHAT; // Only group messages
}
