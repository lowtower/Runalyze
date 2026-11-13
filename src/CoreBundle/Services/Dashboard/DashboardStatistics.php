<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services\Dashboard;

use Plugin;

final readonly class DashboardStatistics
{
    /**
     * @param list<array{label: string, href: string, active: bool, children: list<array{label: string, href: string}>}> $navigation
     */
    public function __construct(
        public array $navigation,
        public ?int $activityId,
        public ?Plugin $plugin,
        public bool $hasActiveStatistics
    ) {}
}
