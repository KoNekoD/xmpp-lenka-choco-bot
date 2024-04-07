<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\UpdateTypeEnum;
use App\Service\UlidService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;

#[Entity]
class UpdateChat
{
    #[Id, Column(type: 'string', length: 26), GeneratedValue(strategy: 'NONE')]
    private readonly string $id;

    /** @var Collection<MemberWarn> $warns */
    #[OneToMany(targetEntity: MemberWarn::class, mappedBy: 'chat')]
    private Collection $warns;

    #[OneToOne(targetEntity: ChatConfiguration::class, cascade: ['persist'])]
    private ?ChatConfiguration $configuration = null;

    public function __construct(
        #[Column(enumType: UpdateTypeEnum::class)]
        private readonly UpdateTypeEnum $type,

        #[Column(unique: true)] private readonly string $jid,
        #[Column(nullable: true)] private readonly ?string $title,
    ) {
        $this->id = UlidService::generate();
        $this->warns = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): UpdateTypeEnum
    {
        return $this->type;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getJid(): string
    {
        return $this->jid;
    }

    /** @return MemberWarn[] */
    public function getLastFiveWarns(): array
    {
        /** @var MemberWarn[] $warns */
        $warns = array_reverse($this->warns->toArray());

        return array_slice($warns, 0, 5);
    }

    /** @return MemberWarn[] */
    public function getWarnsByWarnedMember(Member $member): array
    {
        /** @var MemberWarn[] $warns */
        $warns = $this->warns->toArray();

        return array_filter(
            $warns,
            static fn(MemberWarn $warn) => (
                $warn->getWarned()->getId() === $member->getId()
            )
        );
    }

    public function getDefaultMuteTimeInSeconds(): int
    {
        return 60 * 60; // 1 hour @TODO Remove hardcode
    }

    public function getDefaultWarnCount(): int
    {
        return 3; // @TODO Remove hardcode
    }

    public function getConfiguration(): ChatConfiguration
    {
        if (null === $this->configuration) {
            $this->configuration = new ChatConfiguration();
        }

        return $this->configuration;
    }
}
