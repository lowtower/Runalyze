<?php

namespace Runalyze\Bundle\CoreBundle\Component\Notifications;

use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\BackupReadyMessage;
use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\MessageInterface;
use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\PosterGeneratedMessage;
use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\TemplateBasedMessage;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Runalyze\Profile\Notifications\MessageTypeProfile;

class MessageFactory
{
    /**
     * @return MessageInterface
     */
    public function getMessage(Notification $notification)
    {
        return match ($notification->getMessageType()) {
            MessageTypeProfile::TEMPLATE_BASED_MESSAGE => new TemplateBasedMessage($notification->getData()),
            MessageTypeProfile::POSTER_GENERATED_MESSAGE => new PosterGeneratedMessage($notification->getData()),
            MessageTypeProfile::BACKUP_READY_MESSAGE => new BackupReadyMessage(),
            default => throw new \InvalidArgumentException('Given notification is of unknown type.'),
        };
    }
}
