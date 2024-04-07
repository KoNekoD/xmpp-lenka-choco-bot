<?php

declare(strict_types=1);

namespace App\Service\ChatCommand\Moderation;

use App\DTO\ChatCommandResult;
use App\Exception\ChatMemberException;
use App\Repository\ChatMemberRepository;
use App\Service\ChatCommand\ChatCommandData;
use App\Service\ChatCommand\ChatCommandInterface;
use App\Service\JobPublisher;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('chat_command', ['regex' => "/^Варны(\s(.*))?$/"])]
final readonly class WarnsList
    implements ChatCommandInterface
{
    public function __construct(
        private ChatMemberRepository $chatMemberRepository,
        private JobPublisher $jobPublisher,
    ) {}

    public function run(ChatCommandData $data): ChatCommandResult
    {
        try {
            if ($this->getTargetUsername($data)) {
                $memberFilter = $this->chatMemberRepository->findChatMemberByFirstMentionOrUsername(
                    $data->update,
                    $this->getTargetUsername($data)
                );
                $warns = $data->chat->getWarnsByWarnedMember($memberFilter);
                $text = "Список предупреждений участника @{$this->getTargetUsername($data)}: \n";
                foreach ($warns as $warn) {
                    if ($warn->isExpired()) {
                        continue;
                    }
                    $text .= sprintf(
                        'От: %s, Причина: %s, Истекает: %s.'."\n",
                        $warn->getCreatorFirstname(),
                        $warn->getReason(),
                        $warn->getExpiresAt()->format('Y-m-d H:i:s')
                    );
                }
            } else {
                $warns = $data->chat->getLastFiveWarns();
                $text = "Список предупреждений: \n";
                foreach ($warns as $warn) {
                    if ($warn->isExpired()) {
                        continue;
                    }
                    $text .= sprintf(
                        'Предупреждение для %s, от: %s, Истекает: %s.'."\n",
                        $warn->getWarnedFirstname(),
                        $warn->getCreatorFirstName(),
                        $warn->getExpiresAt()->format('Y-m-d H:i:s')
                    );
                }
            }

            $this->jobPublisher->sendMessage($data->chat, $text);
        } catch (ChatMemberException $e) {
            return ChatCommandResult::fatal($e);
        }

        return ChatCommandResult::success();
    }

    public function getTargetUsername(ChatCommandData $command): ?string
    {
        $args = $command->arguments;

        if (!isset($args[2])) {
            return null;
        }

        return trim($args[2]);
    }
}
