<?php

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

class AveragePronationExcursion extends AbstractKey
{
	public function id(): int
	{
		return \Runalyze\Dataset\Keys::AVG_PRONATION_EXCURSION;
	}

	public function column(): array
	{
        return ['avg_pronation_excursion_left', 'avg_pronation_excursion_right'];
	}

	public function label(): string
	{
		return __('Pronation excursion');
	}

    public function shortLabel(): string
    {
        return __('Pronation');
    }

	public function stringFor(Context $context): string
	{
		return $context->dataview()
		    ->pronationExcursion();
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
