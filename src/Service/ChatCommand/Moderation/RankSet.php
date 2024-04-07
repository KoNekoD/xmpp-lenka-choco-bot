<?php

declare(strict_types=1);

namespace App\Service\ChatCommand\Moderation;

use App\DTO\ChatCommandResult;
use App\Enum\MemberRankStatusEnum;
use App\Exception\ChatMemberException;
use App\Repository\ChatMemberRepository;
use App\Service\ChatCommand\ChatCommandData;
use App\Service\ChatCommand\ChatCommandInterface;
use App\Service\ChatCommand\ChatMemberAuthenticator;
use App\Service\JobPublisher;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('chat_command', ['regex' => "/^([!]+)(модер)\s(.*)$/"])]
final readonly class RankSet
    implements ChatCommandInterface
{
    /**
     * $args[0] - Full command
     * $args[1] - Rank level, counted by '!' symbol
     * $args[2] - Command body
     * $args[3] - Targeted chat user.
     */

    public function __construct(
        private ChatMemberRepository $chatMemberRepository,
        private ChatMemberAuthenticator $memberAuthenticator,
        private JobPublisher $jobPublisher
    ) {}

    public function run(ChatCommandData $data): ChatCommandResult
    {
        try {
            $target = $this->chatMemberRepository
                ->findChatMemberByFirstMentionOrUsername(
                    update: $data->update,
                    username: $this->getTargetUsername($data)
                );
            $this->memberAuthenticator->authenticateRank(
                who: $data->whoMember,
                requiredAccessRank: $target->getRank()->getRankValue(),
                target: $target
            );
            $this->memberAuthenticator->authenticateRankPrimitive(
                who: $data->whoMember,
                requiredAccessRank: $this->getValue($data)
            );
            $newRank = MemberRankStatusEnum::fromInteger(
                $this->getValue($data)
            );
            $target->rankUpdate($newRank);

            $text = sprintf(
                'Теперь пользователь @%s имеет ранг %s',
                $this->getTargetUsername($data),
                $target->getRank()->getRankValue()->name
            );
            $this->jobPublisher->sendMessage($data->chat, $text);

            return ChatCommandResult::success();
        } catch (ChatMemberException $e) {
            return ChatCommandResult::fatal($e);
        }
    }

    public function getTargetUsername(ChatCommandData $command): string
    {
        $args = $command->arguments;

        return trim($args[3]);
    }

    public function getValue(ChatCommandData $command): int
    {
        $args = $command->arguments;

        return strlen($args[1]);
    }
}
