<?php

declare(strict_types=1);

namespace App\Enum;

enum MemberRankStatusEnum: int
{
    case Member = 0;
    case JuniorModerator = 1;
    case SeniorModerator = 2;
    case JuniorAdministrator = 3;
    case SeniorAdministrator = 4;
    case Creator = 5;

    public static function fromInteger(int $rank): self
    {
        if ($rank <= 0) {
            return self::Member; // Min
        }

        foreach (self::cases() as $status) {
            if ($status->value === $rank) {
                return $status;
            }
        }

        return self::Creator; // Max
    }
}
