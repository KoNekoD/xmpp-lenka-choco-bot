<?php

declare(strict_types=1);

namespace App\Enum;

enum UserMarryStatusEnum: string
{
    case NOT_ACCEPTED = 'Marry request';
    case ACCEPTED = 'Accepted';
}
