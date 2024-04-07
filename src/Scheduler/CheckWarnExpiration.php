<?php

declare(strict_types=1);

namespace App\Scheduler;

use App\Entity\MemberWarn;
use App\Service\JobPublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsPeriodicTask(frequency: 5, schedule: 'check_warn_expiration')]
final readonly class CheckWarnExpiration
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private JobPublisher $jobPublisher,
    ) {}

    public function __invoke(): void
    {
        /** @var MemberWarn[] $warns */
        $warns = $this->entityManager->createQueryBuilder()
            ->select('w')
            ->from(MemberWarn::class, 'w')
            ->where('w.expiresAt < :time')
            ->setParameter('time', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();

        foreach ($warns as $warn) {
            $warn->expire();
            $member = $warn->getWarned();
            $chat = $member->getChat();
            $text = sprintf(
                'Предупреждение, выданное %s админом %s, %s до %s по причине %s было снято автоматически',
                $member->getUser()->getFullyQualifiedNick(),
                $warn->getCreator()->getUser()->getFullyQualifiedNick(),
                $warn->getCreatedAt()->format('Y-m-d H:i:s'),
                $warn->getExpiresAt()->format('Y-m-d H:i:s'),
                $warn->getReason()
            );
            $this->jobPublisher->sendMessage($chat, $text);
        }
    }
}
