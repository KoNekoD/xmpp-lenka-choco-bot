<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\MemberRankStatusEnum;
use App\Service\UlidService;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
class ChatMemberRank
{
    #[Id, Column(type: 'string', length: 26), GeneratedValue(strategy: 'NONE')]
    private readonly string $id;

    public function __construct(
        #[Column(type: 'smallint')]
        private int $rank = 0,
    ) {
        $this->id = UlidService::generate();
    }

    public function promote(int $rankIncrement): void
    {
        $rank = $this->rank + $rankIncrement;
        $newRank = MemberRankStatusEnum::fromInteger($rank);

        $this->rank = $newRank->value;
    }

    public function demote(int $rankDecrement): void
    {
        $rank = $this->rank - $rankDecrement;
        $newRank = MemberRankStatusEnum::fromInteger($rank);

        $this->rank = $newRank->value;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getRankValue(): MemberRankStatusEnum
    {
        return MemberRankStatusEnum::fromInteger($this->rank);
    }

    public function getRankValuePrimitive(): int
    {
        return $this->rank;
    }

    public function setRankValue(MemberRankStatusEnum $rank): void
    {
        $this->rank = $rank->value;
    }
}
