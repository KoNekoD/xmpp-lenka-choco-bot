<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\UpdateUser;

final readonly class ChatMemberMessagesStatDTO
{
    public function __construct(public UpdateUser $user, public int $count) {}
}
