<?php

declare(strict_types=1);

namespace App\Enum;

final class ChatConfigurationRightsEnum
{
    // More than SeniorAdministrator can use this
    final public const MemberRankStatusEnum CAN_MANAGE_CHAT_CONFIGURATION = MemberRankStatusEnum::SeniorAdministrator;

    // More than JuniorModerator can use this
    final public const MemberRankStatusEnum CAN_MUTE = MemberRankStatusEnum::JuniorModerator;
}
