<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\JobTypeEnum;
use App\Service\UlidService;
use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Symfony\Component\Uid\Ulid;

#[Entity]
class Job
{
    #[Id, Column(type: 'string', length: 26), GeneratedValue(strategy: 'NONE')]
    private readonly string $id;

    #[Column(nullable: true)] private ?DateTimeImmutable $handledAt = null;

    #[Column(nullable: true)] private ?DateTimeImmutable $completedAt = null;

    /** @var array<string, mixed> $resultPayload */
    #[Column] private array $resultPayload = [];

    /** @param array<string, mixed> $payload */
    public function __construct(
        #[Column] private readonly array $payload,
        #[Column] private readonly JobTypeEnum $type,
    )
    {
        $this->id = UlidService::generate();
    }

    public function getId(): string
    {
        return $this->id;
    }

    /** @return array<string, mixed> */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getType(): JobTypeEnum
    {
        return $this->type;
    }

    /** @param array<string, mixed> $payload */
    public function markAsCompleted(array $payload): void
    {
        $this->completedAt = new DateTimeImmutable();
        $this->resultPayload = $payload;
    }

    public function markAsHandled(): void
    {
        $this->handledAt = new DateTimeImmutable();
    }

    public function createdAt(): DateTimeImmutable
    {
        return Ulid::fromString($this->id)->getDateTime();
    }

    public function getHandledAt(): ?DateTimeImmutable
    {
        return $this->handledAt;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    /** @return array<string, mixed> */
    public function getResultPayload(): array
    {
        return $this->resultPayload;
    }
}
