<?php

declare(strict_types=1);

namespace App\Service\ChatCommand;

use App\Entity\Member;
use App\Exception\ChatMemberKickedOrLeftException;
use App\Repository\ChocoRepository;
use Symfony\Component\Clock\Clock;

final readonly class ChatMemberTelemetryProvider
{
    public function __construct(
        private ChocoRepository $chocoRepository
    ) {}

    /**
     * @throws ChatMemberKickedOrLeftException
     */
    public function produce(
        Member $target,
        string $targetMentionString,
        string $targetLinkString
    ): string {
        $now = Clock::get()->now();
        $sinceSpentDateTime = $target->getSinceSpentTime();
        $spentInterval = $sinceSpentDateTime->diff($now);

        $intervalString = ' ';

        if ($spentInterval->y !== 0) {
            $intervalString .= "$spentInterval->y лет ";
        }
        if ($spentInterval->m !== 0) {
            $intervalString .= "$spentInterval->m месяцев ";
        }
        //                if ($spentInterval->d) {
        $intervalString .= "$spentInterval->d дней ";
        //                }

        $perDay = $this->chocoRepository
            ->getMessagesCountAggregatedByChatMemberAndTimeRange(
                $now->modify('-1 day'),
                $now,
                $target
            );

        $perWeek = $this->chocoRepository
            ->getMessagesCountAggregatedByChatMemberAndTimeRange(
                $now->modify('-1 week'),
                $now,
                $target
            );

        $perMonth = $this->chocoRepository
            ->getMessagesCountAggregatedByChatMemberAndTimeRange(
                $now->modify('-1 month'),
                $now,
                $target
            );

        $perAll = $this->chocoRepository
            ->getMessagesCountAggregatedByChatMemberAndTimeRange(
                $sinceSpentDateTime,
                $now,
                $target
            );

        $chatMemberReputation = $target->getReputation();

        $sample = (
            "👤 Это пользователь %s (%s)\n".
            "▫️ [0] Уровень силы: НЕ_РЕАЛИЗОВАНО!!!\n".
            "Репутация: ✨ НЕ_РЕАЛИЗОВАНО | ➕ %s\n".
            "Первое появление: %s (%s)\n".
            'Актив (день|неделя|месяц|всего): %d | %d | %d | %d'
        );

        return sprintf(
            $sample,
            $targetMentionString,
            $targetLinkString,
            // Репутация: НЕ_РЕАЛИЗОВАНО | ➕ %s
            $chatMemberReputation,
            // Первое появление: %s (%s)
            $sinceSpentDateTime->format('d.m.Y'),
            $intervalString,
            // Актив (день|неделя|месяц|всего): %d | %d | %d | %d
            $perDay,
            $perWeek,
            $perMonth,
            $perAll
        );
    }
}
