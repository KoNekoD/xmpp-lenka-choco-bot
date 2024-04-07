<?php

declare(strict_types=1);

namespace App\Service\ChatCommand\Moderation;

use App\DTO\ChatCommandResult;
use App\Entity\MemberWarn;
use App\Exception\ChatMemberException;
use App\Repository\ChatMemberRepository;
use App\Service\ChatCommand\ChatCommandData;
use App\Service\ChatCommand\ChatCommandInterface;
use App\Service\ChatCommand\ChatMemberAuthenticator;
use App\Service\JobPublisher;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('chat_command', ['regex' => "/^(Варн)\s(.*)\n(.*)\n(на\s(((\d+)\s)?(день|дня|дней)))$/"])]
final readonly class WarnUser
    implements ChatCommandInterface
{
    /**
     * Варн на 20 дней @user Флуд:
     * [1]=> string(8) "Варн"
     * [2]=> string(21) "Иванов Иван"
     * [3]=> string(50) "Пользователь совершал спам"
     * [4]=> string(14) "на 22 дня"
     * [5]=> string(9) "22 дня"
     * [6]=> string(3) "22 "
     * [7]=> string(2) "22"
     * [8]=> string(6) "дня".
     */
    public function __construct(
        private ChatMemberRepository $chatMemberRepository,
        private ChatMemberAuthenticator $memberAuthenticator,
        private JobPublisher $jobPublisher,
        private EntityManagerInterface $entityManager,
    ) {}

    public function run(ChatCommandData $data): ChatCommandResult
    {
        try {
            $who = $data->who;
            $target = $this->chatMemberRepository->findChatMemberByFirstMentionOrUsername(
                $data->update,
                $this->getTargetUsername($data)
            );
            $this->memberAuthenticator->authenticateRank(
                $data->whoMember,
                $target->getRank()->getRankValue(),
                $target
            );

            $newWarn = new MemberWarn(
                warned: $target,
                creator: $data->whoMember,
                chat: $target->getChat(),
                reason: $this->getWarnReason($data),
                expiresAt: $this->getWarnExpireDateTime($data)
            );
            $this->entityManager->persist($newWarn);
            $this->entityManager->flush();

            $text = sprintf(
                'Участник @%s получил предупреждение до %s, причина: %s',
                $data->whoMember->getUserFirstName(),
                $newWarn->getExpiresAt()->format('Y-m-d H:i:s'),
                $newWarn->getReason()
            );
            $this->jobPublisher->sendMessage($data->chat, $text);
        } catch (ChatMemberException|DomainException $e) {
            return ChatCommandResult::fatal($e);
        }

        return ChatCommandResult::success();
    }

    public function getTargetUsername(ChatCommandData $command): string
    {
        return trim($command->arguments[2]);
    }

    public function getWarnReason(ChatCommandData $command): string
    {
        $args = $command->arguments;
        if (isset($args[3])) {
            return $args[3];
        }

        throw new DomainException('Вы забыли указать причину');
    }

    public function getWarnExpireDateTime(ChatCommandData $command
    ): DateTimeImmutable {
        $days = $command->arguments[7];

        return (new DateTimeImmutable())->modify("+$days days");
    }
}
