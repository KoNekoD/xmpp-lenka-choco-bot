<?php

declare(strict_types=1);

namespace App\Service\ChatCommand\Fun;

use App\DTO\ChatCommandResult;
use App\Enum\ChatMemberStatusEnum;
use App\Exception\ChatMemberKickedOrLeftException;
use App\Service\ChatCommand\ChatCommandData;
use App\Service\ChatCommand\ChatCommandInterface;
use App\Service\ChatCommand\ChatMemberTelemetryProvider;
use App\Service\JobPublisher;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('chat_command', ['regex' => "/^Кто\sя|Хто\sя/"])]
final readonly class MemberMyStatistic
    implements ChatCommandInterface
{
    public function __construct(
        private ChatMemberTelemetryProvider $telemetryProvider,
        private JobPublisher $jobPublisher
    ) {}

    public function run(ChatCommandData $data): ChatCommandResult
    {
        try {
            $text = $this->telemetryProvider->produce(
                target: $data->whoMember,
                targetMentionString: $data->whoMember->getUserFirstName(),
                targetLinkString: $data->whoMember->getUserFirstName(),
            );
            $this->jobPublisher->sendMessage($data->chat, $text);
        } catch (ChatMemberKickedOrLeftException) {
            $text = match ($data->whoMember->getStatus()) {
                ChatMemberStatusEnum::Kicked => 'Пользователь был кикнут',
                ChatMemberStatusEnum::Left => 'Пользователь покинул чат',
                default => throw new LogicException(),
            };

            $this->jobPublisher->sendMessage($data->chat, $text);
        }

        return ChatCommandResult::success();
    }
}
