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

#[AutoconfigureTag('chat_command', ['regex' => "/Браки/"])]
final readonly class MarryList
    implements ChatCommandInterface
{
    public function __construct(
        private JobPublisher $jobPublisher,
        private ChocoRepository $chocoRepository,
    ) {}

    public function run(ChatCommandData $data): ChatCommandResult
    {
        $now = Clock::get()->now();
        $marries = $this->chocoRepository->getMarriesByChat($data->chat);

        $text = "💍 БРАКИ ЭТОЙ БЕСЕДЫ\n";
        foreach ($marries as $i => $marry) {
            if (!$marry->isMarryGeneralStatusMarried()) {
                continue;
            }

            $intervalString = ' ';

            $spentInterval = $marry->getCreatedAt()->diff($now);
            if ($spentInterval->y !== 0) {
                $intervalString .= "$spentInterval->y лет ";
            }
            if ($spentInterval->m !== 0) {
                $intervalString .= "$spentInterval->m месяцев ";
            }
            //                if ($spentInterval->d) {
            $intervalString .= "$spentInterval->d дней ";
            //                }

            $text .= sprintf(
                "%d. %s (%s)\n",
                $i,
                $marry->getParticipantsFirstNames(),
                $intervalString
            );
        }

        $this->jobPublisher->sendMessage($data->chat, $text);

        return ChatCommandResult::success();
    }
}
