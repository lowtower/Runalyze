<?php

namespace Runalyze\Calculation\Activity;

use Runalyze\View\Activity\Context;

/**
 * Calculates the HR zones for the zone view.
 * Primarly generate a array with
 * - text [optional]
 * - bpm (the lower bound)
 * - html-color [optional]
 * with this array the TableZonesHeartrate can sort the bpms.
 * if there 3 bpms like 95, 120, 160 configured and a activitity min/maxhr of 51/180 the result will be
 * - 0: 51 - 94     (added by Calculator - always)
 * - 1: 95 - 119
 * - 2: 120 - 159
 * - 3: 160 - 179
 * - 4: 180         (added by Calculator on demand)
 * #TSC
 *
 * @author codeproducer
 * @package Runalyze\Calculation\Activity
 */
class HrZonesCalculator
{
    // followings formats are allowed: [text:]bpm[:color]
    // examples: 165, 165:#ff0000, Anarob:165:#ff0000 or Anarob:165
    public const PATTERN = "/^(.{1,25}:)?(\d{2,3})(:#[0-9a-fA-F]{6})?$/";

    public const PATTERN_GRP_TEXT = 1;

    public const PATTERN_GRP_HR = 2;

    public const PATTERN_GRP_COLOR = 3;

    private string $sourceHint;

    protected ?array $activityZoneBounds = null;

    protected ?array $sportZoneBounds = null;

    /**
     * @param ?Context[] $activityZoneBounds
     */
    public function __construct(?array $activityZoneBounds, ?array $sportZoneBounds)
    {
        $this->activityZoneBounds = $activityZoneBounds;
        $this->sportZoneBounds = $sportZoneBounds;
    }

    /**
     * array with the lower bound bpm of the zones (and some additional infos like text and color)
     * index key is a sequence number and used for sorting and matching.
     * starts with idx=0 which is the $minHr and will be inserted before the predefine zones will created.
     * the predefine (generated or from FIT/device) will be start with idx=1.
     *
     * @param int $minHr the minimal HR of this activity
     * @param int $maxHr the max HR of this activity
     * @param int $hrMaxUser the maximal users HR (not the max of this activity)
     */
    public function getZones(int $minHr, int $maxHr, int $hrMaxUser): array
    {
        $majorZoneColors = ['#a6a6a6', '#3b97f3', '#82c91e', '#f98925', '#d32020', '#b80000'];
        $defaultZones = [];

        $k = 0;
        // add the low bound with min-HR (info: the high bound is the 6th element in the hrZoneBounds)
        $defaultZones[$k++] = [
            'hr' => 0,
        ];

        // are hrZoneBounds from a Garmin/FIT device available?
        if ($this->activityZoneBounds !== null && count($this->activityZoneBounds) === 6) {
            // format of the 91|109|127|146|164|182; details see FitActivity.readTimeInZone()
            $t = ['B1 Warm up', 'B2 Easy', 'B3 Aerob', 'B4 Intensiv', 'B5 Maximal', 'B6 Override'];
            // now add the bounds with its colors
            foreach ($this->activityZoneBounds as $value) {
                $defaultZones[$k] = [
                    't' => $t[$k - 1],
                    'hr' => (int) $value,
                    'c' => $majorZoneColors[$k - 1],
                ];
                $k++;
            }

            $this->sourceHint = 'activity stored zones';
        } elseif ($this->sportZoneBounds !== null && count($this->sportZoneBounds) > 0) {
            // zones for the sport are defined
            foreach ($this->sportZoneBounds as $value) {
                preg_match(self::PATTERN, $value, $matches, PREG_UNMATCHED_AS_NULL);
                $this->removeDelimiter($matches);

                $defaultZones[$k] = [
                    'hr' => (int) $matches[self::PATTERN_GRP_HR],
                ];
                if ($matches[self::PATTERN_GRP_TEXT] !== null) {
                    $defaultZones[$k]['t'] = $matches[1];
                }

                if ($matches[self::PATTERN_GRP_COLOR] !== null) {
                    $defaultZones[$k]['c'] = $matches[3];
                }

                $k++;
            }

            $this->sourceHint = 'sport stored zones';
        } else {
            // default case: 10-percent steps to 100% (max-HR) with colors in the major range 50-100%
            $c = 0;
            for ($i = 3; $i <= 10; $i++, $k++) {
                $defaultZones[$k] = [
                    'hr' => (int) ($hrMaxUser * $i / 10) + 1,
                ];
                // in zonee 50-100% use the same colors as upper
                if ($i >= 5 && $i <= 10) {
                    $defaultZones[$k]['c'] = $majorZoneColors[$c++];
                }
            }

            $this->sourceHint = 'default zones';
        }

        // if the min-HR lower than the first real/computed min HR (index=1), correct the 0 value
        if ($minHr < $defaultZones[1]['hr']) {
            $defaultZones[0]['hr'] = $minHr;
        }

        // this is a "dummy" zone and will never be filled with time/distance; but its used to limit the previous zone ;-)
        if ($maxHr >= end($defaultZones)['hr']) {
            $defaultZones[$k++] = [
                'hr' => $maxHr + 1,
            ];
        }

        // sort: highest idx/bpm must be the first entry
        krsort($defaultZones, SORT_NUMERIC);

        return $defaultZones;
    }

    /**
     * removes a colon in text and/or color.
     * @param array<int, mixed> $zoneMatch
     */
    private function removeDelimiter(array &$zoneMatch): void
    {
        if ($zoneMatch[self::PATTERN_GRP_TEXT] !== null) {
            $zoneMatch[self::PATTERN_GRP_TEXT] = substr($zoneMatch[self::PATTERN_GRP_TEXT], 0, strlen($zoneMatch[self::PATTERN_GRP_TEXT]) - 1);
        }

        if ($zoneMatch[self::PATTERN_GRP_COLOR] !== null) {
            $zoneMatch[self::PATTERN_GRP_COLOR] = substr($zoneMatch[self::PATTERN_GRP_COLOR], 1);
        }
    }

    public function getSourceHint(): string
    {
        return $this->sourceHint;
    }

    /**
     * validates the user input in the sports view.
     *
     * @return bool true=invalid
     */
    public static function validateInputZone(string $zone): bool
    {
        $ok = preg_match(self::PATTERN, $zone, $matches, PREG_UNMATCHED_AS_NULL);
        // [0] is the full string and the other idx are the matches
        $matches = array_filter($matches);
        // check also the matches-1 (because of the [0]) and the ":" are valid
        return ! $ok || count($matches) - 1 < substr_count($zone, ':') + 1;
    }

    /**
     * sorts the zones array ascend of bpm.
     *
     * @param string[] $zones
     * @return string[]
     */
    public static function sortZones(array $zones): array
    {
        usort($zones, static fn (string $a, string $b): int => self::extractHr($a) <=> self::extractHr($b));

        return $zones;
    }

    /**
     * extracts the raw bpm value of a zone-string, independent of
     * the strict formatting rules of self::PATTERN (which requires >=2 digits).
     * used only for internal sorting, not for input validation.
     */
    private static function extractHr(string $zone): int
    {
        foreach (explode(':', $zone) as $part) {
            if (ctype_digit($part)) {
                return (int) $part;
            }
        }

        return 0;
    }
}
