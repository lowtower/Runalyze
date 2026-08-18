<?php

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

class AverageImpactGs extends AbstractKey
{
	public function id(): int
	{
		return \Runalyze\Dataset\Keys::AVG_IMPACT_GS;
	}

	public function column()
	{
        return ['avg_impact_gs_left', 'avg_impact_gs_right'];
	}

	public function label(): string
	{
		return __('Impact Gs');
	}

	public function stringFor(Context $context)
	{
		return $context->dataview()->impactGs();
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
