<?php

declare(strict_types=1);

namespace App\Service\ChatCommand;

use App\Entity\Member;
use App\Enum\ChatMemberStatusEnum;
use App\Enum\MemberRankStatusEnum;
use App\Exception\ChatMemberException;

final readonly class ChatMemberAuthenticator
{
    public static function getChatMemberPrefix(
        MemberRankStatusEnum $rank
    ): string {
        return match ($rank) {
            MemberRankStatusEnum::Member => '🤡',
            MemberRankStatusEnum::JuniorModerator => '⚡️',
            MemberRankStatusEnum::SeniorModerator => '🥉',
            MemberRankStatusEnum::JuniorAdministrator => '🥈',
            MemberRankStatusEnum::SeniorAdministrator => '🥇',
            MemberRankStatusEnum::Creator => '💎',
        };
    }

    /** @throws ChatMemberException */
    public function authenticateRank(
        Member $who,
        MemberRankStatusEnum $requiredAccessRank,
        Member $target,
    ): void {
        $targetStatus = $target->getStatus();

        if (ChatMemberStatusEnum::Left === $targetStatus) {
            throw new ChatMemberException(
                'Выбранный участник покинул этот чат'
            );
        }

        if (ChatMemberStatusEnum::Kicked === $targetStatus) {
            throw new ChatMemberException(
                'Выбранный участник был кикнут из чата'
            );
        }

        if (ChatMemberStatusEnum::Administrator === $targetStatus) {
            throw new ChatMemberException(
                'Выбранный участник это администратор'
            );
        }

        if (ChatMemberStatusEnum::Creator === $targetStatus) {
            throw new ChatMemberException(
                'Выбранный участник это создатель чата'
            );
        }

        if (
            $who->getRank()
                ->getRankValue()->value <= $requiredAccessRank->value
        ) {
            throw new ChatMemberException(
                'Данное действие запрещено для вашего ранга. Ранг цели выше или равен вашему'
            );
        }
    }

    /** @throws ChatMemberException */
    public function authenticateRankPrimitive(
        Member $who,
        int $requiredAccessRank
    ): void {
        if ($who->getRank()->getRankValue()->value <= $requiredAccessRank) {
            throw new ChatMemberException(
                'Данное действие запрещено для вашего ранга. Ранг цели выше или равен вашему'
            );
        }
    }
}
