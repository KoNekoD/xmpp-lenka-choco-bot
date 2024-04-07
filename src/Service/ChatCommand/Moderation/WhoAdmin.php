<?php

declare(strict_types=1);

namespace App\Service\ChatCommand\Moderation;

use App\DTO\ChatCommandResult;
use App\Repository\ChatMemberRepository;
use App\Service\ChatCommand\ChatCommandData;
use App\Service\ChatCommand\ChatCommandInterface;
use App\Service\ChatCommand\ChatMemberAuthenticator;
use App\Service\JobPublisher;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('chat_command', ['regex' => "/^Кто\sадмин$/"])]
final readonly class WhoAdmin
    implements ChatCommandInterface
{
    public function __construct(
        private ChatMemberRepository $chatMemberRepository,
        private JobPublisher $jobPublisher,
    ) {}

    public function run(ChatCommandData $data): ChatCommandResult
    {
        $members = $this->chatMemberRepository->getChatMembersWithPrivileges(
            $data->chat
        );
        $text = 'Список участников с привилегиями:';
        foreach ($members as $member) {
            $user = $member->getUser();
            $mention = $user->getFullyQualifiedNick();
            $memberPrefix = ChatMemberAuthenticator::getChatMemberPrefix(
                $member->getRank()->getRankValue()
            );
            $text .= "\n $memberPrefix $mention {$member->getRank()->getRankValue()->name}";
        }

        $this->jobPublisher->sendMessage($data->chat, $text);

        return ChatCommandResult::success();
    }
}
