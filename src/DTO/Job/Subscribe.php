<?php

declare(strict_types=1);

namespace App\DTO\Job;

final readonly class Subscribe
{
    public function __construct(public string $jid) {}
}
