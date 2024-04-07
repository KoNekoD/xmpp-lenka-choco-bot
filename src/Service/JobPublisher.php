<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Job\Custom;
use App\DTO\Job\MuteUser;
use App\DTO\Job\SendMessage;
use App\DTO\Job\Subscribe;
use App\Entity\Job;
use App\Entity\Member;
use App\Entity\UpdateChat;
use App\Enum\JobTypeEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class JobPublisher
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerService $serializerService,
        #[Autowire(param: 'xmpp.host')] private string $host,
        #[Autowire(param: 'xmpp.username')] private string $username,
    ) {}

    public function sendMessage(UpdateChat $chat, string $body): void
    {
        $dto = new SendMessage(
            recipient: $chat->getJid(),
            dialogType: $chat->getType()->value,
            body: $body
        );

        $payload = $this->serializerService->normalize($dto);

        $job = new Job(
            payload: $payload,
            type: JobTypeEnum::SendMessage
        );

        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    public function mute(Member $member): void
    {
        $dto = new MuteUser(
            to: $member->getChat()->getJid(),
            nick: $member->getUser()->getFullyQualifiedNick()
        );

        $payload = $this->serializerService->normalize($dto);

        $job = new Job(
            payload: $payload,
            type: JobTypeEnum::MuteUser
        );

        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    public function custom(string $xml): void
    {
        $dto = new Custom(
            xml: $xml
        );

        $payload = $this->serializerService->normalize($dto);

        $job = new Job(
            payload: $payload,
            type: JobTypeEnum::Custom
        );

        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    public function getRoster(): void
    {
        $job = new Job(
            payload: [],
            type: JobTypeEnum::GetRoster
        );

        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    public function listSubscriptions(): void
    {
        $dto = new Custom(
            xml: XmppRequest::listSubscriptions()
        );

        $payload = $this->serializerService->normalize($dto);

        $job = new Job(
            payload: $payload,
            type: JobTypeEnum::Custom
        );

        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    public function subscribe(string $jid): void
    {
        $dto = new Subscribe(
            jid: $jid
        );

        $payload = $this->serializerService->normalize($dto);

        $job = new Job(
            payload: $payload,
            type: JobTypeEnum::Subscribe
        );

        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    public function unsubscribe(string $from): void
    {
        $dto = new Custom(
            xml: XmppRequest::unsubscribe(
                username: $this->username,
                host: $this->host,
                from: $from
            )
        );

        $payload = $this->serializerService->normalize($dto);

        $job = new Job(
            payload: $payload,
            type: JobTypeEnum::Custom
        );

        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    public function removeFromRoster(string $jid): void
    {
        $dto = new Custom(
            xml: XmppRequest::removeFromRoster(
                jid: $jid
            )
        );

        $payload = $this->serializerService->normalize($dto);

        $job = new Job(
            payload: $payload,
            type: JobTypeEnum::Custom
        );

        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    public function ping(): void
    {
        $job = new Job(
            payload: [],
            type: JobTypeEnum::Ping
        );

        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    public function getChats(): void
    {
        $job = new Job(
            payload: [],
            type: JobTypeEnum::GetChats
        );

        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }
}
