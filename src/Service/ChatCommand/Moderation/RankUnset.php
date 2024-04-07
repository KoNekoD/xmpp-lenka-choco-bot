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

#[AutoconfigureTag('chat_command', ['regex' => "/^(Снять|Разжаловать)\s(.*)$/"])]
final readonly class RankUnset
    implements ChatCommandInterface
{
    /**
     * $args[0] - Full command
     * $args[1] - Command body
     * $args[2] - Targeted chat user.
     */

    public function __construct(
        private ChatMemberRepository $chatMemberRepository,
        private ChatMemberAuthenticator $memberAuthenticator,
        private JobPublisher $jobPublisher
    ) {}

    public function run(ChatCommandData $data): ChatCommandResult
    {
        try {
            $target = $this->chatMemberRepository->findChatMemberByFirstMentionOrUsername(
                $data->update,
                $this->getTargetUsername($data)
            );
            if (MemberRankStatusEnum::Member === $target->getRank(
                )->getRankValue()) {
                $text = 'Выбранный участник не имеет ранга модератора';
                $this->jobPublisher->sendMessage($data->chat, $text);

                return ChatCommandResult::success();
            }
            $this->memberAuthenticator->authenticateRank(
                $data->whoMember,
                $target->getRank()->getRankValue(),
                $target
            );
            $target->rankUpdate(MemberRankStatusEnum::Member);
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

        return trim($args[2]);
    }
}
