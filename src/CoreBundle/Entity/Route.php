<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Geohash\Geohash;
use Runalyze\Bundle\CoreBundle\Entity\Adapter\RouteAdapter;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Calculation\Route\GeohashLine;

/**
 * @ORM\Table(name="route")
 * @ORM\Entity(repositoryClass="Runalyze\Bundle\CoreBundle\Entity\RouteRepository")
 * @ORM\EntityListeners({"Runalyze\Bundle\CoreBundle\EntityListener\RouteListener"})
 * @ORM\HasLifecycleCallbacks()
 */
class Route implements AccountRelatedEntityInterface
{
    /**
     * @var int
     */
    public const PATH_GEOHASH_PRECISION = 12;

    /**
     * @var int
     */
    public const BOUNDARIES_GEOHASH_PRECISION = 10;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", precision=10, nullable=false, options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false, options={"default":""})
     */
    private $name = '';

    /**
     * @var string
     *
     * @ORM\Column(name="cities", type="string", length=255, nullable=false, options={"default":""})
     */
    private $cities = '';

    /**
     * @var float [km]
     *
     * @ORM\Column(name="distance", type="casted_decimal_2", precision=6, scale=2, options={"unsigned":true})
     */
    private $distance = 0.0;

    /**
     * @var int [m]
     *
     * @ORM\Column(name="elevation", type="smallint", nullable=false, options={"unsigned":true})
     */
    private $elevation = 0;

    /**
     * @var int [m]
     *
     * @ORM\Column(name="elevation_up", type="smallint", nullable=false, options={"unsigned":true})
     */
    private $elevationUp = 0;

    /**
     * @var int [m]
     *
     * @ORM\Column(name="elevation_down", type="smallint", nullable=false, options={"unsigned":true})
     */
    private $elevationDown = 0;

    /**
     * @var array|null
     *
     * @ORM\Column(name="geohashes", type="geohash_array", nullable=true)
     */
    private $geohashes;

    /**
     * @var array|null [m]
     *
     * @ORM\Column(name="elevations_original", type="pipe_array", nullable=true)
     */
    private $elevationsOriginal;

    /**
     * @var array|null [m]
     *
     * @ORM\Column(name="elevations_corrected", type="pipe_array", nullable=true)
     */
    private $elevationsCorrected;

    /**
     * @var string
     *
     * @ORM\Column(name="elevations_source", type="string", length=255, nullable=false, options={"default" = ""})
     */
    private $elevationsSource = '';

    /**
     * @var bool
     *
     * @ORM\Column(name="altitude_barometric", type="boolean")
     */
    private $altitudeBarometric = false;

    /**
     * @var string|null [geohash]
     *
     * @ORM\Column(name="startpoint", type="string", length=10, nullable=true, options={"fixed" = true})
     */
    private $startpoint;

    /**
     * @var string|null [geohash]
     *
     * @ORM\Column(name="endpoint", type="string", length=10, nullable=true, options={"fixed" = true})
     */
    private $endpoint;

    /**
     * @var string|null [geohash]
     *
     * @ORM\Column(name="min", type="string", length=10, nullable=true, options={"fixed" = true})
     */
    private $min;

    /**
     * @var string|null [geohash]
     *
     * @ORM\Column(name="max", type="string", length=10, nullable=true, options={"fixed" = true})
     */
    private $max;

    /**
     * @var bool
     *
     * @ORM\Column(name="in_routenet", type="boolean")
     */
    private $inRoutenet = false;

    /**
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity="Account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="accountid", referencedColumnName="id", nullable=false)
     * })
     */
    private $account;

    /**
     * @var bool
     *
     * @ORM\Column(name="`lock`", type="boolean")
     */
    private $lock = false;

    /**
     * @var bool
     */
    private $areMinMaxSynchronized = true;

