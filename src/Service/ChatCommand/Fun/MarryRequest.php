<?php

declare(strict_types=1);

namespace App\Service\ChatCommand\Fun;

use App\DTO\ChatCommandResult;
use App\Entity\Marry;
use App\Entity\Member;
use App\Exception\MarryException;
use App\Repository\ChatMemberRepository;
use App\Service\ChatCommand\ChatCommandData;
use App\Service\ChatCommand\ChatCommandInterface;
use App\Service\JobPublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('chat_command', ['regex' => "/^(\/do)\s(.*)((\s+)?\n.*)?$/"])]
final readonly class MarryRequest
    implements ChatCommandInterface
{
    public function __construct(
        private ChatMemberRepository $chatMemberRepository,
        private EntityManagerInterface $entityManager,
        private JobPublisher $jobPublisher,
    ) {}

    public function run(ChatCommandData $data): ChatCommandResult
    {
        $who = $data->who;

        $targetList = $this->chatMemberRepository->findChatMembersByUsernames(
            $data->update,
            $this->getTargetUsernameList($data)
        );

        /** @var Member[] $marryList */
        $marryList = [$who, ...$targetList];

        $rawTargets = $this->getTargetUsernameListRaw($data);

        foreach ($targetList as $targetItem) {
            if ($targetItem->getId() === $who->getId()) {
                $text = 'Сделать предложение самому себе? Так делать нельзя';
                $this->jobPublisher->sendMessage($data->chat, $text);

                return ChatCommandResult::fail($text, true);
            }
        }

        if (count($marryList) < 2) {
            $text = 'Количество участников брака должно быть больше одного';
            $this->jobPublisher->sendMessage($data->chat, $text);

            return ChatCommandResult::fail($text, true);
        }

        if (count($targetList) !== count($rawTargets)) {
            $unknownTargets = $rawTargets;
            foreach ($targetList as $target) {
                foreach ($unknownTargets as $i => $unknownTarget) {
                    if (
                        str_contains(
                            $unknownTarget,
                            $target->getUserFirstName()
                        )
                    ) {
                        unset($unknownTargets[$i]);
                    }
                }
            }
            $text = sprintf(
                'unknown targets: %s',
                implode(', ', $unknownTargets)
            );
            $this->jobPublisher->sendMessage($data->chat, $text);

            return ChatCommandResult::fail($text, true);
        }

        try {
            foreach ($marryList as $participant) {
                if ($participant->isMarried()) {
                    throw new MarryException(
                        sprintf(
                            'User %s already married',
                            $participant->getUserFirstName()
                        )
                    );
                }
            }

            $marry = new Marry();

            foreach ($marryList as $participant) {
                $marry->addParticipant($participant->getUser());
            }

            //
            if (null === $who->getMarry()) {
                throw new MarryException(
                    sprintf(
                        'ChocoUser %s does not have marry requests',
                        $who->getFullyQualifiedNick()
                    )
                );
            }
            $who->acceptMarry();

            $participantsToNotify = $who->getMarryOrThrow()->getParticipants();

            $mentionString = '';
            foreach ($participantsToNotify as $participant) {
                if ('' !== $mentionString) {
                    $mentionString .= ', ';
                }
                $mentionString .= $participant->getFullyQualifiedNick();
            }

            $text = sprintf(
                'Брак между %s успешно заключен',
                $mentionString
            );
            $this->jobPublisher->sendMessage($data->chat, $text);

            $participantsToNotify = [];
            foreach ($marryList as $participant) {
                if ($participant->getUser()->getId() !== $who->getId()) {
                    $participantsToNotify[] = $participant;
                }
            }

            if ($participantsToNotify === []) {
                $text = sprintf(
                    'Участник %s сделал предложение... Но участников нет',
                    $who->getFullyQualifiedNick(),
                );
                $this->jobPublisher->sendMessage($data->chat, $text);
            }

            $mentionString = '';
            foreach ($participantsToNotify as $participant) {
                if ('' !== $mentionString) {
                    $mentionString .= ', ';
                }
                $mentionString .= $participant->getUserFirstName();
            }

            $text = sprintf(
                'Участник %s сделал предложение %s. '.
                'Принять предложение: Брак да, Отвергнуть: Брак нет',
                $who->getFullyQualifiedNick(),
                $mentionString
            );
            $this->jobPublisher->sendMessage($data->chat, $text);

            $this->entityManager->persist($marry);
            $this->entityManager->flush();

            return ChatCommandResult::success();
        } catch (MarryException $e) {
            return ChatCommandResult::fatal($e);
        }
    }

    /** @return string[] */
    public function getTargetUsernameList(ChatCommandData $command): array
    {
        $args = $command->arguments;

        $usernames = explode(' ', $args[2]);

        return array_map(trim(...), $usernames);
    }

    /** @return string[] */
    public function getTargetUsernameListRaw(ChatCommandData $command): array
    {
        return explode(' ', $command->arguments[2]);
    }
}
