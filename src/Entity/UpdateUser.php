<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\UpdateTypeEnum;
use App\Enum\UserMarryStatusEnum;
use App\Exception\MarryException;
use App\Service\UlidService;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use LogicException;
use Symfony\Component\Serializer\Attribute\Ignore;

#[Entity]
class UpdateUser
{
    #[Id, Column(type: 'string', length: 26), GeneratedValue(strategy: 'NONE')]
    private readonly string $id;

    #[ManyToOne(targetEntity: Marry::class, inversedBy: 'participants')]
    private ?Marry $marry = null;

    #[Column(nullable: true, enumType: UserMarryStatusEnum::class)]
    private ?UserMarryStatusEnum $marryStatus = null;

    public function __construct(
        #[Column(enumType: UpdateTypeEnum::class)]
        private readonly UpdateTypeEnum $type,
        #[Column(unique: true)]
        private readonly string $jid,
    ) {
        $this->id = UlidService::generate();
    }

    public function isMarried(): bool
    {
        return (
            null !== $this->marry
            &&
            $this->isMarryStatusAccepted()
            &&
            $this->marry->isMarryGeneralStatusMarried()
        );
    }

    public function isMarryStatusAccepted(): bool
    {
        return UserMarryStatusEnum::ACCEPTED === $this->marryStatus;
    }

    /** @throws MarryException */
    public function trySendMarryRequest(Marry $marry): void
    {
        if ($this->isMarried()) {
            throw new MarryException(
                sprintf(
                    'ChocoUser %s already married',
                    $this->getFullyQualifiedNick()
                )
            );
        }
        $this->marry = $marry;
        $this->marryStatus = UserMarryStatusEnum::NOT_ACCEPTED;
    }

    public function divorceMarry(): void
    {
        $this->marry = null;
        $this->marryStatus = null;
    }

    public function getMarry(): ?Marry
    {
        return $this->marry;
    }

    #[Ignore]
    public function getMarryOrThrow(): Marry
    {
        $marry = $this->marry;

        if (null === $marry) {
            throw new LogicException('Should not happen');
        }

        return $marry;
    }

    public function getMarryStatus(): ?UserMarryStatusEnum
    {
        return $this->marryStatus;
    }

    public function acceptMarry(): void
    {
        $this->marryStatus = UserMarryStatusEnum::ACCEPTED;
    }

    public function isMarryParticipantStatusAccepted(): bool
    {
        return UserMarryStatusEnum::ACCEPTED === $this->marryStatus;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): UpdateTypeEnum
    {
        return $this->type;
    }

    public function getJid(): ?string
    {
        return $this->jid;
    }

    public function getFullyQualifiedNick(): string
    {
        return $this->jid;
    }
}
