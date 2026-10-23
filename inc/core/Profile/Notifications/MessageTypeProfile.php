<?php

declare(strict_types=1);

namespace Runalyze\Profile\Notifications;

use Runalyze\Common\Enum\AbstractEnum;

class MessageTypeProfile extends AbstractEnum
{
    public const int TEMPLATE_BASED_MESSAGE = 1;

    public const int POSTER_GENERATED_MESSAGE = 2;

    public const int BACKUP_READY_MESSAGE = 3;
}
