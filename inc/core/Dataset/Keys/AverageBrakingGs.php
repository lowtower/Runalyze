<?php

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

class AverageBrakingGs extends AbstractKey
{
	public function id(): int
	{
		return \Runalyze\Dataset\Keys::AVG_BRAKING_GS;
	}

	public function column()
	{
        return ['avg_braking_gs_left', 'avg_braking_gs_right'];
	}

	public function label(): string
	{
		return __('Braking Gs');
	}

	public function stringFor(Context $context)
	{
		return $context->dataview()->brakingGs();
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
