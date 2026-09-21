<?php

declare(strict_types=1);

namespace Runalyze\Parser\Activity\Common\Data\Merge;

use Runalyze\Parser\Activity\Common\Data\ActivityData;

class ActivityDataMerger implements MergerInterface
{
    public function __construct(
        protected ActivityData $ResultingData,
        protected ActivityData $DataToMerge
    ) {
    }

    public function merge(): void
    {
        foreach ($this->ResultingData->getPropertyNames() as $property) {
            $this->ResultingData->{$property} ??= $this->DataToMerge->{$property};
        }
    }
}
