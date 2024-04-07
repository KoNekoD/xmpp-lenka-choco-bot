<?php

declare(strict_types=1);

namespace App\Entity;

use App\Service\UlidService;
use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Symfony\Component\Uid\Ulid;

#[Entity]
class UnknownUpdateElement
{
    #[Id, Column(type: 'string', length: 26), GeneratedValue(strategy: 'NONE')]
    private string $id;

    /** @param array<int|string, mixed> $payload */
    public function __construct(#[Column] private array $payload)
    {
        $this->id = UlidService::generate();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return Ulid::fromString($this->id)->getDateTime();
    }

    public function getId(): string
    {
        return $this->id;
    }

    /** @return array<int|string, mixed> */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /** @param array<int|string, mixed> $payload */
    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }
}
