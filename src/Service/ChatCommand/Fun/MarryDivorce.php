<?php

declare(strict_types=1);

namespace App\Service\ChatCommand\Fun;

use App\DTO\ChatCommandResult;
use App\Service\ChatCommand\ChatCommandData;
use App\Service\ChatCommand\ChatCommandInterface;
use App\Service\JobPublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('chat_command', ['regex' => "/\!Развод/"])]
final readonly class MarryDivorce
    implements ChatCommandInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private JobPublisher $jobPublisher,
    ) {}

    public function run(ChatCommandData $data): ChatCommandResult
    {
        $marry = $data->who->getMarry();

        if ($marry !== null) {
            $text = sprintf(
                'Брак между %s расторгнут.',
                $marry->getParticipantsFirstNames(', ')
            );

            $marry->divorce();
            $this->entityManager->remove($marry);

            $this->jobPublisher->sendMessage($data->chat, $text);
        }

        return ChatCommandResult::success();
    }
}
