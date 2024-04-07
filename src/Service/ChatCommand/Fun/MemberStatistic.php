<?php

declare(strict_types=1);

namespace App\Service\ChatCommand\Fun;

use App\DTO\ChatCommandResult;
use App\Enum\ChatMemberStatusEnum;
use App\Exception\ChatMemberException;
use App\Exception\ChatMemberKickedOrLeftException;
use App\Repository\ChatMemberRepository;
use App\Service\ChatCommand\ChatCommandData;
use App\Service\ChatCommand\ChatCommandInterface;
use App\Service\ChatCommand\ChatMemberTelemetryProvider;
use App\Service\JobPublisher;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('chat_command', ['regex' => "/^(Кто|Хто)\s((?!я).*)$/"])]
final readonly class MemberStatistic
    implements ChatCommandInterface
{
    /**
     * (?!ABC) - Negative lookahead.
     * Specifies a group that can not match after the main expression
     * (if it matches, the result is discarded).
     */

    public function __construct(
        private ChatMemberRepository $chatMemberRepository,
        private ChatMemberTelemetryProvider $telemetryProvider,
        private JobPublisher $jobPublisher,
    ) {}

    public function run(ChatCommandData $data): ChatCommandResult
    {
        try {
            $target = $this->chatMemberRepository
                ->findChatMemberByFirstMentionOrUsername(
                    $data->update,
                    $this->getTargetUsername($data)
                );

            try {
                $text = $this->telemetryProvider->produce(
                    $target,
                    $target->getUserFirstName(),
                    $target->getUserFirstName()
                );
                $this->jobPublisher->sendMessage($data->chat, $text);
            } catch (ChatMemberKickedOrLeftException) {
                $text = match ($target->getStatus()) {
                    ChatMemberStatusEnum::Kicked => 'Пользователь был кикнут',
                    ChatMemberStatusEnum::Left => 'Пользователь покинул чат',
                    default => throw new LogicException(),
                };

                $this->jobPublisher->sendMessage($data->chat, $text);
            }

            return ChatCommandResult::success();
        } catch (ChatMemberException $e) {
            return ChatCommandResult::fatal($e);
        }
    }

    public function getTargetUsername(ChatCommandData $command): string
    {
        $args = $command->arguments;

        return trim($args[2]);
    }
}
