<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Uid\Ulid;

final class UlidService
{
    /** @var array<string> */
    public static array $forcedUlidStack = [];

    public static function generate(): string
    {
        if (self::$forcedUlidStack !== []) {
            return array_shift(self::$forcedUlidStack);
        }

        return Ulid::generate();
    }

    public static function isValid(string $ulid): bool
    {
        return Ulid::isValid($ulid);
    }
}
