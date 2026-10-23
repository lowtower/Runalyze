<?php

namespace Runalyze\Parser\Activity\Common\Data\Merge;

use Runalyze\Parser\Activity\Common\Data\Metadata;

class MetadataMerger implements MergerInterface
{
    public function __construct(
        protected Metadata $ResultingMetadata,
        protected Metadata $MetadataToMerge
    ) {}

    public function merge(): void
    {
        $this->mergeTimestamp();
        $this->mergeSportDetails();
        $this->mergeDeviceDetails();
        $this->mergeDescriptions();
        $this->mergeEquipment();
    }

    protected function mergeTimestamp()
    {
        if ($this->ResultingMetadata->getTimestamp() === null) {
            $this->ResultingMetadata->setTimestamp(
                $this->MetadataToMerge->getTimestamp(),
                $this->MetadataToMerge->getTimezoneOffset()
            );
        }
    }

    protected function mergeSportDetails()
    {
        if ($this->ResultingMetadata->getSportName() === '') {
            $this->ResultingMetadata->setSportName($this->MetadataToMerge->getSportName());
        }

        if ($this->ResultingMetadata->getInternalSportId() === null) {
            $this->ResultingMetadata->setInternalSportId($this->MetadataToMerge->getInternalSportId());
        }

        if ($this->ResultingMetadata->getTypeName() === '') {
            $this->ResultingMetadata->setTypeName($this->MetadataToMerge->getTypeName());
        }
    }

    protected function mergeDeviceDetails()
    {
        if ($this->ResultingMetadata->getCreator() === '') {
            $this->ResultingMetadata->setCreator($this->MetadataToMerge->getCreator());
        }

        if ($this->ResultingMetadata->getCreatorDetails() === '') {
            $this->ResultingMetadata->setCreator($this->ResultingMetadata->getCreator(), $this->MetadataToMerge->getCreatorDetails());
        }

        if ($this->ResultingMetadata->getActivityId() === null) {
            $this->ResultingMetadata->setActivityId($this->MetadataToMerge->getActivityId());
        }
    }

    protected function mergeDescriptions()
    {
        if ($this->ResultingMetadata->getDescription() === '') {
            $this->ResultingMetadata->setDescription($this->MetadataToMerge->getDescription());
        }

        if ($this->ResultingMetadata->getNotes() === '') {
            $this->ResultingMetadata->setNotes($this->MetadataToMerge->getNotes());
        }

        if ($this->ResultingMetadata->getRouteDescription() === '') {
            $this->ResultingMetadata->setRouteDescription($this->MetadataToMerge->getRouteDescription());
        }
    }

    protected function mergeEquipment()
    {
        foreach ($this->MetadataToMerge->getEquipmentNames() as $name) {
            $this->ResultingMetadata->addEquipment($name);
        }
    }
}
