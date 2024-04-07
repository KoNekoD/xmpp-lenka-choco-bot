<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Update;
use App\Enum\UpdateHandleStatusEnum;
use App\Message\UpdateHandleMessage;
use App\Repository\ChatMemberRepository;
use App\Service\ChatCommand\ChatCommandData;
use App\Service\ChatCommand\ChatCommandInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Traversable;

#[AsMessageHandler] final readonly class UpdateHandleMessageHandler
{
    /** @var array<string, ChatCommandInterface> $handlers */
    private array $handlers;

    /** @param array<string, ChatCommandInterface> $handlers */
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private ChatMemberRepository $chatMemberRepository,
        #[TaggedIterator('chat_command', indexAttribute: 'regex')]
        iterable $handlers
    ) {
        $this->handlers = $handlers instanceof Traversable ?
            iterator_to_array($handlers) : $handlers;
    }

    public function __invoke(UpdateHandleMessage $event): void
    {
        $update = $this->entityManager->find(Update::class, $event->updateId);
        $message = $update->getMessage();
        $text = $message->getText();

        /** @var string[] $keys */
        $keys = array_keys($this->handlers);

        /** @var string[] $matches */
        $matches = [];
        $command = null;
        foreach ($keys as $key) {
            if (preg_match($key, $text, $matches)) {
                $command = $this->handlers[$key];
                break;
            }
        }
        if (null === $command) {
            $update->changeStatus(UpdateHandleStatusEnum::REJECTED);
            $this->entityManager->flush();

            return;
        }

        $whoMember = $this->chatMemberRepository->findOrCreateOne(
            user: $update->getMessage()->getFrom(),
            chat: $update->getChat(),
        );

        $result = $command->run(
            data: new ChatCommandData(
                update: $update,
                whoMember: $whoMember,
                arguments: $matches
            )
        );
        if ($result->isOk) {
            $update->changeStatus(UpdateHandleStatusEnum::FULFILLED);
            $this->entityManager->flush();

            return;
        }

        $payload = ['err' => $result->getError(), 'id' => $update->getId()];
        if ($result->isFinallyFailed) {
            $this->logger->warning('Update finally failed', $payload);
            $update->changeStatus(UpdateHandleStatusEnum::REJECTED);
        } else {
            $this->logger->warning('UnknownUpdateElement failed', $payload);
            $update->handleFailed();
        }
        $this->entityManager->flush();
    }
}
