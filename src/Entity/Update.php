<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\UpdateHandleStatusEnum;
use App\Service\UlidService;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
class Update
{
    final public const int MAX_UPDATE_HANDLE_RETRIES = 5;

    #[Id, Column(type: 'string', length: 26), GeneratedValue(strategy: 'NONE')]
    private readonly string $id;

    #[Column(type: 'smallint', enumType: UpdateHandleStatusEnum::class)]
    private UpdateHandleStatusEnum $handleStatus;

    #[Column(type: 'smallint')]
    private int $handleRetriesCount;

    public function __construct(
        #[ManyToOne(cascade: ['persist'])]
        private readonly UpdateChat $chat,
        #[ManyToOne(cascade: ['persist'])]
        private readonly UpdateMessage $message,
    ) {
        $this->id = UlidService::generate();

        $this->handleStatus = UpdateHandleStatusEnum::IN_PROGRESS;
        $this->handleRetriesCount = 1;
    }

    public function handleFailed(): void
    {
        if ($this->handleRetriesCount >= self::MAX_UPDATE_HANDLE_RETRIES) {
            // Limit exceeded
            $this->handleStatus = UpdateHandleStatusEnum::REJECTED;
        } else {
            $this->handleStatus = UpdateHandleStatusEnum::FAILED;
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getChat(): UpdateChat
    {
        return $this->chat;
    }

    public function getMessage(): UpdateMessage
    {
        return $this->message;
    }

    public function changeStatus(UpdateHandleStatusEnum $status): void
    {
        $this->handleStatus = $status;
    }

    public function getHandleStatus(): UpdateHandleStatusEnum
    {
        return $this->handleStatus;
    }

    public function getHandleRetriesCount(): int
    {
        return $this->handleRetriesCount;
    }
}
