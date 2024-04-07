<?php

declare(strict_types=1);

namespace App\Service\ChatCommand\Settings;

use App\DTO\ChatCommandResult;
use App\Enum\ChatConfigurationRightsEnum;
use App\Exception\ChatMemberException;
use App\Service\ChatCommand\ChatCommandData;
use App\Service\ChatCommand\ChatCommandInterface;
use App\Service\ChatCommand\ChatMemberAuthenticator;
use App\Service\JobPublisher;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('chat_command', ['regex' => "/\-(CanMute|Муты)/"])]
final readonly class MuteDisable
    implements ChatCommandInterface
{
    public function __construct(
        private ChatMemberAuthenticator $memberAuthenticator,
        private JobPublisher $jobPublisher,
    ) {}

    public function run(ChatCommandData $data): ChatCommandResult
    {
        try {
            $this->memberAuthenticator->authenticateRankPrimitive(
                $data->whoMember,
                ChatConfigurationRightsEnum::CAN_MANAGE_CHAT_CONFIGURATION->value
            );

            $data->chat->getConfiguration()->manage(muteEnabled: false);

            $text = 'Выключена возможность глушить участников';
            $this->jobPublisher->sendMessage($data->chat, $text);
        } catch (ChatMemberException $e) {
            return ChatCommandResult::fatal($e);
        }

        return ChatCommandResult::success();
    }
}
