<?php

declare(strict_types=1);

namespace Runalyze\Parser\Activity\Common\Data\Merge;

use Runalyze\Parser\Activity\Common\Data\ActivityData;

class ActivityDataMerger implements MergerInterface
{
    /**
     * @var ActivityData
     */
    protected $ResultingData;

    /**
     * @var ActivityData
     */
    protected $DataToMerge;

    public function __construct(ActivityData $firstData, ActivityData $secondData)
    {
        $this->ResultingData = $firstData;
        $this->DataToMerge = $secondData;
    }

    public function merge()
    {
        foreach ($this->ResultingData->getPropertyNames() as $property) {
            $this->ResultingData->{$property} ??= $this->DataToMerge->{$property};
        }
    }
}
