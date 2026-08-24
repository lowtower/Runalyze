<?php

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

class AverageFootstrikeType extends AbstractKey
{
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::AVG_FOOTSTRIKE_TYPE;
    }

    public function column(): array
    {
        return ['avg_footstrike_type_left', 'avg_footstrike_type_right'];
    }

    public function label(): string
    {
        return __('Footstrike type');
    }

    public function stringFor(Context $context): string
    {
        return $context->dataview()
            ->footstrikeType();
    }

    public function summaryMode(): int
    {
        return SummaryMode::AVG;
    }

    public function cssClass(): string
    {
        return 'small';
    }
}
