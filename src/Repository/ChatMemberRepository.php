<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Member;
use App\Entity\Update;
use App\Entity\UpdateChat;
use App\Entity\UpdateUser;
use App\Enum\ChatMemberStatusEnum;
use App\Enum\MemberRankStatusEnum;
use App\Exception\ChatMemberException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

final class ChatMemberRepository
    extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Member::class);
    }

    /**
     * @throws NonUniqueResultException
     * @throws ChatMemberException
     */
    public function findChatMemberByFirstMentionOrUsername(
        Update $update,
        string $username
    ): Member
    {
        try {
            // @TODO Optimize this
            $updateChat = $update->getChat();

            $entities = $update->getMessage()->getEntities();
            if ($entities !== []) {
                $user = $this->getEntityManager()->createQueryBuilder()
                    ->select('u')
                    ->from(UpdateUser::class, 'u')
                    ->where('u.u.fullyQualifiedNick IN (:usernames)')
                    ->setParameter('usernames', $entities)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();

                if ($user !== null) {
                    /** @var Member $member */
                    $member = $this->getEntityManager()->createQueryBuilder()
                        ->select('m')
                        ->from(Member::class, 'm')
                        ->where('m.user = :user')
                        ->andWhere('m.chat = :chat')
                        ->setParameter('chat', $updateChat)
                        ->setParameter('user', $user)
                        ->getQuery()
                        ->getSingleResult();

                    return $member;
                }
            }

            /** @var Member $member */
            $member = $this->getEntityManager()->createQueryBuilder()
                ->select('m')
                ->from(Member::class, 'm')
                ->where('m.chat = :chat')
                ->setParameter('chat', $updateChat)
                ->innerJoin('m.user', 'u')
                ->andWhere('u.fullyQualifiedNick = :username')
                ->setParameter('username', $username)
                ->orderBy('u.id', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleResult();

            return $member;
        } catch (NoResultException) {
            throw new ChatMemberException(
                'Не удалось найти указанного участника в данном чате. Пусть он напишет хоть одно сообщение'
            );
        }
    }

    /** @return Member[] */
    public function getChatMembersWithPrivileges(UpdateChat $chat): array
    {
        /** @var Member[] $members */
        $members = $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from(Member::class, 'm')
            ->andWhere('m.chat = :chat')
            ->setParameter('chat', $chat)
            ->innerJoin('m.rank', 'r')
            ->andWhere('r.rank <> :mr')
            ->setParameter('mr', MemberRankStatusEnum::Member->value)
            ->getQuery()
            ->getResult();

        return $members;
    }

    /**
     * @param string[] $usernameList
     * @return Member[]
     */
    public function findChatMembersByUsernames(
        Update $update,
        array $usernameList
    ): array {
        /** @var Member[] $result */
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from(Member::class, 'm')
            ->innerJoin('m.user', 'u')
            ->where('m.chat = :chat')
            ->setParameter('chat', $update->getChat())
            ->andWhere('u.fullyQualifiedNick IN (:usernames)')
            ->setParameter('usernames', $usernameList)
            ->orderBy('uu.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findOrCreateOne(UpdateUser $user, UpdateChat $chat): Member
    {
        /** @var Member $member */
        $member = $this->getEntityManager()->createQueryBuilder()
            ->select('m')
            ->from(Member::class, 'm')
            ->where('m.user = :user')
            ->andWhere('m.chat = :chat')
            ->setParameter('user', $user)
            ->setParameter('chat', $chat)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $member) {
            $member = new Member(
                chat: $chat,
                user: $user,
                status: ChatMemberStatusEnum::Member
            );
            $this->getEntityManager()->persist($member);
            $this->getEntityManager()->flush();
        }

        return $member;
    }
}
