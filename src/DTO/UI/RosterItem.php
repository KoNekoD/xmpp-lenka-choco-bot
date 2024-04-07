<?php

declare(strict_types=1);

namespace App\DTO\UI;

final readonly class RosterItem
{
    public function __construct(
        public string $subscription,
        public ?string $name,
        public string $jid,
    ) {}
}
