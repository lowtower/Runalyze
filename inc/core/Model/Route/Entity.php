<?php

/**
 * This file contains class::Entity
 * @package Runalyze\Model\Route
 */

namespace Runalyze\Model\Route;

use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Coordinate\CoordinateInterface;
use League\Geotools\Geohash\Geohash;
use Runalyze\Calculation\Route\GeohashLine;
use Runalyze\Model;

/**
 * Route entity
 *
 * Attention: `set(Entity::GEOHASHES, $geohashes)` or `setLatitudesLongitudes($lats, $lngs)`
 * should be used instead of serving geohashes in constructor to ensure correct
 * min/max geohashes. They are not calculated within `synchronize()`!
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\Route
 */
class Entity extends Model\EntityWithID implements Model\Loopable, Model\Common\WithNullableArraysInterface
{
    use Model\Common\WithNullableArraysTrait;

    /**
     * Cities separator
     */
    public const string CITIES_SEPARATOR = ' - ';

    public const int PATH_GEOHASH_PRECISION = 12;

    public const int BOUNDARIES_GEOHASH_PRECISION = 10;

    /**
     * Key: name
     */
    public const string NAME = 'name';

    /**
     * Key: cities
     */
    public const string CITIES = 'cities';

    /**
     * Key: distance
     */
    public const string DISTANCE = 'distance';

    /**
     * Key: elevation
     */
    public const string ELEVATION = 'elevation';

    /**
     * Key: elevation up
     */
    public const string ELEVATION_UP = 'elevation_up';

    /**
     * Key: elevation down
     */
    public const string ELEVATION_DOWN = 'elevation_down';

    /**
     * Key: geohash
     */
    public const string GEOHASHES = 'geohashes';

    /**
     * Key: elevations original
     */
    public const string ELEVATIONS_ORIGINAL = 'elevations_original';

    /**
     * Key: elevations corrected
     */
    public const string ELEVATIONS_CORRECTED = 'elevations_corrected';

    /**
     * Key: elevations source
     */
    public const string ELEVATIONS_SOURCE = 'elevations_source';

    /**
     * Key: elevations source
     */
    public const string ALTITUDE_BAROMETRIC = 'altitude_barometric';

    /**
     * Key: startpoint in geohash
     */
    public const string STARTPOINT = 'startpoint';

    /**
     * Key: endpoint in geohash
     */
    public const string ENDPOINT = 'endpoint';

    /**
     * Key: minimal longitude & latitude in geohash
     */
    public const string MIN = 'min';

    /**
     * Key: maximal longitude & latitude in geohash
     */
    public const string MAX = 'max';

    /**
     * Key: in routenet
     */
    public const string IN_ROUTENET = 'in_routenet';

    /**
     * Key: lock
     */
    public const string LOCK = 'lock';

    /**
     * Flag: ensure arrays to be equally sized
     */
    protected bool $checkArraySizes = true;

    /**
     * Construct
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        if ($this->hasGeohashes() && ! empty($this->Data[self::GEOHASHES])) {
            $this->Data[self::GEOHASHES] = GeohashLine::extend($this->Data[self::GEOHASHES]);
        }
    }

    /**
     * Construct factory for route data where every x point is used.
     * #TSC This is used to reduce the amount of routes and the shown gps positions; so more routes
     * can be shown with lower resolution.
     */
    public static function createWithIntervalClearing(array &$data, int $positioninterval = 1): self
    {
        $e = new self($data);

        // remove points so every x position is keeped
        if ($positioninterval > 1 && $e->hasGeohashes() && ! empty($e->Data[self::GEOHASHES])) {
            // use a loop based on the original array to avoid a "copy"
            $i = count($e->Data[self::GEOHASHES]);
            while ($i > 0) {
                for ($y = 1; $i > 0 && $y < $positioninterval; $y++) {
                    unset($e->Data[self::GEOHASHES][--$i]);
                }

                $i--;
            }

            $e->Data[self::GEOHASHES] = array_values($e->Data[self::GEOHASHES]);
            // reset also the count
            $e->numberOfPoints = count($e->Data[self::GEOHASHES]);
            // the other data (like min/max or evaluation) must be indentical and must not be changed
        }

        return $e;
    }

