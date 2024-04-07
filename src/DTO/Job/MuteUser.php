<?php

declare(strict_types=1);

namespace App\DTO\Job;

final readonly class MuteUser
{
    public function __construct(public string $to, public string $nick) {}
}
