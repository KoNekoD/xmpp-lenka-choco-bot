<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\DTO\XmlElement;
use App\Entity\UnknownUpdateElement;
use App\Entity\Update;
use App\Entity\UpdateChat;
use App\Entity\UpdateMessage;
use App\Entity\UpdateUser;
use App\Enum\UpdateTypeEnum;
use App\Message\NewXmlElementMessage;
use App\Message\UpdateHandleMessage;
use App\Repository\ChocoRepository;
use App\Repository\JobRepository;
use App\Service\SerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler] final readonly class NewXmlElementMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerService $serializer,
        private LoggerInterface $logger,
        private MessageBusInterface $bus,
        private JobRepository $jobRepository,
        private ChocoRepository $chocoRepository,
    ) {}

    public function __invoke(NewXmlElementMessage $message): void
    {
        if ($message->element->name !== 'root') {
            throw new RuntimeException('Expected root element');
        }

        foreach ($message->element->children as $child) {
            if (isset($child->attributes['id'])) {
                $jobId = $child->attributes['id'];

                $job = $this->jobRepository->find($jobId);
                if (null !== $job) {
                    $job->markAsCompleted(
                        $this->serializer->normalize($child)
                    );
                    continue;
                }
            }

            match ($child->name) {
                'message' => $this->handleMessage($child),
                'presence' => null, // Presence should be ignored(unneeded)
                default => $this->saveUnknownChild($child),
            };
        }

        $this->entityManager->flush();
    }

    private function handleMessage(XmlElement $child): void
    {
        $body = $child->findFirstChildWithName('body');
        if (!$body) {
            $this->saveUnknownChild($child);

            return;
        }

        $this->logger->info($this->serializer->serialize($child));

        $unicodeString = $body->content;
        $stringUTF8 = json_decode('"'.$unicodeString.'"');
        if (!is_string($stringUTF8)) {
            return;
        }

        $type = $child->attributes['type'] ?? null;
        if (!in_array($type, ['chat', 'groupchat'], true)) {
            return;
        }

        $from = $child->attributes['from'] ?? null;
        if (!is_string($from)) {
            return;
        }

        $fromArr = explode('/', $from);
        $fromJID = $fromArr[0];

        $chat = $this->chocoRepository->findChatByJid($fromJID);
        if (null === $chat) {
            $chat = new UpdateChat(
                type: UpdateTypeEnum::from($type),
                jid: $fromJID,
                title: $fromJID // will be updated later to correct value
            );
            $this->entityManager->persist($chat);
            $this->entityManager->flush();
        }

        $fromResource = null;
        if ($chat->getType() === UpdateTypeEnum::Chat) {
            $fromResource = $fromArr[1];
        }

        $user = $this->chocoRepository->findUserByJid($from);
        if (null === $user) {
            $user = new UpdateUser(
                type: $chat->getType(),
                jid: $from
            );
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        $update = new Update(
            chat: $chat,
            message: new UpdateMessage(
                from: $user,
                text: $stringUTF8,
                fromResource: $fromResource
            )
        );
        $this->entityManager->persist($update);
        $this->entityManager->flush();
        $this->bus->dispatch(new UpdateHandleMessage($update->getId()));
//        $to = $fromJID;
//
//        $body = $stringUTF8;
//
//        $this->entityManager->persist(
//            object: new Job(
//                recipient: $to,
//                dialogType: $type,
//                body: $body
//            )
//        );
    }

    private function saveUnknownChild(XmlElement $child): void
    {
        $elementPayload = $this->serializer->normalize($child);

        $this->entityManager->persist(
            new UnknownUpdateElement($elementPayload)
        );
        $this->entityManager->flush();
    }
}
