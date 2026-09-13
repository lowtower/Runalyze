<?php

namespace Runalyze\Parser\Activity\FileType;

use SimpleXMLElement;

class TcxActivityRuntastic extends TcxActivity
{
    protected int $CurrentIndex = 0;

    protected int $LastActiveIndex = 0;

    /**
     * @var int [bpm]
     */
    protected int $LastValidHR = 0;

    protected function parseGeneralValues()
    {
        parent::parseGeneralValues();

        $this->Container->ActivityData->Duration = (int) $this->Xml->Lap->TotalTimeSeconds;
    }

    protected function parseTrackpoint(SimpleXMLElement &$trackPoint)
    {
        if ($this->DistancesAreEmpty) {
            $trackPoint->addChild('DistanceMeters', 1000 * $this->distanceToTrackpoint($trackPoint));
        }

        if (! empty($trackPoint->HeartRateBpm)) {
            $this->LastValidHR = (int) round((float) $trackPoint->HeartRateBpm->Value);
        }

        $this->Container->ContinuousData->Time[] = $this->strtotime((string) $trackPoint->Time) - $this->Container->Metadata->getTimestamp();
        $this->Container->ContinuousData->Distance[] = (float) $trackPoint->DistanceMeters / 1000;
        $this->Container->ContinuousData->Altitude[] = (int) $trackPoint->AltitudeMeters;
        $this->Container->ContinuousData->HeartRate[] = $this->LastValidHR;

        if (! empty($trackPoint->Position)) {
            $this->Container->ContinuousData->Latitude[] = (float) $trackPoint->Position->LatitudeDegrees;
            $this->Container->ContinuousData->Longitude[] = (float) $trackPoint->Position->LongitudeDegrees;
        } elseif ($this->Container->ContinuousData->Latitude !== []) {
            $this->Container->ContinuousData->Latitude[] = end($this->Container->ContinuousData->Latitude);
            $this->Container->ContinuousData->Longitude[] = end($this->Container->ContinuousData->Longitude);
        } else {
            $this->Container->ContinuousData->Latitude[] = 0;
            $this->Container->ContinuousData->Longitude[] = 0;
        }

        $this->CurrentIndex++;

        $this->parseExtensionValues($trackPoint);
    }

    protected function getCurrentPaceForRuntastic(): int
    {
        $currDist = $this->Container->ContinuousData->Distance[$this->CurrentIndex];
        $lastDist = $this->Container->ContinuousData->Distance[$this->LastActiveIndex];
        $currTime = $this->Container->ContinuousData->Time[$this->CurrentIndex];
        $lastTime = $this->Container->ContinuousData->Time[$this->LastActiveIndex];

        if ($currDist > $lastDist) {
            return (int) round(($currTime - $lastTime) / ($currDist - $lastDist));
        }

        return 0;
    }
}
