<?php

namespace Runalyze\Parser\Activity\FileType;

use Runalyze\Import\Exception\UnsupportedFileException;
use Runalyze\Parser\Activity\Common\AbstractSingleParser;
use Runalyze\Parser\Activity\Common\Data\GpsDistanceCalculator;
use Runalyze\Parser\Activity\Common\Data\Pause\Pause;
use Runalyze\Parser\Activity\Common\Data\Round\Round;
use Runalyze\Parser\Activity\Common\PauseDetectionCapableParserInterface;
use Runalyze\Parser\Activity\Common\PauseDetectionCapableTrait;
use Runalyze\Parser\Activity\Common\StrtotimeWithLocalTimezoneOffsetTrait;
use SimpleXMLElement;

class TcxActivity extends AbstractSingleParser implements PauseDetectionCapableParserInterface
{
    use PauseDetectionCapableTrait;
    use StrtotimeWithLocalTimezoneOffsetTrait;

    protected \SimpleXMLElement $Xml;

    /**
     * @var int [s]
     */
    private static int $IGNORE_NO_MOVE_UNTIL = 1;

    /**
     * @var int [s]
     */
    protected int $PauseInSeconds = 0;

    /**
     * @var int [m]
     */
    protected int $LastPoint = 0;

    /**
     * @var float [m]
     */
    protected float $LastDistance = -1;

    protected bool $LastPointWasEmpty = false;

    protected bool $IsWithoutDistance = false;

    protected bool $DistancesAreEmpty = false;

    protected bool $WasPause = false;

    /**
     * @var int [s]
     */
    protected int $PauseDuration = 0;

    public function __construct(SimpleXMLElement $activity)
    {
        parent::__construct();

        $this->checkThatActivityXmlIsValid($activity);

        $this->Xml = $activity;
    }

    protected function checkThatActivityXmlIsValid(SimpleXMLElement $xml)
    {
        if (! property_exists($xml, 'Id') || ! property_exists($xml, 'Lap')) {
            throw new UnsupportedFileException('Given XML object is not a valid tcx activity. <Id>- or <Lap>-tag could not be located.');
        }
    }

    public function parse(): void
    {
        $this->parseGeneralValues();
        $this->parseLaps();
    }

    protected function parseGeneralValues()
    {
        $this->Container->Metadata->setTimestampAndTimezoneOffsetWithUtcFixFrom((string) $this->Xml->Id);

        if (isset($this->Xml->Creator) && isset($this->Xml->Creator->Name)) {
            $this->Container->Metadata->setCreator('', (string) $this->Xml->Creator->Name);
        }

        if (isset($this->Xml->attributes()->Sport)) {
            $this->Container->Metadata->setSportName((string) $this->Xml->attributes()->Sport);
        }

        if (! empty($this->Xml->Notes)) {
            $this->Container->Metadata->setNotes((string) $this->Xml->Notes);
        }

        if (! empty($this->Xml->Training)) {
            $this->Container->Metadata->setDescription((string) $this->Xml->Training->Plan->Name);
        }
    }

    protected function parseLaps()
    {
        // NOTE: SimpleXMLElement::key() always returns a string (see the
        // SimpleXML manual). When there is exactly one <Lap> element, the
        // iterator's key is the tag name ("Lap") instead of an integer
        // index. A loose "== 0" comparison used to silently coerce that
        // non-numeric string to 0 under PHP 7.4, making the check pass by
        // accident. Since PHP 8.0's "saner string to number comparisons"
        // (https://wiki.php.net/rfc/string_to_number_comparison),
        // 0 == "Lap" evaluates to false, so the correction below was never
        // executed for single-lap activities, leaving Metadata->Timestamp
        // at the (later) <Id> value instead of the (earlier, correct)
        // <Lap StartTime> / first-trackpoint value.
        // Track the loop position explicitly instead of relying on the
        // iterator key, since it is not reliably an integer.
        $lapIndex = 0;
        foreach ($this->Xml->Lap as $lap) {
            if ($lapIndex === 0) {
                if (isset($lap['StartTime'])) {
                    $start = $this->strtotime((string) $lap['StartTime']);

                    if ($start < $this->Container->Metadata->getTimestamp()) {
                        $this->Container->Metadata->setTimestampAndTimezoneOffsetWithUtcFixFrom((string) $lap['StartTime']);
                    }
                }

                if (isset($lap->Track[0]->Trackpoint[0]->Time) && (string) $lap->Track[0]->Trackpoint[0]->Time !== '') {
                    $firstTrackpointTime = (string) $lap->Track[0]->Trackpoint[0]->Time;
                    $start = $this->strtotime($firstTrackpointTime);

                    if ($start < $this->Container->Metadata->getTimestamp()) {
                        $this->Container->Metadata->setTimestampAndTimezoneOffsetWithUtcFixFrom($firstTrackpointTime);
                    }
                }
            }

            $this->parseLap($lap);
            ++$lapIndex;
        }
    }