    /**
     * @throws \RuntimeException
     */
    protected function checkArraySizes(): void
    {
        foreach ($this->properties() as $key) {
            if ($this->isArray($key)) {
                try {
                    $count = count($this->Data[$key]);

                    if ($key == self::ELEVATIONS_CORRECTED && $this->numberOfPoints > 0 && $count > $this->numberOfPoints) {
                        $this->Data[$key] = array_slice($this->Data[$key], 0, $this->numberOfPoints);
                    } else {
                        $this->checkArraySize($count);
                    }
                } catch (\RuntimeException $E) {
                    throw new \RuntimeException($E->getMessage() . ' (for ' . $key . ')', $E->getCode(), $E);
                }
            }
        }
    }

    /**
     * @return array<int, string>
     */
    public static function allProperties(): array
    {
        return [
            self::NAME,
            self::CITIES,
            self::DISTANCE,
            self::ELEVATION,
            self::ELEVATION_UP,
            self::ELEVATION_DOWN,
            self::GEOHASHES,
            self::ELEVATIONS_ORIGINAL,
            self::ELEVATIONS_CORRECTED,
            self::ELEVATIONS_SOURCE,
            self::ALTITUDE_BAROMETRIC,
            self::STARTPOINT,
            self::ENDPOINT,
            self::MIN,
            self::MAX,
            self::IN_ROUTENET,
            self::LOCK,
        ];
    }

    /**
     * All properties
     * @return array<int, string>
     */
    public static function allDatabaseProperties(): array
    {
        return [
            self::NAME,
            self::CITIES,
            self::DISTANCE,
            self::ELEVATION,
            self::ELEVATION_UP,
            self::ELEVATION_DOWN,
            self::GEOHASHES,
            self::ELEVATIONS_ORIGINAL,
            self::ELEVATIONS_CORRECTED,
            self::ELEVATIONS_SOURCE,
            self::ALTITUDE_BAROMETRIC,
            self::STARTPOINT,
            self::ENDPOINT,
            self::MIN,
            self::MAX,
            self::IN_ROUTENET,
        ];
    }

    /**
     * Properties
     */
    public function properties(): array
    {
        return self::allProperties();
    }

    /**
     * Is the property an array?
     */
    public function isArray(string $key): bool
    {
        return match ($key) {
            self::ELEVATIONS_ORIGINAL, self::ELEVATIONS_CORRECTED, self::GEOHASHES => true,
            default => false,
        };
    }

    /**
     * Can be null?
     */
    protected function canBeNull(string $key): bool
    {
        return match ($key) {
            self::ELEVATIONS_ORIGINAL, self::ELEVATIONS_CORRECTED, self::ALTITUDE_BAROMETRIC, self::GEOHASHES, self::STARTPOINT, self::ENDPOINT, self::MIN, self::MAX => true,
            default => false,
        };
    }

    /**
     * Synchronize internal models
     */
    public function synchronize(): void
    {
        parent::synchronize();

        $this->ensureAllNumericValues();
        $this->synchronizeStartAndEndpoint();

        if (! $this->hasCorrectedElevations()) {
            $this->set(self::ELEVATIONS_SOURCE, '');
        }
    }

    /**
     * Ensure that numeric fields get numeric values
     */
    protected function ensureAllNumericValues()
    {
        $this->ensureNumericValue([
            self::DISTANCE,
            self::ELEVATION,
            self::ELEVATION_UP,
            self::ELEVATION_DOWN,
            self::ALTITUDE_BAROMETRIC,
            self::IN_ROUTENET,
        ]);
    }

