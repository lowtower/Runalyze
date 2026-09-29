<?php

/**
 * This file contains class::TableZonesHeartrate
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\Activity\Pace;
use Runalyze\Calculation\Activity\HrZonesCalculator;
use Runalyze\Calculation\Distribution\TimeSeriesForTrackdata;
use Runalyze\Model\Trackdata;
use Runalyze\View\Activity\Context;

/**
 * Display heartrate zones
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
class TableZonesHeartrate extends TableZonesAbstract
{
    /**
     * @var int [bpm]
     */
    public const MINIMUM_TIME_IN_ZONE_MIN_HR = 60;

    public const DEFAULT_COLOR = '#c6c5c5';

    private string $footerHint = '';

    /**
     * Calculator
     */
    protected \Runalyze\Calculation\Activity\HrZonesCalculator $Calculator;

    public function __construct(Context $context)
    {
        $this->Calculator = new HrZonesCalculator($context->activity()->hrZoneBounds(), $context->sport()->hrZoneBounds());

        parent::__construct($context);
    }

    /**
     * Get title for average
     */
    public function titleForAverage(): string
    {
        return __('avg.') . '&nbsp;' . __('Pace');
    }

    /**
     * Get hint for footer #TSC
     */
    public function footerHint(): string
    {
        return $this->footerHint;
    }

    protected function initData()
    {
        $Zones = $this->computeZones();
        $Pace = new Pace(0, 0);
        $Pace->setUnit($this->Context->sport()->legacyPaceUnit());

        foreach ($Zones as $Info) {
            if ($Info['time'] > parent::MINIMUM_TIME_IN_ZONE || $Info['#start'] > self::MINIMUM_TIME_IN_ZONE_MIN_HR) {
                $Pace->setTime($Info['time']);
                $Pace->setDistance($Info['distance']);

                // #TSC add more info in the zone column: the range of the % and numbers incl. a text if available
                $perRange = $this->threeNumbers($Info['%start']) . ' -' . $this->threeNumbers($Info['%end']) . ' &#37;';
                $bpmRange = $this->threeNumbers($Info['#start']) . ' -' . $this->threeNumbers($Info['#end']) . ' bpm';

                $this->Data[] = [
                    'zone' => $Info['text'] . ' ' . $perRange . ' / ' . $bpmRange,
                    'time' => $Info['time'],
                    'distance' => $Info['distance'],
                    'average' => $Pace->value() > 0 ? $Pace->valueWithAppendix() : '-',
                    'color' => $Info['color'], # TSC support for colors
                ];
            }
        }
    }

    /**
     * fill a 2 char number with blanks for a better "column" prepresentation.
     */
    private function threeNumbers(int $num): string
    {
        return ($num <= 99 ? '&nbsp;&nbsp;' : '') . $num;
    }

    protected function computeZones(): array
    {
        if ($this->Context->activity()->maxHrUser() !== null) {
            $hrMax = $this->Context->activity()
                ->maxHrUser();
            $footerHintMaxHr = sprintf(
                'with a maximum user heart rate of %d from the activity.',
                $hrMax
            );
        } else {
            $hrMax = Runalyze\Configuration::Data()->HRmax();
            $footerHintMaxHr = sprintf(
                'with a maximum user heart rate of %d from the user body data.',
                $hrMax
            );
        }

        if ($hrMax <= 0) {
            $this->footerHint =
                'Heart-rate zones cannot be calculated because no valid '
                . 'maximum heart rate is available.';

            return [];
        }

        $zones = [];

        $hasDistance = $this->Context->trackdata()
            ->has(Trackdata\Entity::DISTANCE);

        $distribution = new TimeSeriesForTrackdata(
            $this->Context->trackdata(),
            Trackdata\Entity::HEARTRATE,
            $hasDistance ? [Trackdata\Entity::DISTANCE] : []
        );

        $data = $distribution->data();
        $histogram = $distribution->histogram();

        if ($histogram === []) {
            $this->footerHint =
                'Heart-rate zones cannot be calculated because no '
                . 'heart-rate data is available.';

            return [];
        }

        ksort($histogram);

        $minHr = array_key_first($histogram);
        $maxHr = array_key_last($histogram);

        if (! is_int($minHr) && ! ctype_digit((string) $minHr)) {
            $this->footerHint =
                'Heart-rate zones cannot be calculated because the '
                . 'heart-rate data contains invalid values.';

            return [];
        }

        if (! is_int($maxHr) && ! ctype_digit((string) $maxHr)) {
            $this->footerHint =
                'Heart-rate zones cannot be calculated because the '
                . 'heart-rate data contains invalid values.';

            return [];
        }

        $defaultZones = $this->getZones(
            (int) $minHr,
            (int) $maxHr,
            $hrMax,
            $footerHintMaxHr
        );

        foreach ($histogram as $bpm => $seconds) {
            $zoneIndex = $this->zoneFor($bpm, $defaultZones);

            if ($zoneIndex === null) {
                continue;
            }

            if (
                ! isset($defaultZones[$zoneIndex]) ||
                ! isset($defaultZones[$zoneIndex + 1])
            ) {
                continue;
            }

            if (! isset($zones[$zoneIndex])) {
                $startHr = (int) $defaultZones[$zoneIndex]['hr'];
                $endHr = (int) $defaultZones[$zoneIndex + 1]['hr'] - 1;

                $zones[$zoneIndex] = [
                    'text' => $defaultZones[$zoneIndex]['t'] ?? '',
                    '#start' => $startHr,
                    '#end' => $endHr,
                    '%start' => (int) round(100 * $startHr / $hrMax),
                    '%end' => (int) round(100 * $endHr / $hrMax),
                    'time' => 0,
                    'distance' => 0.0,
                    'color' => $defaultZones[$zoneIndex]['c']
                        ?? self::DEFAULT_COLOR,
                ];
            }

            $zones[$zoneIndex]['time'] += $seconds;
            $zones[$zoneIndex]['distance'] += $hasDistance
                ? ($data[$bpm][Trackdata\Entity::DISTANCE] ?? 0.0)
                : 0.0;
        }

        ksort($zones, SORT_NUMERIC);

        return $zones;
    }

    private function zoneFor(int $bpm, array $zones): ?int
    {
        foreach ($zones as $key => $value) {
            if ($bpm >= $value['hr']) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @return mixed[]
     */
    private function getZones(int $minHr, int $maxHr, int $hrMaxUser, string $footerHintMaxHr): array
    {
        $z = $this->Calculator->getZones($minHr, $maxHr, $hrMaxUser);
        $this->footerHint = 'Shows the ' . $this->Calculator->getSourceHint() . ' ' . $footerHintMaxHr;
        return $z;
    }
}
