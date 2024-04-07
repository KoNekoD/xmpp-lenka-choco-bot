<?php

declare(strict_types=1);

namespace App\Service\ChatCommand\Fun;

use App\DTO\ChatCommandResult;
use App\Exception\ChatMemberException;
use App\Repository\ChatMemberRepository;
use App\Service\ChatCommand\ChatCommandData;
use App\Service\ChatCommand\ChatCommandInterface;
use App\Service\JobPublisher;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('chat_command', ['regex' => "/^(\/me)\s(\S+)\s(.*)((\s+)?\n.*)?$/"])]
final readonly class SimpleRolePlay
    implements ChatCommandInterface
{
    public function __construct(
        private ChatMemberRepository $chatMemberRepository,
        private JobPublisher $jobPublisher
    ) {}

    /**
     * @throws ChatMemberException
     */
    public function run(ChatCommandData $data): ChatCommandResult
    {
        $who = $data->who;
        $target = $this->chatMemberRepository->findChatMemberByFirstMentionOrUsername(
            $data->update,
            $this->getRolePlayTarget($data)
        );

        $postfixPosition = strpos($this->getRolePlayAction($data), 'ть');
        if (false === $postfixPosition) {
            return ChatCommandResult::fail(
                "RP Action {$this->getRolePlayAction($data)} is not translatable",
                true
            );
        }

        $rolePlayAction = $this->getRolePlayAction($data);
        $pastTimeAction = str_replace('ть', 'л', $rolePlayAction);

        $additionalText = '';
        $additionalTextMessage = $this->getRolePlayAdditionalMessage($data);
        if ('' !== $additionalTextMessage) {
            $additionalText = " со словами: $additionalTextMessage";
        }

        $text = sprintf(
            "%s %s %s%s",
            $who->getFullyQualifiedNick(),
            $pastTimeAction,
            $target->getUserFirstName(),
            $additionalText
        );
        $this->jobPublisher->sendMessage($data->chat, $text);

        return ChatCommandResult::success();
    }

    public function getRolePlayTarget(ChatCommandData $command): string
    {
        $args = $command->arguments;

        return trim($args[3]);
    }

    public function getRolePlayAction(ChatCommandData $command): string
    {
        $args = $command->arguments;

        return $args[2];
    }

    public function getRolePlayAdditionalMessage(ChatCommandData $command
    ): string {
        $args = $command->arguments;

        if (empty($args[4])) {
            return '';
        }

        return $args[4];
    }
}
