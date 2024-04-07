<?php

declare(strict_types=1);

namespace App\Service\ChatCommand;

use App\DTO\ChatCommandResult;

interface ChatCommandInterface
{
    public function run(ChatCommandData $data): ChatCommandResult;
}
