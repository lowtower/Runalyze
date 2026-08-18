<?php

declare(strict_types=1);

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

class PercentageHilly extends AbstractKey
{
	public function id(): int
	{
		return \Runalyze\Dataset\Keys::PERCENTAGE_HILLY;
	}

	public function column(): string
	{
		return 'percentage_hilly';
	}

	public function label(): string
	{
		return __('Percentage hilly');
	}

	public function shortLabel(): string
	{
		return __('Hilly');
	}

	public function description(): string
	{
		return __(
		    '' // TODO
		);
	}

	public function stringFor(Context $context)
	{
        if (! $context->activity()->isPublic() && \Request::isOnSharedPage() || $context->activity()->id() <= 0) {
            return $context->dataview()
                ->percentageHilly();
        }

        return '<a class="window" href="activity/'.$context->activity()->id().'/climb-score">'.$context->dataview()->percentageHilly().'</a>';
	}
}
