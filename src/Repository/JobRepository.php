<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Job;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

class JobRepository
    extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Job::class);
    }

    /** @return Job[] */
    public function getWork(): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.handledAt IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function find(
        mixed $id,
        int|LockMode|null $lockMode = null,
        ?int $lockVersion = null
    ): ?Job {
        /** @var ?Job $job */
        $job = parent::find(
            $id,
            $lockMode,
            $lockVersion
        );

        return $job;
    }
}
