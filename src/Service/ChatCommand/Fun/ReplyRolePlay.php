<?php

declare(strict_types=1);

namespace App\Service\ChatCommand\Fun;

use App\DTO\ChatCommandResult;
use App\Repository\ChatMemberRepository;
use App\Service\ChatCommand\ChatCommandData;
use App\Service\ChatCommand\ChatCommandInterface;
use App\Service\JobPublisher;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('chat_command', ['regex' => "/^(\/do)\s(.*)((\s+)?\n.*)?$/"])]
final readonly class ReplyRolePlay
    implements ChatCommandInterface
{
    public function __construct(
        private ChatMemberRepository $chatMemberRepository,
        private JobPublisher $jobPublisher,
    ) {}

    public function run(ChatCommandData $data): ChatCommandResult
    {
        $who = $data->who;
        $targetUpdateUser = $this->chatMemberRepository
            ->findChatMemberByFirstMentionOrUsername(
                update: $data->update,
                username: $data->update->getMessage()->getText()
            );

        $rolePlayAction = $this->getRolePlayAction($data);
        $postfixPosition = strpos($rolePlayAction, 'ть');
        if (false === $postfixPosition) {
            return ChatCommandResult::fail(
                "RP Action $rolePlayAction is not translatable",
                true
            );
        }

        $pastTimeAction = str_replace(
            'ть',
            'л',
            $rolePlayAction
        );

        $additionalText = '';
        $additionalTextMessage = $this->getRolePlayAdditionalMessage($data);
        if ('' !== $additionalTextMessage) {
            $additionalText = " со словами: $additionalTextMessage";
        }

        $text = sprintf(
            "%s %s %s%s",
            $who->getFullyQualifiedNick(),
            $pastTimeAction,
            $targetUpdateUser->getUserFirstName(),
            $additionalText
        );
        $this->jobPublisher->sendMessage($data->chat, $text);

        return ChatCommandResult::success();
    }

    public function getRolePlayAction(ChatCommandData $command): string
    {
        $args = $command->arguments;

        return $args[2];
    }

    public function getRolePlayAdditionalMessage(ChatCommandData $command
    ): string {
        $args = $command->arguments;

        if (empty($args[3])) {
            return '';
        }

        return $args[3];
    }
}
