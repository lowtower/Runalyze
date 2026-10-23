<?php

declare(strict_types=1);

namespace Runalyze\Parser\Activity\Common\Data\Merge;

use Runalyze\Parser\Activity\Common\Data\FitDetails;

class FitDetailsMerger implements MergerInterface
{
    public function __construct(
        protected FitDetails $ResultingDetails,
        protected FitDetails $DetailsToMerge
    ) {}

    public function merge(): void
    {
        foreach ($this->getPropertiesToMerge() as $property) {
            $this->ResultingDetails->{$property} ??= $this->DetailsToMerge->{$property};
        }
    }

    /**
     * @return array<int, string>
     */
    protected function getPropertiesToMerge(): array
    {
        return [
            'VO2maxEstimate',
            'RecoveryTime',
            'HrvAnalysis',
            'TrainingEffect',
            'PerformanceCondition',
            'PerformanceConditionEnd',
        ];
    }
}
