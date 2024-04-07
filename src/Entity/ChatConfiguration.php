<?php

declare(strict_types=1);

namespace App\Entity;

use App\Service\UlidService;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Entity]
class ChatConfiguration
{
    #[Id, Column(type: 'string', length: 26), GeneratedValue(strategy: 'NONE')]
    private readonly string $id;

    #[Column(type: 'boolean')]
    private bool $newbieNotify = true;

    #[Column(type: 'boolean')]
    private bool $muteEnabled = false;

    public function __construct()
    {
        $this->id = UlidService::generate();
    }

    public function manage(
        ?bool $newbieNotify = null,
        ?bool $muteEnabled = null
    ): void {
        if (null !== $newbieNotify) {
            $this->newbieNotify = $newbieNotify;
        }

        if (null !== $muteEnabled) {
            $this->muteEnabled = $muteEnabled;
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function isNewbieNotify(): bool
    {
        return $this->newbieNotify;
    }

    public function isMuteEnabled(): bool
    {
        return $this->muteEnabled;
    }
}
