<?php

namespace Runalyze\Parser\Activity\FileType;

use Runalyze\Parser\Activity\Common\AbstractSingleParser;
use Runalyze\Parser\Activity\Common\Data\Pause\Pause;
use Runalyze\Parser\Common\FileContentAwareParserInterface;
use Runalyze\Parser\Common\XmlParserTrait;

class KmlExtended extends AbstractSingleParser implements FileContentAwareParserInterface
{
    use XmlParserTrait;

    /**
     * @var int [s]
     */
    protected int $PauseInSeconds = 0;

    protected int $PauseIndex = 0;

    /**
     * @var int[]
     */
    protected array $PauseTimes = [];

    /**
     * @var string[]|bool[]
     */
    protected array $ResumeTimes = [];

    public function parse(): void
    {
        $this->preparePauses();
        $this->parseGeneralValues();
        $this->parseCoordinates();
        $this->parseTimeSteps();
        $this->parseExtendedData();
        $this->interpretTimestampAsServerTime();
    }

    protected function parseGeneralValues()
    {
        $when = $this->Xml->xpath('//when');

        $this->Container->Metadata->setTimestamp(strtotime((string) $when[0]));
    }

    protected function interpretTimestampAsServerTime()
    {
        $this->Container->Metadata->interpretTimestampAsServerTime($this->Container->Metadata->getTimestamp());
    }

    protected function parseCoordinates()
    {
        foreach ($this->Xml->xpath('//gx:coord') as $coord) {
            $parts = explode(' ', (string) $coord);
            $num = count($parts);

            if (($num === 3 || $num === 2) && ($parts[0] != 0.0 || $parts[1] != 0.0)) {
                $this->Container->ContinuousData->Latitude[] = $parts[1];
                $this->Container->ContinuousData->Longitude[] = $parts[0];
                $this->Container->ContinuousData->Altitude[] = ($num > 2) ? $parts[2] : null;
            }
        }
    }

    protected function parseTimeSteps()
    {
        $startTime = $this->Container->Metadata->getTimestamp();
        $DstCorrector = date('I') - date('I', $startTime);

        foreach ($this->Xml->xpath('//when') as $step) {
            $time = strtotime((string) $step);

            if ($this->hasMorePauses()) {
                $currentTime = strftime('%T', $time + $DstCorrector * 3600);

                if ($currentTime == $this->ResumeTimes[$this->PauseIndex]) {
                    $this->Container->Pauses->add(new Pause(
                        end($this->Container->ContinuousData->Time),
                        $this->PauseTimes[$this->PauseIndex]
                    ));

                    $this->PauseInSeconds += $this->PauseTimes[$this->PauseIndex];
                    $this->PauseIndex++;
                }
            }

            $this->Container->ContinuousData->Time[] = $time - $startTime - $this->PauseInSeconds;
        }
    }

    protected function parseExtendedData()
    {
        foreach ($this->Xml->xpath('//gx:SimpleArrayData') as $array) {
            $values = $array->xpath('gx:value');

            switch (strtolower($array['name'])) {
                case 'calories':
                    $this->parseExtendedCalories($values);
                    break;
                case 'distance':
                    $this->parseExtendedDistance($values);
                    break;
                case 'heartrate':
                    $this->parseExtendedHeartRate($values);
                    break;
                case 'power':
                    $this->parseExtendedPower($values);
                    break;
                case 'cadence':
                    $this->parseExtendedCadence($values);
                    break;
                case 'temperature':
                    $this->parseExtendedTemperature($values);
                    break;
            }
        }
    }

    /**
     * @param \SimpleXMLElement[]|bool $values
     */
    protected function parseExtendedCalories($values)
    {
        $kcal = array_pop($values);

        $this->Container->ActivityData->EnergyConsumption = (int) $kcal;
    }

    /**
     * @param \SimpleXMLElement[]|bool $values
     */
    protected function parseExtendedDistance($values)
    {
        foreach ($values as $value) {
            $this->Container->ContinuousData->Distance[] = (float) $value / 1000;
        }
    }

    /**
     * @param \SimpleXMLElement[]|bool $values
     */
    protected function parseExtendedHeartRate($values)
    {
        foreach ($values as $value) {
            $this->Container->ContinuousData->HeartRate[] = (int) $value;
        }
    }

    /**
     * @param \SimpleXMLElement[]|bool $values
     */
    protected function parseExtendedPower($values)
    {
        foreach ($values as $value) {
            $this->Container->ContinuousData->Power[] = (int) $value;
        }
    }

    /**
     * @param \SimpleXMLElement[]|bool $values
     */
    protected function parseExtendedCadence($values)
    {
        foreach ($values as $value) {
            $this->Container->ContinuousData->Cadence[] = (int) $value;
        }
    }

    protected function parseExtendedAltitude(array $values)
    {
        foreach ($values as $value) {
            $this->Container->ContinuousData->Altitude[] = (int) $value;
        }
    }

    /**
     * @param \SimpleXMLElement[]|bool $values
     */
    protected function parseExtendedTemperature($values)
    {
        foreach ($values as $value) {
            $this->Container->ContinuousData->Temperature[] = (int) $value;
        }
    }

    protected function preparePauses()
    {
        $start = 0;

        foreach ($this->Xml->Document->Folder as $folder) {
            if ($folder['id'] == 'pause_resume') {
                foreach ($folder->Placemark as $mark) {
                    if ($mark->styleUrl == '#pause') {
                        $start = strtotime($mark->description . 'Z');
                    } elseif ($mark->styleUrl == '#resume') {
                        $end = strtotime($mark->description . 'Z');

                        $this->ResumeTimes[] = strftime('%T', $end);
                        $this->PauseTimes[] = ($end - $start) % DAY_IN_S;
                    }
                }
            }
        }
    }

    protected function hasMorePauses(): bool
    {
        return $this->PauseIndex < count($this->PauseTimes);
    }
}
