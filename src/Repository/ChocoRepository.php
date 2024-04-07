<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\ChatMemberMessagesStatDTO;
use App\Entity\Marry;
use App\Entity\Member;
use App\Entity\Update;
use App\Entity\UpdateChat;
use App\Entity\UpdateUser;
use App\Exception\BaseException;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

final class ChocoRepository
    extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Update::class);
    }

    /**
     * @throws Exception
     */
    public function getMessagesCountAggregatedByChatMemberAndTimeRange(
        DateTimeImmutable $fromDate,
        DateTimeImmutable $toDate,
        Member $who
    ): int {
        $sql = <<<SQL
SELECT count(um.id)
FROM update_message um
INNER JOIN update u ON um.id = u.message_id
WHERE (um.id BETWEEN :from AND :to) AND u.chat_id = :chat_id AND um.from_id = :user_id
SQL;

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->bindValue(
            'from',
            Ulid::generate($fromDate)
        );
        $stmt->bindValue(
            'to',
            Ulid::generate($toDate)
        );
        $stmt->bindValue(
            'chat_id',
            $who->getChat()->getId()
        );
        $stmt->bindValue(
            'user_id',
            $who->getUser()->getId()
        );
        $result = $stmt->executeQuery();

        /** @var int $fetchedResult */
        $fetchedResult = $result->fetchOne();

        return $fetchedResult;
    }

    /**
     * @return ChatMemberMessagesStatDTO[]
     * @throws Exception
     * @throws BaseException
     */
    public function getMessagesStats(
        DateTimeImmutable $fromDate,
        DateTimeImmutable $toDate,
        UpdateChat $chat
    ): array {
        $sql = <<<SQL
SELECT um.from_id as choco_user_id, count(um.id) as quantity FROM update_message um
WHERE (um.id BETWEEN :from AND :to)
GROUP BY um.from_id
ORDER BY count(um.id) DESC
LIMIT 10
SQL;

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);

        $stmt->bindValue(
            'from',
            Ulid::generate($fromDate)
        );
        $stmt->bindValue(
            'to',
            Ulid::generate($toDate)
        );

        $result = $stmt->executeQuery();

        /** @var array<int, array{
         *     choco_user_id: string,
         *     quantity: int
         * }> $rawStats
         */
        $rawStats = $result->fetchAllAssociative();
        //        $rawStats = $this->getEntityManager()->createQueryBuilder()
        //            ->select("uuu.id as chocoUserId, count(m.id) as quantity")
        //            ->from("Choco:UnknownUpdateElement\UpdateMessage", 'u')
        //            ->leftJoin("u.message", 'm') // Choco/Message
        //            ->leftJoin('u.chat', 'uc') // UnknownUpdateElement/ChocoChat
        //            ->leftJoin('u.from', 'uu')
        //            ->leftJoin('uu.user', 'uuu')
        //            ->groupBy("uuu.id")
        //            ->orderBy('count(m.id)', 'DESC')
        //            ->where('m.createdAt BETWEEN :from AND :to')
        //            ->andWhere('uc.chat = :chat')
        //            ->setParameters([
        //                'from' => $fromDate,
        //                'to' => $toDate,
        //                'chat' => $chat
        //            ])
        //            ->setMaxResults(10)
        //            ->getQuery()
        //            ->getResult();

        $chocoUsersIds = [];
        foreach ($rawStats as $rawStat) {
            $chocoUsersIds[] = $rawStat['choco_user_id'];
        }

        /** @var UpdateUser[] $chocoUsers */
        $chocoUsers = $this->getEntityManager()->createQueryBuilder()
            ->select('u')
            ->from(UpdateUser::class, 'u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $chocoUsersIds)
            ->getQuery()
            ->getResult();

        if (count($chocoUsers) !== count($rawStats)) {
            throw new BaseException(
                'INCORRECT QUERY. $chocoUsers and $rawStats count MUST be equal'
            );
        }

        $stats = [];

        foreach ($rawStats as $rawStat) {
            foreach ($chocoUsers as $chocoUser) {
                if ($rawStat['choco_user_id'] === $chocoUser->getId()) {
                    $stats[] = new ChatMemberMessagesStatDTO(
                        $chocoUser,
                        $rawStat['quantity']
                    );
                }
            }
        }

        return $stats;
    }

    /** @return Marry[] */
    public function getMarriesByChat(UpdateChat $chat): array
    {
        /** @var Marry[] $result */
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select('u.marry')
            ->from(Member::class, 'm')
            ->where('m.chat = :chat')
            ->setParameter('chat', $chat)
            ->join('m.user', 'u')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findChatByJid(string $jid): ?UpdateChat
    {
        /** @var ?UpdateChat $result */
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select('uc')
            ->from(UpdateChat::class, 'uc')
            ->where('uc.jid = :jid')
            ->setParameter('jid', $jid)
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }

    public function findUserByJid(string $jid): ?UpdateUser
    {

        /** @var ?UpdateUser $result */
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select('uu')
            ->from(UpdateUser::class, 'uu')
            ->where('uu.jid = :jid')
            ->setParameter('jid', $jid)
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }

}
