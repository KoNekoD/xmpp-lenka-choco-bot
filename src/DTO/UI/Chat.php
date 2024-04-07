<?php

declare(strict_types=1);

namespace App\DTO\UI;

final readonly class Chat
{
    public function __construct(public string $name, public string $jid) {}
}
