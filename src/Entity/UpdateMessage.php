<?php

declare(strict_types=1);

namespace App\Entity;

use App\Service\UlidService;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
class UpdateMessage
{
    #[Id, Column(type: 'string', length: 26), GeneratedValue(strategy: 'NONE')]
    private readonly string $id;

    public function __construct(
        #[ManyToOne(cascade: ['persist'])]
        private readonly UpdateUser $from,
        #[Column(type: 'text')]
        private readonly string $text,
        #[Column(nullable: true)]
        private readonly ?string $fromResource,
    ) {
        $this->id = UlidService::generate();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFrom(): UpdateUser
    {
        return $this->from;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getFromResource(): ?string
    {
        return $this->fromResource;
    }

    /** @return string[] */
    public function getEntities(): array
    {
        return explode(' ', $this->text);
    }

}
