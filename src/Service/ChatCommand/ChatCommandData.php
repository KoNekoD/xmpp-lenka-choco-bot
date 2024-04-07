<?php

declare(strict_types=1);

namespace App\Service\ChatCommand;

use App\Entity\Member;
use App\Entity\Update;
use App\Entity\UpdateChat;
use App\Entity\UpdateUser;

final readonly class ChatCommandData
{
    public UpdateChat $chat;
    public UpdateUser $who;

    /** @param string[] $arguments */
    public function __construct(
        public Update $update,
        public Member $whoMember,
        public array $arguments = [],
    ) {
        $this->chat = $this->update->getChat();
        $this->who = $this->update->getMessage()->getFrom();
    }
}