    /**
     * Set array
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function set(string $key, mixed $value): void
    {
        parent::set($key, $value);

        if ($key === self::GEOHASHES) {
            $this->setMinMaxFromGeohashes($value);
        }
    }

    public function setGeohashesWithoutMinMaxRecalculation(array $geohashes): void
    {
        $this->Data[self::GEOHASHES] = $geohashes;
    }

    public function setMinMaxFromGeohashes(?array $geohashes = null): void
    {
        if ($geohashes === null) {
            $this->setMinMaxToNull();

            return;
        }

        $latitudes = [];
        $longitudes = [];

        foreach ($geohashes as $geohash) {
            $coordinate = $this->coordinate($geohash);
            $latitudes[] = round((float) $coordinate->getLatitude(), 6);
            $longitudes[] = round((float) $coordinate->getLongitude(), 6);
        }

        $this->setMinMaxFromLatitudesLongitudes($latitudes, $longitudes);
    }

    public function forceToSetMinMaxFromGeohashes(): void
    {
        $this->setMinMaxFromGeohashes($this->Data[self::GEOHASHES]);
    }

    /**
     * Set geohashes from latitudes/longitudes
     * @param array<int, mixed> $latitudes
     * @param array<int, mixed> $longitudes
     * @throws \InvalidArgumentException
     */
    public function setLatitudesLongitudes(array $latitudes, array $longitudes): void
    {
        $size = count($latitudes);

        if ($size !== count($longitudes)) {
            throw new \InvalidArgumentException('Latitude & Longitude Array cannot have different lenghts');
        }

        $latitudes = array_map(fn ($value): float|int => ($value == '') ? 0 : (float) $value, $latitudes);
        $longitudes = array_map(fn ($value): float|int => ($value == '') ? 0 : (float) $value, $longitudes);

        $geohashes = [];

        for ($i = 0; $i < $size; ++$i) {
            /** @var Geohash $geohashObj */
            $geohashObj = new Geohash()->encode(new Coordinate([$latitudes[$i], $longitudes[$i]]), self::PATH_GEOHASH_PRECISION);
            $geohashes[] = $geohashObj->getGeohash();
        }

        $this->Data[self::GEOHASHES] = $geohashes;
        $this->handleNewArraySize($size);

        $this->setMinMaxFromLatitudesLongitudes($latitudes, $longitudes);
    }

    /**
     * create latitude and longitude array
     * @return array Array with coordiantes: ['lat' => array, 'lng' => array]
     */
    public function latitudesAndLongitudesFromGeohash(): array
    {
        $Coordinates = [];
        $Geohashes = $this->Data[self::GEOHASHES];
        $size = count($this->Data[self::GEOHASHES]);

        for ($i = 0; $i < $size; $i++) {
            $geo = $this->coordinate($Geohashes[$i]);
            $Coordinates['lat'][] = round((float) $geo->getLatitude(), 6);
            $Coordinates['lng'][] = round((float) $geo->getLongitude(), 6);
        }

        return $Coordinates;
    }

    /**
     * get latitudes array from geohashes
     * @return array latitudes
     */
    public function latitudesFromGeohash()
    {
        return $this->latitudesAndLongitudesFromGeohash()['lat'];
    }

    /**
     * get longitudes array from geohashes
     * @return array longitudes
     */
    public function longitudesFromGeohash()
    {
        return $this->latitudesAndLongitudesFromGeohash()['lng'];
    }

    /**
     * @param float[]|bool[]|int[] $latitudes
     * @param float[]|bool[]|int[] $longitudes
     */
    protected function setMinMaxFromLatitudesLongitudes(array $latitudes, array $longitudes)
    {
        $latitudes = array_filter($latitudes);
        $longitudes = array_filter($longitudes);

        if ($latitudes !== [] && $longitudes !== []) {
            $MinCoordinate = new Coordinate([min($latitudes), min($longitudes)]);
            /** @var Geohash $geohashObj */
            $geohashObj = new Geohash()->encode($MinCoordinate, self::BOUNDARIES_GEOHASH_PRECISION);
            $this->Data[self::MIN] = $geohashObj->getGeohash();

            $MaxCoordinate = new Coordinate([max($latitudes), max($longitudes)]);
            /** @var Geohash $geohashObj */
            $geohashObj = new Geohash()->encode($MaxCoordinate, self::BOUNDARIES_GEOHASH_PRECISION);
            $this->Data[self::MAX] = $geohashObj->getGeohash();
        } else {
            $this->setMinMaxToNull();
        }
    }

    /**
     * Set min/max to null
     */
    protected function setMinMaxToNull()
    {
        $this->Data[self::MIN] = null;
        $this->Data[self::MAX] = null;
    }

    /**
     * Synchronize start- and endpoint
     */
    protected function synchronizeStartAndEndpoint()
    {
        $this->Data[self::STARTPOINT] = $this->findStartpoint();
        $this->Data[self::ENDPOINT] = $this->findEndpoint();

        if ($this->Data[self::STARTPOINT] === null) {
            $this->Data[self::GEOHASHES] = [];
        }
    }

    protected function findStartpoint(): ?string
    {
        return $this->findFirstNonNullGeohash($this->Data[self::GEOHASHES]);
    }

    protected function findEndpoint(): ?string
    {
        return $this->findFirstNonNullGeohash(array_reverse($this->Data[self::GEOHASHES]));
    }

