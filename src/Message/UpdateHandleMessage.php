<?php

declare(strict_types=1);

namespace App\Message;

final readonly class UpdateHandleMessage
{
    public function __construct(public string $updateId) {}
}
