<?php

declare(strict_types=1);

namespace App\DTO\Job;

final readonly class Custom
{
    public function __construct(public string $xml) {}
}
