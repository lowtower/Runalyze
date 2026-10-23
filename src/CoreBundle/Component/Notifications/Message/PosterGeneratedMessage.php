<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Notifications\Message;

use Runalyze\Profile\Notifications\MessageTypeProfile;
use Symfony\Component\Routing\RouterInterface;

class PosterGeneratedMessage implements MessageInterface
{
    /**
     * @var int all posters succeeded
     */
    public const int STATE_SUCCESS = 0;

    /**
     * @var int only some posters have been generated
     */
    public const int STATE_PARTIAL = 1;

    /**
     * @var int no poster generated
     */
    public const int STATE_FAILED = 2;

    /**
     * @var int see self::STATE_...
     */
    protected int $State;

    public function __construct($state = self::STATE_SUCCESS)
    {
        $this->State = (int) $state;
    }

    public function getMessageType(): int
    {
        return MessageTypeProfile::POSTER_GENERATED_MESSAGE;
    }

    public function getData(): string
    {
        return (string) $this->State;
    }

    public function getLifetime(): int
    {
        return 5;
    }

    public function getText(\Symfony\Contracts\Translation\TranslatorInterface $translator): string
    {
        return match ($this->State) {
            self::STATE_PARTIAL => $translator->trans('Not all of your posters could be generated.'),
            self::STATE_FAILED => $translator->trans('You requested posters could not be generated.'),
            default => $translator->trans('Your posters have been generated and are now available for download.'),
        };
    }

    public function hasLink(): bool
    {
        return $this->State !== self::STATE_FAILED;
    }

    public function getLink(RouterInterface $router): string
    {
        return $router->generate('poster');
    }

    public function isLinkInternal(): bool
    {
        return true;
    }

    public function getWindowSizeForInternalLink(): string
    {
        return '';
    }
}
