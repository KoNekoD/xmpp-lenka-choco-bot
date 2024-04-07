<?php

declare(strict_types=1);

namespace App\Service\ChatCommand\Fun;

use App\DTO\ChatCommandResult;
use App\Exception\MarryException;
use App\Service\ChatCommand\ChatCommandData;
use App\Service\ChatCommand\ChatCommandInterface;
use App\Service\JobPublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('chat_command', ['regex' => "/^(Брак)\s(да|нет)$/"])]
final readonly class MarryAnswer
    implements ChatCommandInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private JobPublisher $jobPublisher,
    ) {}

    public function run(ChatCommandData $data): ChatCommandResult
    {
        $who = $data->who;

        try {
            if ($who->isMarryParticipantStatusAccepted()) {
                throw new MarryException(
                    'Вы уже приняли предложение. Развестись: !Развод'
                );
            }

            if ($this->isAnswerAccept($data) && !$who->isMarried()) {
                $who->acceptMarry();
                $who->getMarryOrThrow()->tryAcceptGeneralMarry();

                $text = sprintf(
                    'Брак принят %s',
                    $who->getFullyQualifiedNick()
                );
                $this->jobPublisher->sendMessage($data->chat, $text);
            } else {
                $marry = $who->getMarry();
                if ($marry && !$who->isMarried()) {
                    $marry->divorce();
                    $this->entityManager->remove($marry);
                    $this->entityManager->flush();

                    $text = sprintf(
                        'Брак отвергнут %s',
                        $who->getFullyQualifiedNick()
                    );
                    $this->jobPublisher->sendMessage($data->chat, $text);
                }
            }

            return ChatCommandResult::success();
        } catch (MarryException $e) {
            return ChatCommandResult::fatal($e);
        }
    }

    private function isAnswerAccept(ChatCommandData $command): bool
    {
        $args = $command->arguments;

        return 'да' === $args[2];
    }
}
