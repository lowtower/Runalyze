<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Notifications\Message;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

interface MessageInterface
{
    /**
     * @see \Runalyze\Profile\Notifications\MessageTypeProfile
     */
    public function getMessageType(): int;

    public function getData(): string;

    /**
     * @return null|int [days]
     */
    public function getLifetime(): ?int;

    public function getText(TranslatorInterface $translator): string;

    public function hasLink(): bool;

    public function getLink(RouterInterface $router): string;

    public function isLinkInternal(): bool;

    /**
     * @return string e.g. 'big' or 'small' (anything else will be interpreted as default size)
     */
    public function getWindowSizeForInternalLink(): string;
}
