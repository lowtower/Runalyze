<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services\Activity;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class SharedActivityUrlGenerator
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function generate(int $activityId): string
    {
        return $this->urlGenerator->generate('shared-activity', [
            'activityHash' => base_convert((string) $activityId, 10, 35),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
