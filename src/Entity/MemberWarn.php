<?php

declare(strict_types=1);

namespace App\Entity;

use App\Service\UlidService;
use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Symfony\Component\Uid\Ulid;

#[Entity]
class MemberWarn
{
    #[Id, Column(type: 'string', length: 26), GeneratedValue(strategy: 'NONE')]
    private readonly string $id;

    #[Column] private bool $expired = false;

    public function __construct(
        #[ManyToOne(targetEntity: Member::class, inversedBy: 'warns')]
        private readonly Member $warned,
        #[ManyToOne(targetEntity: Member::class, inversedBy: 'issuedWarnings')]
        private readonly Member $creator,
        #[ManyToOne(targetEntity: UpdateChat::class, inversedBy: 'warns')]
        private readonly UpdateChat $chat,
        #[Column] private readonly string $reason,
        #[Column] private readonly DateTimeImmutable $expiresAt,
    ) {
        $this->id = UlidService::generate();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return Ulid::fromString($this->id)->getDateTime();
    }

    public function isExpired(): bool
    {
        return $this->expired;
    }

    public function expire(): void
    {
        $this->expired = true;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWarned(): Member
    {
        return $this->warned;
    }

    public function getWarnedFirstname(): string
    {
        return $this->warned->getUser()->getFullyQualifiedNick();
    }

    public function getCreator(): Member
    {
        return $this->creator;
    }

    public function getCreatorFirstname(): string
    {
        return $this->creator->getUser()->getFullyQualifiedNick();
    }

    public function getChat(): UpdateChat
    {
        return $this->chat;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
