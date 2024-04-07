<?php

declare(strict_types=1);

namespace App\DTO\Job;

final readonly class SendMessage
{
    /**
     * @param string $recipient
     * @param "chat"|"groupchat" $dialogType
     * @param string $body
     */
    public function __construct(
        public string $recipient,
        public string $dialogType,
        public string $body,
    ) {}
}
