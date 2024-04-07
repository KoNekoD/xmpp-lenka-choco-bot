<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\UI\Chat;
use App\DTO\UI\RosterItem;
use App\DTO\XmlElement;
use App\Entity\Job;
use App\Entity\UnknownUpdateElement;
use App\Entity\Update;
use App\Enum\JobTypeEnum;
use App\Service\JobPublisher;
use App\Service\SerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class DashboardController
    extends AbstractController
{
    public function __construct(
        private readonly JobPublisher $jobPublisher,
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerService $serializer,
    ) {}

    #[Route('/', name: 'dashboard', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $xml = $request->request->get('xml');
        if (is_string($xml)) {
            $this->jobPublisher->custom($xml);
            $this->addFlash('success', 'XML sent');
        }

        $isGetRoster = $request->request->get('GetRoster');
        if ($isGetRoster) {
            $this->jobPublisher->getRoster();
            $this->addFlash('success', 'Roster request sent');

            return $this->redirectToRoute('dashboard');
        }
        $isGetChats = $request->request->get('GetChats');
        if ($isGetChats) {
            $this->jobPublisher->getChats();
            $this->addFlash('success', 'Chats request sent');

            return $this->redirectToRoute('dashboard');
        }
        $isListSubscriptions = $request->request->get('ListSubscriptions');
        if ($isListSubscriptions) {
            $this->jobPublisher->listSubscriptions();
            $this->addFlash('success', 'Subscriptions list request sent');

            return $this->redirectToRoute('dashboard');
        }
        $isSubscribeTarget = $request->request->get('SubscribeTarget');
        if (is_string($isSubscribeTarget)) {
            $this->jobPublisher->subscribe($isSubscribeTarget);
            $this->addFlash('success', 'Subscription request sent');
        }
        $isUnsubscribeTarget = $request->request->get('UnsubscribeTarget');
        if (is_string($isUnsubscribeTarget)) {
            $this->jobPublisher->unsubscribe($isUnsubscribeTarget);
            $this->addFlash('success', 'Unsubscription request sent');
        }
        $isRemoveFromRosterTarget = $request->request->get(
            'RemoveFromRosterTarget'
        );
        if (is_string($isRemoveFromRosterTarget)) {
            $this->jobPublisher->removeFromRoster($isRemoveFromRosterTarget);
            $this->addFlash('success', 'Removal from roster request sent');
        }

        /** @var UnknownUpdateElement[] $unknownXmls */
        $unknownXmls = $this->entityManager
            ->getRepository(UnknownUpdateElement::class)
            ->createQueryBuilder('e')
            ->orderBy('e.id', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        /** @var Update[] $updates */
        $updates = $this->entityManager
            ->getRepository(Update::class)
            ->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        /** @var Job[] $jobs */
        $jobs = $this->entityManager
            ->getRepository(Job::class)
            ->createQueryBuilder('j')
            ->orderBy('j.id', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        /** @var ?Job $latestRosterJob */
        $latestRosterJob = $this->entityManager
            ->getRepository(Job::class)
            ->createQueryBuilder('j')
            ->where('j.type = :type')
            ->andWhere('j.completedAt IS NOT NULL')
            ->setParameter('type', JobTypeEnum::GetRoster)
            ->orderBy('j.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        /** @var RosterItem[] $latestRosterItems */
        $latestRosterItems = [];
        if ($latestRosterJob !== null) {
            $resultPayload = $latestRosterJob->getResultPayload();

            $xmlElement = $this->serializer->denormalize(
                $resultPayload,
                XmlElement::class
            );
            $query = $xmlElement->findFirstChildWithName('query');

            foreach ($query?->children ?? [] as $item) {
                $rosterItem = $this->serializer->denormalize(
                    $item->attributes,
                    RosterItem::class
                );
                $latestRosterItems[] = $rosterItem;
            }
        }

        /** @var ?Job $latestChatsJob */
        $latestChatsJob = $this->entityManager
            ->getRepository(Job::class)
            ->createQueryBuilder('j')
            ->where('j.type = :type')
            ->andWhere('j.completedAt IS NOT NULL')
            ->setParameter('type', JobTypeEnum::GetChats)
            ->orderBy('j.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        /** @var Chat[] $latestChatsItems */
        $latestChatsItems = [];
        if ($latestChatsJob !== null) {
            $resultPayload = $latestChatsJob->getResultPayload();

            $xmlElement = $this->serializer->denormalize(
                $resultPayload,
                XmlElement::class
            );
            $storage = $xmlElement->findFirstChildWithName('query')
                ?->findFirstChildWithName('storage');

            foreach ($storage?->children ?? [] as $chat) {
                $chatItem = $this->serializer->denormalize(
                    $chat->attributes,
                    Chat::class
                );
                $latestChatsItems[] = $chatItem;
            }
        }

        return $this->render(
            'dashboard.index.html.twig',
            [
                'unknownXmls' => $unknownXmls,
                'updates' => $updates,
                'jobs' => $jobs,
                'latestRosterJob' => $latestRosterJob,
                'latestRosterItems' => $latestRosterItems,

                'latestChatsJob' => $latestChatsJob,
                'latestChatsItems' => $latestChatsItems,
            ]
        );
    }
}
