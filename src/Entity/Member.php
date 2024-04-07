<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ChatMemberStatusEnum;
use App\Enum\MemberRankStatusEnum;
use App\Exception\ChatMemberException;
use App\Exception\ChatMemberKickedOrLeftException;
use App\Exception\ChatMemberReputationException;
use App\Service\UlidService;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use DomainException;
use Symfony\Component\Clock\Clock;

#[Entity]
class Member
{
    final public const int REPUTATION_CHANGE_QUOTA_DEFAULT = 10;

    #[Id, Column(type: 'string', length: 26), GeneratedValue(strategy: 'NONE')]
    private readonly string $id;

    #[OneToOne] private ?ChatMemberRank $rank = null;

    /** @var Collection<MemberWarn> $warns */
    #[OneToMany(targetEntity: MemberWarn::class, mappedBy: 'warned')]
    private Collection $warns;

    /** @var Collection<MemberWarn> $issuedWarnings */
    #[OneToMany(targetEntity: MemberWarn::class, mappedBy: 'creator')]
    private Collection $issuedWarnings;

    #[Column(nullable: true)]
    private ?DateTimeImmutable $sinceSpentTime = null;

    #[Column] private int $reputation;

    #[Column] private DateTimeImmutable $reputationChangeQuotaLastUpdated;

    #[Column] private int $reputationChangeQuota;

    public function __construct(
        #[ManyToOne(cascade: ['persist'])] private readonly UpdateChat $chat,
        #[ManyToOne(cascade: ['persist'])] private readonly UpdateUser $user,
        #[Column(type: 'smallint', enumType: ChatMemberStatusEnum::class)]
        private ChatMemberStatusEnum $status,
    ) {
        $this->id = UlidService::generate();
        if (!$this->status->isKicked() && !$this->status->isLeft()) {
            $this->sinceSpentTime = Clock::get()->now();
        }
        $this->warns = new ArrayCollection();
        $this->issuedWarnings = new ArrayCollection();
        $this->reputation = 0;
        $this->reputationChangeQuota = self::REPUTATION_CHANGE_QUOTA_DEFAULT;
        $this->reputationChangeQuotaLastUpdated = Clock::get()->now();
    }

    /** @throws ChatMemberKickedOrLeftException */
    public function getSinceSpentTime(): DateTimeImmutable
    {
        if (null === $this->sinceSpentTime) {
            throw new ChatMemberKickedOrLeftException();
        }

        return $this->sinceSpentTime;
    }

    /** @throws ChatMemberReputationException */
    public function promoteReputation(self $who): void
    {
        if ($who->getReputationChangeQuota() > 0) {
            $this->reputation++;
            $who->decreaseReputationQuota();
        } else {
            throw new ChatMemberReputationException('Quota exceeded');
        }
    }

    private function getReputationChangeQuota(): int
    {
        return $this->reputationChangeQuota;
    }

    private function decreaseReputationQuota(): void
    {
        $this->reputationChangeQuota--;
    }

    /** @throws ChatMemberReputationException */
    public function demoteReputation(self $who): void
    {
        if ($who->getReputationChangeQuota() > 0) {
            $this->reputation--;
            $who->decreaseReputationQuota();
        } else {
            throw new ChatMemberReputationException('Quota exceeded');
        }
    }

    public function getReputation(): int
    {
        return $this->reputation;
    }

    public function getUserFirstName(): string
    {
        return $this->user->getFullyQualifiedNick();
    }

    public function getStatus(): ChatMemberStatusEnum
    {
        return $this->status;
    }

    /**
     * @throws ChatMemberException
     */
    public function getWarnById(string $warnId): MemberWarn
    {
        foreach ($this->warns as $warn) {
            if ($warn->getId() === $warnId) {
                return $warn;
            }
        }
        throw new ChatMemberException(
            "ChocoChat member warn with id: $warnId not found"
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function initRank(ChatMemberRank $rank): void
    {
        $this->rank = $rank;
    }

    public function rankPromote(int $rankIncrement): void
    {
        $this->getRank()->promote($rankIncrement);
    }

    public function getRank(): ChatMemberRank
    {
        if (null === $this->rank) {
            throw new DomainException('Member must should be exist');
        }

        return $this->rank;
    }

    public function rankDemote(int $rankDecrement): void
    {
        $this->getRank()->demote($rankDecrement);
    }

    public function rankUpdate(MemberRankStatusEnum $rank): void
    {
        $this->getRank()->setRankValue($rank);
    }

    public function updateMemberInformation(ChatMemberStatusEnum $status): void
    {
        $this->updateStatus($status);
        $this->tryUpdateReputationChangeQuota();
    }

    private function updateStatus(ChatMemberStatusEnum $status): void
    {
        $this->status = $status;

        if ($this->status->isKicked() || $this->status->isLeft()) {
            $this->sinceSpentTime = null;
        } elseif (null === $this->sinceSpentTime) {
            $this->sinceSpentTime = Clock::get()->now();
        }
    }

    private function tryUpdateReputationChangeQuota(): void
    {
        $nowTimestamp = Clock::get()->now()->getTimestamp();

        $nextQuotaUpdateTimestamp = $this->reputationChangeQuotaLastUpdated
            ->modify('+10 hours')
            ->getTimestamp();

        if ($nowTimestamp > $nextQuotaUpdateTimestamp) {
            $this->reputationChangeQuota =
                self::REPUTATION_CHANGE_QUOTA_DEFAULT;

            $this->reputationChangeQuotaLastUpdated = Clock::get()->now();
        }
    }

    public function getChat(): UpdateChat
    {
        return $this->chat;
    }

    public function getUser(): UpdateUser
    {
        return $this->user;
    }

    public function getMarry(): ?Marry
    {
        return $this->user->getMarry();
    }

    public function isMarried(): bool
    {
        return $this->user->isMarried();
    }

    /** @return MemberWarn[] */
    public function getWarns(): array
    {
        /** @var MemberWarn[] $warns */
        $warns = $this->warns->toArray();

        return $warns;
    }

    /** @return MemberWarn[] */
    public function getIssuedWarnings(): array
    {
        /** @var MemberWarn[] $warns */
        $warns = $this->issuedWarnings->toArray();

        return $warns;
    }

    public function getReputationChangeQuotaLastUpdated(): DateTimeImmutable
    {
        return $this->reputationChangeQuotaLastUpdated;
    }
}
