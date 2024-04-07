<?php

declare(strict_types=1);

namespace App\Enum;

enum ChatMemberStatusEnum: int
{
    case Creator = 2;

    case Administrator = 1;

    case Member = 0;

    case Left = 3;

    case Kicked = 4;

    case Restricted = 5;

    public function isKicked(): bool
    {
        return self::Kicked === $this;
    }

    public function isLeft(): bool
    {
        return self::Left === $this;
    }
}
