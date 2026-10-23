<?php

/**
 * This file contains class::Entity
 * @package Runalyze\Model\Swimdata
 */

namespace Runalyze\Model\Swimdata;

use Runalyze\Model;
use Runalyze\Model\Trackdata;
use Runalyze\Profile\FitSdk\StrokeTypeProfile;

/**
 * Swimdata entity
 *
 * @author Hannes Christiansen & Michael Pohl
 * @package Runalyze\Model\Swimdata
 */
class Entity extends Model\Entity implements Model\Loopable
{
    /**
     * Key: activity id
     */
    public const string ACTIVITYID = 'activityid';

    /**
     * Key: stroke
     */
    public const string STROKE = 'stroke';

    /**
     * Key: pool length
     */
    public const string POOL_LENGTH = 'pool_length';

    /**
     * Key: stroketype
     */
    public const string STROKETYPE = 'stroketype';

    /**
     * Key: swolf
     */
    public const string SWOLF = 'swolf';

    /**
     * Key: SWOLFCYCLES
     */
    public const string SWOLFCYCLES = 'swolfcycles';

    /**
     * Construct
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->checkArraySizes();
    }

    /**
     * @return array<int, string>
     */
    public static function allProperties(): array
    {
        return [
            self::ACTIVITYID,
            self::STROKE,
            self::STROKETYPE,
            self::SWOLF,
            self::SWOLFCYCLES,
            self::POOL_LENGTH,
        ];
    }

    /**
     * Can be null?
     */
    protected function canBeNull(string $key): bool
    {
        return match ($key) {
            self::STROKE, self::STROKETYPE, self::SWOLF, self::SWOLFCYCLES => true,
            default => false,
        };
    }

    /**
     * Is the property an array?
     */
    public function isArray(string $key): bool
    {
        return $key !== self::ACTIVITYID && $key !== self::POOL_LENGTH;
    }

    /**
     * Properties
     */
    public function properties(): array
    {
        return self::allProperties();
    }

    public function synchronize(): void
    {
        parent::synchronize();

        $this->ensureAllNumericValues();
    }

    /**
     * Ensure that numeric fields get numeric values
     */
    protected function ensureAllNumericValues()
    {
        $this->ensureNumericValue([
            self::POOL_LENGTH,
        ]);
    }

    /**
     * Number of points
     */
    public function num(): int
    {
        return $this->numberOfPoints;
    }

    /**
     * Value at
     *
     * Remark: This method may throw index offsets.
     * @return mixed
     */
    public function at(int $index, string $key)
    {
        return $this->Data[$key][$index];
    }

    public function activityID(): int
    {
        return (int) $this->Data[self::ACTIVITYID];
    }

    public function stroke(): array
    {
        return $this->Data[self::STROKE];
    }

    public function stroketype(): array
    {
        return $this->Data[self::STROKETYPE];
    }

    public function swolf(): array
    {
        return $this->Data[self::SWOLF];
    }

    /**
     * SWOLFcycles
     */
    public function swolfcycles(): array
    {
        return $this->Data[self::SWOLFCYCLES];
    }

    /**
     * STROKETYPE
     * @return int [cm]
     */
    public function poollength(): int
    {
        return (int) $this->Data[self::POOL_LENGTH];
    }

    /*
     * Calculate Distance based on pool length
     * Fills a array like [0.025,0.05,0.075,0.75,0.1 ...]
     */
    public function fillDistanceArray(Trackdata\Entity $trackdata): void
    {
        if ($this->poollength() && ! $trackdata->has(Trackdata\Entity::DISTANCE) && $this->num() === $trackdata->num()) {
            $length = $this->poollength();

            $distance = null;
            if ($this->stroketype() && $this->num() === count($this->stroketype())) {
                // #TSC: optimize for GARMINs: if we have 0 strokes for one lane, distance must be 0 for this lane; because it's a erholung/"length_type=0=idle" lane
                $d = 0;
                foreach ($this->stroketype() as &$strtype) {
                    // only add pool-length if stroke-type is not pause are done in this lane
                    $d += $strtype != StrokeTypeProfile::BREAK ? $length : null;
                    $distance[] = $d;
                }
            } else {
                // if we have no strokes, fill the array as before; every lane = pool-length
                $distance = range($length, $this->num() * $length, $length);
            }

            // from meter to kilometer
            $distance = array_map(fn ($v): int|float => $v / 100000, $distance);
            $trackdata->set(Trackdata\Entity::DISTANCE, $distance);
            $trackdata->calculatePaceArray();
        }
    }

    /*
     * Create swolf array
     * http://marathonswimmers.org/blog/2012/05/stroke-count-games/
     */
    public function fillSwolfArray(Trackdata\Entity $trackdata): void
    {
        if ($this->stroke() && $trackdata->has(Trackdata\Entity::TIME) && $this->num() === $trackdata->num()) {
            $TrackLoop = new Trackdata\Loop($trackdata);
            $Loop = new Loop($this);

            $max = $Loop->num();
            $swolf = [];
            $swolfcycles = [];

            for ($i = 1; $i <= $max; ++$i) {
                $duration = $TrackLoop->difference(Trackdata\Entity::TIME);

                // #TSC if we have no stroke for this lane we cant calulate SWOLF
                if ($Loop->stroke() > 0) {
                    $swolf[] = $duration + $Loop->stroke();
                    $swolfcycles[] = $duration + $Loop->stroke() / 2;
                } else {
                    $swolf[] = null;
                    $swolfcycles[] = null;
                }

                $Loop->nextStep();
                $TrackLoop->nextStep();
            }

            $this->set(self::SWOLF, $swolf);
            $this->set(self::SWOLFCYCLES, $swolfcycles);
        }
    }
}
