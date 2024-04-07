<?php

declare(strict_types=1);

namespace App\Enum;

enum UpdateHandleStatusEnum: int
{
    case IN_PROGRESS = 1;
    case FAILED = 3;
    case REJECTED = 4;
    case FULFILLED = 5;
}
