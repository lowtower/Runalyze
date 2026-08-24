<?php

declare(strict_types=1);

namespace Runalyze\Profile\Notifications;

use Runalyze\Common\Enum\AbstractEnum;

class MessageTypeProfile extends AbstractEnum
{
    /**
     * @var int
     */
    public const TEMPLATE_BASED_MESSAGE = 1;

    /**
     * @var int
     */
    public const POSTER_GENERATED_MESSAGE = 2;

    /**
     * @var int
     */
    public const BACKUP_READY_MESSAGE = 3;
}