    /**
     * @var RouteAdapter
     */
    private $Adapter;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name): self
    {
        $this->name = $name ?: '';
        $this->cities = $name ?: '';

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $cities
     */
    public function setCities($cities): self
    {
        $this->cities = $cities ?: '';
        $this->name = $cities ?: '';

        return $this;
    }

    /**
     * @return string
     */
    public function getCities()
    {
        return $this->cities;
    }

    /**
     * @param float $distance [km]
     */
    public function setDistance($distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    /**
     * @return float [km]
     */
    public function getDistance()
    {
        return $this->distance;
    }

    /**
     * @param int $elevation [m]
     */
    public function setElevation($elevation): self
    {
        $this->elevation = $elevation;

        return $this;
    }

    /**
     * @return int [m]
     */
    public function getElevation()
    {
        return $this->elevation;
    }

    /**
     * @param int $elevationUp [m]
     */
    public function setElevationUp($elevationUp): self
    {
        $this->elevationUp = $elevationUp;

        return $this;
    }

    /**
     * @return int [m]
     */
    public function getElevationUp()
    {
        return $this->elevationUp;
    }

    /**
     * @param int $elevationDown [m]
     */
    public function setElevationDown($elevationDown): self
    {
        $this->elevationDown = $elevationDown;

        return $this;
    }

    /**
     * @return int [m]
     */
    public function getElevationDown()
    {
        return $this->elevationDown;
    }

    /**
     * @param int $altitudeBarometric boolean
     */
    public function setAltitudeBarometric($altitudeBarometric): self
    {
        $this->altitudeBarometric = $altitudeBarometric;

        return $this;
    }

    /**
     * @return bool
     */
    public function getAltitudeBarometric()
    {
        return $this->altitudeBarometric;
    }

    public function setGeohashes(?array $geohashes = null): self
    {
        $this->areMinMaxSynchronized = false;
        $this->geohashes = $geohashes;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getGeohashes()
    {
        return $this->geohashes;
    }

    public function hasGeohashes(): bool
    {
        return $this->geohashes !== null;
    }

    /**
     * @param array<int, mixed> $latitudes
     * @param array<int, mixed> $longitudes
     */
    public function setLatitudesAndLongitudes(array $latitudes, array $longitudes): self
    {
        $size = count($latitudes);

        if ($size !== count($longitudes)) {
            throw new \InvalidArgumentException('Latitude and longitude array must be of same length.');
        }

        $latitudes = array_map(function ($value): float { return ($value == '') ? 0.0 : (float) $value; }, $latitudes);
        $longitudes = array_map(function ($value): float { return ($value == '') ? 0.0 : (float) $value; }, $longitudes);

        $this->areMinMaxSynchronized = false;
        $this->geohashes = [];

        for ($i = 0; $i < $size; ++$i) {
            $this->geohashes[] = (new Geohash())->encode(
                new Coordinate([$latitudes[$i], $longitudes[$i]]),
                self::PATH_GEOHASH_PRECISION
            )->getGeohash();
        }

        return $this;
    }

    /**
     * @return array [[lat1, lat2, ...], [lng1, lng2, ...]]
     */
    public function getLatitudesAndLongitudes(): array
    {
        $coordinates = [[], []];

        if ($this->geohashes !== null) {
            $size = count($this->geohashes);

            for ($i = 0; $i < $size; $i++) {
                $coordinate = (new Geohash())->decode($this->geohashes[$i])->getCoordinate();
                $coordinates[0][] = round($coordinate->getLatitude(), 6);
                $coordinates[1][] = round($coordinate->getLongitude(), 6);
            }
        }

        return $coordinates;
    }

    /**
     * @return array
     */
    public function getLatitudes()
    {
        return $this->getLatitudesAndLongitudes()[0];
    }

    /**
     * @return array
     */
    public function getLongitudes()
    {
        return $this->getLatitudesAndLongitudes()[1];
    }

    /**
     * @param array|null $elevationsOriginal [m]
     */
    public function setElevationsOriginal(?array $elevationsOriginal = null): self
    {
        $this->elevationsOriginal = $elevationsOriginal;

        return $this;
    }

    /**
     * @return array|null [m]
     */
    public function getElevationsOriginal()
    {
        return $this->elevationsOriginal;
    }

    /**
     * @param array|null $elevationsCorrected [m]
     */
    public function setElevationsCorrected(?array $elevationsCorrected = null): self
    {
        $this->elevationsCorrected = $elevationsCorrected;

        return $this;
    }

    /**
     * @return array|null [m]
     */
    public function getElevationsCorrected()
    {
        return $this->elevationsCorrected;
    }

    /**
     * @return array|null [m]
     */
    public function getElevations()
    {
        if ($this->elevationsCorrected !== null) {
            return $this->elevationsCorrected;
        }

        return $this->elevationsOriginal;
    }

    public function hasElevations(): bool
    {
        return $this->elevationsCorrected !== null || $this->elevationsOriginal !== null;
    }

    public function hasOriginalElevations(): bool
    {
        return $this->elevationsOriginal !== null;
    }

    public function hasCorrectedElevations(): bool
    {
        return $this->elevationsCorrected !== null;
    }

    /**
     * @param string|null $elevationsSource
     */
    public function setElevationsSource($elevationsSource): self
    {
        $this->elevationsSource = $elevationsSource;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getElevationsSource()
    {
        return $this->elevationsSource;
    }

    /**
     * @param string|null $startpoint [geohash]
     */
    public function setStartpoint($startpoint): self
    {
        $this->startpoint = $startpoint;

        return $this;
    }

    /**
     * @return string|null [geohash]
     */
    public function getStartpoint()
    {
        return $this->startpoint;
    }

    /**
     * @param string|null $endpoint [geohash]
     */
    public function setEndpoint($endpoint): self
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * @return string|null [geohash]
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @param string|null $min [geohash]
     */
    public function setMin($min): self
    {
        $this->min = $min;

        return $this;
    }

    /**
     * @return string|null [geohash]
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * @param string|null $max [geohash]
     */
    public function setMax($max): self
    {
        $this->max = $max;

        return $this;
    }

    /**
     * @return string|null [geohash]
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * @param bool $flag
     */
    public function setInRoutenet($flag): self
    {
        $this->inRoutenet = (bool) $flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function getInRoutenet()
    {
        return $this->inRoutenet;
    }

    public function setAccount(Account $account): self
    {
        $this->account = $account;

        return $this;
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param bool $flag
     */
    public function setLock($flag): self
    {
        $this->lock = (bool) $flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function getLock()
    {
        return $this->lock;
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        return $this->lock;
    }

    public function isEmpty(): bool
    {
        return
            $this->name == '' &&
            $this->cities == '' &&
            ($this->geohashes === null || empty($this->geohashes)) &&
            ($this->elevationsOriginal === null || empty($this->elevationsOriginal)) &&
            ($this->elevationsCorrected === null || empty($this->elevationsCorrected))
        ;
    }

    /**
     * @return bool
     */
    public function areMinMaxGeohashSynchronized()
    {
        return $this->areMinMaxSynchronized;
    }

    /**
     * @return RouteAdapter
     */
    public function getAdapter()
    {
        if ($this->Adapter === null) {
            $this->Adapter = new RouteAdapter($this);
        }

        return $this->Adapter;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function synchronize()
    {
        $this->setStartEndGeohashes();
        $this->synchronizeMinMaxGeohashIfRequired();

        if ($this->elevationsCorrected === null) {
            $this->elevationsSource = '';
        }
    }

    public function synchronizeMinMaxGeohashIfRequired()
    {
        if (! $this->areMinMaxSynchronized) {
            $this->setMinMaxGeohashes();
        }
    }

    public function setMinMaxGeohashes()
    {
        $this->min = null;
        $this->max = null;

        $coordinates = $this->getLatitudesAndLongitudes();
        $latitudes = array_filter($coordinates[0]);
        $longitudes = array_filter($coordinates[1]);

        if (! empty($latitudes) && ! empty($longitudes)) {
            $minCoordinate = new Coordinate([min($latitudes), min($longitudes)]);
            $maxCoordinate = new Coordinate([max($latitudes), max($longitudes)]);

            $this->min = (new Geohash())->encode($minCoordinate, self::BOUNDARIES_GEOHASH_PRECISION)->getGeohash();
            $this->max = (new Geohash())->encode($maxCoordinate, self::BOUNDARIES_GEOHASH_PRECISION)->getGeohash();
        }

        $this->areMinMaxSynchronized = true;
    }

    public function setStartEndGeohashes()
    {
        if ($this->geohashes !== null) {
            $this->startpoint = GeohashLine::findFirstNonNullGeohash($this->geohashes, self::BOUNDARIES_GEOHASH_PRECISION);
            $this->endpoint = GeohashLine::findFirstNonNullGeohash(array_reverse($this->geohashes), self::BOUNDARIES_GEOHASH_PRECISION);

            if ($this->startpoint === null) {
                $this->geohashes = null;
            }
        } else {
            $this->startpoint = null;
            $this->endpoint = null;
        }
    }
}