    protected function parseLap(SimpleXMLElement &$lap)
    {
        $this->parseLapValues($lap);
        $this->parseTrackpoints($lap);
    }

    protected function parseLapValues(SimpleXMLElement &$lap)
    {
        if (! empty($lap->Calories)) {
            $this->Container->ActivityData->EnergyConsumption += (int) $lap->Calories;
        }

        $this->Container->Rounds->add(new Round(
            round((int) $lap->DistanceMeters) / 1000,
            round((float) $lap->TotalTimeSeconds),
            (string) $lap->Intensity === 'Active'
        ));

        $this->IsWithoutDistance = (
            ((int) $lap->DistanceMeters === 0 && (int) $lap->TotalTimeSeconds > 10) ||
            (
                isset($lap->Track[0]) && count($lap->Track[0]->Trackpoint) > 5 &&
                (float) $lap->Track[0]->Trackpoint[0]->DistanceMeters === (float) $lap->Track[0]->Trackpoint[count($lap->Track[0]->Trackpoint) - 2]->DistanceMeters
            )
        );
    }

    protected function parseTrackpoints(SimpleXMLElement &$trackPoints)
    {
        $this->LastPoint = 0;

        foreach ($trackPoints->Track as $track) {
            if ($this->LastPoint > 0) {
                $this->LastPointWasEmpty = true;
            }

            if (count($track->xpath('./Trackpoint')) > 0) {
                $this->DistancesAreEmpty = count($track->xpath('./Trackpoint/DistanceMeters')) == 0;

                if ($this->strtotime((string) $trackPoints['StartTime']) + 8 < $this->strtotime((string) $track->Trackpoint[0]->Time)) {
                    $this->LastPointWasEmpty = true;
                }

                foreach ($track->Trackpoint as $trackPoint) {
                    $this->parseTrackpoint($trackPoint);
                }
            }
        }
    }

    /**
     * @phpstan-impure This method mutates $this->LastPoint (among other properties).
     */
    protected function parseTrackpoint(SimpleXMLElement &$trackPoint)
    {
        if ($this->DistancesAreEmpty) {
            $trackPoint->addChild('DistanceMeters', 1000 * $this->distanceToTrackpoint($trackPoint));
        }

        $thisBreakInMeter = (float) $trackPoint->DistanceMeters - $this->LastDistance;
        $thisBreakInSeconds = ($this->strtotime((string) $trackPoint->Time) - $this->Container->Metadata->getTimestamp() - end($this->Container->ContinuousData->Time)) - $this->PauseInSeconds;

        if ($thisBreakInSeconds <= 0) {
            return;
        }

        if ($this->DetectPauses && ! $this->IsWithoutDistance) {
            $noMove = ($this->LastDistance == (float) $trackPoint->DistanceMeters);
            $tooSlow = ! $this->LastPointWasEmpty && $thisBreakInMeter > 0 && ($thisBreakInSeconds / $thisBreakInMeter > 6);
        } else {
            $noMove = false;
            $tooSlow = false;
        }

        $hasDistance = isset($trackPoint->DistanceMeters);
        if ((! $hasDistance && ! $this->IsWithoutDistance) || $noMove || $tooSlow) {
            $ignored = false;

            if ($trackPoint->children()->count() == 1 || $noMove || $tooSlow) {
                if ($noMove && $thisBreakInSeconds <= self::$IGNORE_NO_MOVE_UNTIL) {
                    $ignored = true;
                } else {
                    $this->PauseInSeconds += $thisBreakInSeconds;
                    $this->WasPause = true;
                    $this->PauseDuration += $thisBreakInSeconds;
                }
            }

            if (! $ignored) {
                return;
            }
        }

        if (! $hasDistance && $this->Container->ContinuousData->Distance !== []) {
            $trackPoint->DistanceMeters = end($this->Container->ContinuousData->Distance) * 1000;
        }

        if ($this->Container->Metadata->getTimestamp() == 0) {
            $this->Container->Metadata->setTimestampAndTimezoneOffsetWithUtcFixFrom((string) $trackPoint->Time);
        }

        if ($this->LastPointWasEmpty && $this->Container->ContinuousData->Time !== []) {
            $OldPauseInSeconds = $this->PauseInSeconds;
            $this->PauseInSeconds = ($this->strtotime((string) $trackPoint->Time) - $this->Container->Metadata->getTimestamp() - end($this->Container->ContinuousData->Time));
            $this->PauseDuration += $this->PauseInSeconds - $OldPauseInSeconds;
            $this->WasPause = true;
        }

        if ($this->WasPause) {
            $pause = new Pause(end($this->Container->ContinuousData->Time), $this->PauseDuration);
            $pause->setHeartRateDetails(end($this->Container->ContinuousData->HeartRate), ! empty($trackPoint->HeartRateBpm) ? round((int) $trackPoint->HeartRateBpm->Value) : null);

            $this->Container->Pauses->add($pause);

            $this->WasPause = false;
            $this->PauseDuration = 0;
        }

        $this->LastPointWasEmpty = false;
        $this->LastPoint = (int) $trackPoint->DistanceMeters;
        $this->LastDistance = (float) $trackPoint->DistanceMeters;

        $this->Container->ContinuousData->Time[] = $this->strtotime((string) $trackPoint->Time) - $this->Container->Metadata->getTimestamp() - $this->PauseInSeconds;
        $this->Container->ContinuousData->Distance[] = (float) $trackPoint->DistanceMeters / 1000;
        $this->Container->ContinuousData->Altitude[] = (int) $trackPoint->AltitudeMeters;
        $this->Container->ContinuousData->HeartRate[] = (! empty($trackPoint->HeartRateBpm)) ? round((int) $trackPoint->HeartRateBpm->Value) : null;

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

        $this->parseExtensionValues($trackPoint);
    }