    protected function findFirstNonNullGeohash(array $geohashes): ?string
    {
        /** @var Geohash $nullGeohashObj */
        $nullGeohashObj = new Geohash()->encode(new Coordinate([0, 0]), self::BOUNDARIES_GEOHASH_PRECISION);
        $nullGeohash = $nullGeohashObj->getGeohash();

        foreach ($geohashes as $geohash) {
            $geohash = substr($geohash, 0, self::BOUNDARIES_GEOHASH_PRECISION);

            if ($geohash != $nullGeohash) {
                return $geohash;
            }
        }

        return null;
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
    public function at(int $index, $key)
    {
        return $this->Data[$key][$index];
    }

    public function name(): string
    {
        return $this->Data[self::NAME];
    }

    /**
     * Cities as array
     */
    public function citiesAsArray(): array
    {
        return explode(self::CITIES_SEPARATOR, $this->Data[self::CITIES]);
    }

    public function distance(): float
    {
        return (float) $this->Data[self::DISTANCE];
    }

    public function elevation(): int
    {
        return (int) $this->Data[self::ELEVATION];
    }

    public function elevationUp(): int
    {
        return (int) $this->Data[self::ELEVATION_UP];
    }

    public function elevationDown(): int
    {
        return (int) $this->Data[self::ELEVATION_DOWN];
    }

    /**
     * Geohashes
     */
    public function geohashes(): array
    {
        return $this->Data[self::GEOHASHES];
    }

    /**
     * Has position data?
     */
    public function hasPositionData(): bool
    {
        return $this->has(self::GEOHASHES);
    }

    /**
     * Has geohash data?
     */
    public function hasGeohashes(): bool
    {
        return $this->has(self::GEOHASHES);
    }

    /**
     * Original elevations
     */
    public function elevationsOriginal(): array
    {
        return $this->Data[self::ELEVATIONS_ORIGINAL];
    }

    /**
     * Corrected elevations
     */
    public function elevationsCorrected(): array
    {
        return $this->Data[self::ELEVATIONS_CORRECTED];
    }

    /**
     * Strategy name of elevation correction
     */
    public function elevationsSource(): string
    {
        return $this->Data[self::ELEVATIONS_SOURCE];
    }

    /**
     * Barometric device
     */
    public function altitudeBarometric(): string
    {
        return $this->Data[self::ALTITUDE_BAROMETRIC];
    }

    public function min(): CoordinateInterface
    {
        $geohash = $this->Data[self::MIN];
        return $this->coordinate($geohash);
    }

    public function max(): CoordinateInterface
    {
        $geohash = $this->Data[self::MAX];
        return $this->coordinate($geohash);
    }

    /**
     * Elevations array (corrected or original)
     */
    public function elevations(): array
    {
        if ($this->hasCorrectedElevations()) {
            return $this->elevationsCorrected();
        }

        if ($this->hasOriginalElevations()) {
            return $this->elevationsOriginal();
        }

        return [];
    }

    public function hasElevations(): bool
    {
        return $this->hasOriginalElevations() || $this->hasCorrectedElevations();
    }

    public function hasOriginalElevations(): bool
    {
        return isset($this->Data[self::ELEVATIONS_ORIGINAL]) && ! empty($this->Data[self::ELEVATIONS_ORIGINAL]);
    }

    public function hasCorrectedElevations(): bool
    {
        return isset($this->Data[self::ELEVATIONS_CORRECTED]) && ! empty($this->Data[self::ELEVATIONS_CORRECTED]);
    }

    /**
     * Is in routenet?
     */
    public function inRoutenet(): bool
    {
        return $this->Data[self::IN_ROUTENET] == 1;
    }

    /**
     * Calculate distance between two coordinates
     */
    public static function gpsDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $rad1 = deg2rad($lat1);
        $rad2 = deg2rad($lat2);
        $dist = sin($rad1) * sin($rad2) + cos($rad1) * cos($rad2) * cos(deg2rad($lon1 - $lon2));
        $dist = acos($dist);
        $dist = rad2deg($dist);

        $miles = $dist * 60 * 1.1515;

        if (is_nan($miles)) {
            return 0;
        }

        return $miles * 1.609344;
    }

    private function coordinate(string $geohash): CoordinateInterface
    {
        return $this->decodedGeohash($geohash)
            ->getCoordinate();
    }

    private function decodedGeohash(string $geohash): Geohash
    {
        /** @var Geohash $decoded */
        $decoded = new Geohash()->decode($geohash);

        return $decoded;
    }
}
