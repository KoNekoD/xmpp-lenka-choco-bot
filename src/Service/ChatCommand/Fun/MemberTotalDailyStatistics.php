<?php

declare(strict_types=1);

namespace App\Service\ChatCommand\Fun;

use App\DTO\ChatCommandResult;
use App\Repository\ChocoRepository;
use App\Service\ChatCommand\ChatCommandData;
use App\Service\ChatCommand\ChatCommandInterface;
use App\Service\JobPublisher;
use Symfony\Component\Clock\Clock;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('chat_command', ['regex' => "/^Стата$/"])]
final readonly class MemberTotalDailyStatistics
    implements ChatCommandInterface
{
    public function __construct(
        private ChocoRepository $chocoRepository,
        private JobPublisher $jobPublisher,
    ) {}

    public function run(ChatCommandData $data): ChatCommandResult
    {
        $now = Clock::get()->now();
        $stats = $this->chocoRepository->getMessagesStats(
            fromDate: $now->modify('-1 day'),
            toDate: $now,
            chat: $data->chat
        );

        $result = '📊 СТАТИСТИКА ПО СООБЩЕНИЯМ ЗА СУТКИ';
        foreach ($stats as $stat) {
            $result .= sprintf(
                "\n %s - %s",
                $stat->user->getFullyQualifiedNick(),
                $stat->count
            );
        }

        $this->jobPublisher->sendMessage($data->chat, $result);

        return ChatCommandResult::success();
    }
}