    protected function parseExtensionValues(SimpleXMLElement &$point)
    {
        $power = null;
        $rpm = null;

        if (! empty($point->Cadence)) {
            $rpm = (int) $point->Cadence;
        }

        if (isset($point->Extensions)) {
            if (isset($point->Extensions->TPX) && isset($point->Extensions->TPX->RunCadence)) {
                $rpm = (int) $point->Extensions->TPX->RunCadence;
            }

            if (isset($point->Extensions->TPX) && isset($point->Extensions->TPX->Watts)) {
                $power = (int) $point->Extensions->TPX->Watts;
            }

            foreach ($point->Extensions[0]->getNamespaces(true) as $uri) {
                $this->parsePowerFromExtensionValues($point->Extensions[0], $uri, $power, $rpm);
            }
        }

        $this->Container->ContinuousData->Power[] = $power;
        $this->Container->ContinuousData->Cadence[] = $rpm;
    }

    protected function parsePowerFromExtensionValues(SimpleXMLElement $extensions, string $namespaceUri, ?int &$power, ?int &$rpm): void
    {
        $extensionChildren = $extensions->children($namespaceUri);
        if (! isset($extensionChildren->TPX)) {
            return;
        }

        $trackPointExtension = $extensionChildren->TPX[0];
        $values = $trackPointExtension->children($namespaceUri);
        if (isset($values->Watts)) {
            $watts = trim((string) $values->Watts);
            if ($watts !== '') {
                $power = (int) $watts;
            }
        }

        if (isset($values->RunCadence)) {
            $runCadence = trim((string) $values->RunCadence);
            if ($runCadence !== '') {
                $rpm = (int) $runCadence;
            }
        }
    }

    protected function distanceToTrackpoint(SimpleXMLElement &$trackPoint)
    {
        if ($this->Container->ContinuousData->Distance === []) {
            return empty($trackPoint->Position) ? 0.0 : 0.001;
        }

        $currentDistance = end($this->Container->ContinuousData->Distance);

        if (empty($trackPoint->Position)) {
            return $currentDistance;
        }

        $currentLatitude = end($this->Container->ContinuousData->Latitude);
        $currentLongitude = end($this->Container->ContinuousData->Longitude);

        if ($currentLatitude == 0 && $currentLongitude == 0) {
            return $currentDistance + 0.001;
        }

        return $currentDistance + GpsDistanceCalculator::gpsDistance(
            $currentLatitude,
            $currentLongitude,
            (float) $trackPoint->Position->LatitudeDegrees,
            (float) $trackPoint->Position->LongitudeDegrees
        );
    }
}
