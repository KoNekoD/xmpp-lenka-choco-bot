<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\MarryStatusEnum;
use App\Exception\MarryException;
use App\Service\UlidService;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Symfony\Component\Uid\Ulid;

#[Entity]
class Marry
{
    #[Id, Column(type: 'string', length: 26), GeneratedValue(strategy: 'NONE')]
    private readonly string $id;

    #[Column(enumType: MarryStatusEnum::class)]
    private MarryStatusEnum $marryGeneralStatus;

    /** @var Collection<int, UpdateUser> */
    #[OneToMany(targetEntity: UpdateUser::class, mappedBy: 'marry')]
    private Collection $participants;

    public function __construct()
    {
        $this->id = UlidService::generate();
        $this->marryGeneralStatus = MarryStatusEnum::MARRY_REQUEST;
        $this->participants = new ArrayCollection();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return Ulid::fromString($this->id)->getDateTime();
    }

    public function tryAcceptGeneralMarry(): bool
    {
        foreach ($this->getParticipants() as $participant) {
            if (!$participant->isMarryParticipantStatusAccepted()) {
                return false;
            }
        }

        $this->marryGeneralStatus = MarryStatusEnum::MARRIED;

        return true;
    }


    /** @return UpdateUser[] */
    public function getParticipants(): array
    {
        return $this->participants->toArray();
    }

    public function getMarryGeneralStatus(): MarryStatusEnum
    {
        return $this->marryGeneralStatus;
    }

    public function isMarryGeneralStatusMarried(): bool
    {
        return MarryStatusEnum::MARRIED === $this->marryGeneralStatus;
    }

    /** @throws MarryException */
    public function addParticipant(UpdateUser $participant): void
    {
        $this->participants[] = $participant;
        $participant->trySendMarryRequest($this);
    }

    public function divorce(): void
    {
        foreach ($this->participants as $participant) {
            $participant->divorceMarry();
            $this->removeParticipant($participant);
        }

        $this->marryGeneralStatus = MarryStatusEnum::DIVORCE;
    }

    public function removeParticipant(UpdateUser $participant): void
    {
        foreach ($this->participants as $marriedParticipant) {
            if ($marriedParticipant->getId() === $participant->getId()) {
                $this->participants->removeElement($marriedParticipant);
            }
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getParticipantsFirstNames(
        string $separator = ' + '
    ): string {
        $participantFirstNames = [];
        foreach ($this->participants as $participant) {
            $participantFirstNames[] = $participant->getFullyQualifiedNick();
        }

        return implode($separator, $participantFirstNames);
    }
}
