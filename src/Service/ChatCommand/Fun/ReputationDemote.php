<?php

declare(strict_types=1);

namespace App\Service\ChatCommand\Fun;

use App\DTO\ChatCommandResult;
use App\Exception\ChatMemberReputationException;
use App\Repository\ChatMemberRepository;
use App\Service\ChatCommand\ChatCommandData;
use App\Service\ChatCommand\ChatCommandInterface;
use App\Service\JobPublisher;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('chat_command', ['regex' => "/^[-]+$/"])]
final readonly class ReputationDemote
    implements ChatCommandInterface
{
    public function __construct(
        private ChatMemberRepository $chatMemberRepository,
        private JobPublisher $jobPublisher,
    ) {}

    /**
     * @throws Exception
     */
    public function run(ChatCommandData $data): ChatCommandResult
    {
        $target = $this->chatMemberRepository
            ->findChatMemberByFirstMentionOrUsername(
                update: $data->update,
                username: $data->update->getMessage()->getText()
            );

        if ($data->whoMember->getId() === $target->getId()) {
            $this->jobPublisher->sendMessage(
                $data->chat,
                'Нельзя унижать себя!'
            );
        }

        for ($i = 0; $i < $this->getChangeValue($data); $i++) {
            try {
                $target->demoteReputation($data->whoMember);
            } catch (ChatMemberReputationException) {
                $this->jobPublisher->sendMessage(
                    $data->chat,
                    'Превышена дневная квота изменения репутации'
                );
            }
        }

        $text = sprintf(
            'Теперь пользователь @%s имеет репутацию %s',
            $data->whoMember->getUserFirstName(),
            $data->whoMember->getReputation()
        );
        $this->jobPublisher->sendMessage($data->chat, $text);

        return ChatCommandResult::success();
    }

    public function getChangeValue(ChatCommandData $command): int
    {
        $args = $command->arguments;

        return strlen($args[0]);
    }
}
