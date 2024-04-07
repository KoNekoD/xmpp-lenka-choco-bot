<?php

declare(strict_types=1);

namespace App\Scheduler;

use App\DTO\Job\Custom;
use App\DTO\Job\MuteUser;
use App\DTO\Job\SendMessage;
use App\DTO\Job\Subscribe;
use App\DTO\XmlElement;
use App\Entity\Job;
use App\Enum\JobTypeEnum;
use App\Message\NewXmlElementMessage;
use App\Repository\JobRepository;
use App\Service\JabberClient;
use App\Service\JobPublisher;
use App\Service\SerializerService;
use App\Service\XmppRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleSignalEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsPeriodicTask(frequency: 5, schedule: 'xmpp_worker')]
final class XmppWorker
    implements EventSubscriberInterface
{
    public const int PING_DELAY_SECONDS = 30;
    private bool $stopNeeded = false;

    public function __construct(
        private readonly JabberClient $client,
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerService $serializer,
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $bus,
        private readonly JobPublisher $jobPublisher,
        private readonly JobRepository $jobRepository,
        #[Autowire(param: 'xmpp.username')] private readonly string $username,
    ) {}

    public function __invoke(): void
    {
        $this->client->reconnect('worker');
        $timeMark = time();

        $stopTime = Clock::get()->now()->modify('+10 hours');
        while ($stopTime > Clock::get()->now() && !$this->stopNeeded) {
            if ($timeMark + self::PING_DELAY_SECONDS < time()) {
                $this->jobPublisher->ping();
                $timeMark = time();
            }

            $jobs = $this->jobRepository->getWork();
            if (empty($jobs)) {
                sleep(1);
                continue;
            }

            foreach ($jobs as $job) {
                match ($job->getType()) {
                    JobTypeEnum::Ping => $this->ping($job),
                    JobTypeEnum::SendMessage => $this->sendMessage($job),
                    JobTypeEnum::MuteUser => $this->muteUser($job),
                    JobTypeEnum::Custom => $this->custom($job),
                    JobTypeEnum::GetRoster => $this->getRoster($job),
                    JobTypeEnum::GetChats => $this->getChats($job),
                    JobTypeEnum::Subscribe => $this->subscribe($job),
                };
                $job->markAsHandled();
            }

            $this->entityManager->flush();

            $xml = $this->client->receive();
            if ($xml === '') {
                continue;
            }

            $this->bus->dispatch(
                new NewXmlElementMessage(
                    XmlElement::fromString(
                        "<?xml version='1.0'?><root>$xml</root>"
                    )
                )
            );

            sleep(1);
        }

        $result = $this->client->disconnect();
        $this->logger->warning("received on disconnect: $result");
    }

    private function sendMessage(Job $job): void
    {
        $data = $this->serializer->denormalize(
            $job->getPayload(),
            SendMessage::class
        );

        $this->client->send(
            xml: XmppRequest::message(
                body: $data->body,
                to: $data->recipient,
                type: $data->dialogType
            ),
            noWaitAnswer: true
        );
    }

    private function muteUser(Job $job): void
    {
        $data = $this->serializer->denormalize(
            $job->getPayload(),
            MuteUser::class
        );

        $this->client->send(
            xml: XmppRequest::mute(
                to: $data->to,
                nick: $data->nick
            ),
            noWaitAnswer: true
        );
    }

    private function custom(Job $job): void
    {
        $data = $this->serializer->denormalize(
            $job->getPayload(),
            Custom::class
        );

        $this->client->send(
            xml: $data->xml,
            noWaitAnswer: true
        );
    }

    public function handleSignal(ConsoleSignalEvent $event): void
    {
        $rec = $event->getInput()->getArguments()['receivers'] ?? null;
        $key = 'scheduler_xmpp_worker';
        if (!is_array($rec) || empty($rec) || $rec[0] !== $key) {
            return;
        }

        $this->logger->warning('Signal: '.$event->getHandlingSignal());

        $this->client->disconnect();

        $this->stopNeeded = true;
        $event->abortExit();
        $event->stopPropagation();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::SIGNAL => 'handleSignal',
        ];
    }

    private function ping(Job $job): void
    {
        $this->client->send(XmppRequest::ping($job), true);
    }

    private function getRoster(Job $job): void
    {
        $this->client->send(
            xml: XmppRequest::getRoster($job),
            noWaitAnswer: true
        );
    }

    private function getChats(Job $job): void
    {
        $this->client->send(
            xml: XmppRequest::getChats($job),
            noWaitAnswer: true
        );
    }

    private function subscribe(Job $job): void
    {
        $data = $this->serializer->denormalize(
            $job->getPayload(),
            Subscribe::class
        );

        $this->client->send(
            xml: XmppRequest::subscribe(
                job: $job,
                jid: $data->jid,
                username: $this->username
            ),
            noWaitAnswer: true
        );
    }
}
