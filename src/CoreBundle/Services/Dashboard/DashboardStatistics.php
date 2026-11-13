<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services\Dashboard;

use Plugin;

final readonly class DashboardStatistics
{
    public function __construct(
        public array $navigation,
        public ?int $activityId,
        public ?Plugin $plugin,
        public bool $hasActiveStatistics
    ) {}
}
