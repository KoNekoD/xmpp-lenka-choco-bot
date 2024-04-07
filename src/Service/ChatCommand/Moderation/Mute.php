<?php

declare(strict_types=1);

namespace App\Service\ChatCommand\Moderation;

use App\DTO\ChatCommandResult;
use App\Enum\ChatConfigurationRightsEnum;
use App\Exception\ChatMemberException;
use App\Repository\ChatMemberRepository;
use App\Service\ChatCommand\ChatCommandData;
use App\Service\ChatCommand\ChatCommandInterface;
use App\Service\ChatCommand\ChatMemberAuthenticator;
use App\Service\JobPublisher;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('chat_command', ['regex' => "/^(Мут|мут)\s(.*)(\n(.*))?$/"])]
final readonly class Mute
    implements ChatCommandInterface
{
    public function __construct(
        private ChatMemberRepository $chatMemberRepository,
        private ChatMemberAuthenticator $memberAuthenticator,
        private JobPublisher $jobPublisher,
    ) {}

    public function run(ChatCommandData $data): ChatCommandResult
    {
        try {
            $this->memberAuthenticator->authenticateRankPrimitive(
                $data->whoMember,
                ChatConfigurationRightsEnum::CAN_MUTE->value
            );
            $target = $this->chatMemberRepository->findChatMemberByFirstMentionOrUsername(
                $data->update,
                $this->getTargetUsername($data)
            );
            $this->memberAuthenticator->authenticateRank(
                $data->whoMember,
                $target->getRank()->getRankValue(),
                $target
            );

            $this->jobPublisher->mute($target);
            $text = sprintf(
                'Пользователь %s был замьючен по причине: %s',
                $target->getUserFirstName(),
                $this->getMuteReason($data)
            );
            $this->jobPublisher->sendMessage($data->chat, $text);
        } catch (ChatMemberException $e) {
            return ChatCommandResult::fatal($e);
        }

        return ChatCommandResult::success();
    }

    public function getTargetUsername(ChatCommandData $command): string
    {
        $args = $command->arguments;

        return trim($args[2]);
    }

    public function getMuteReason(ChatCommandData $command): ?string
    {
        $args = $command->arguments;
        if (isset($args[4]) && $args[4] !== '') {
            return $args[4];
        }

        return null;
    }
}
