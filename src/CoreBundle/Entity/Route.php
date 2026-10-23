<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Geohash\Geohash;
use Runalyze\Bundle\CoreBundle\Entity\Adapter\RouteAdapter;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\EntityListener\RouteListener;
use Runalyze\Bundle\CoreBundle\Repository\RouteRepository;
use Runalyze\Calculation\Route\GeohashLine;

#[ORM\Table(name: 'route')]
#[ORM\Entity(repositoryClass: RouteRepository::class)]
#[ORM\EntityListeners([RouteListener::class])]
#[ORM\HasLifecycleCallbacks]
class Route implements AccountRelatedEntityInterface
{
    public const int PATH_GEOHASH_PRECISION = 12;

    public const int BOUNDARIES_GEOHASH_PRECISION = 10;

    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER, precision: 10, nullable: false, options: [
        'unsigned' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: false, options: [
        'default' => '',
    ])]
    private string $name = '';

    #[ORM\Column(name: 'cities', type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: false, options: [
        'default' => '',
    ])]
    private string $cities = '';

    /**
     * @var float [km]
     */
    #[ORM\Column(name: 'distance', type: 'casted_decimal_2', precision: 6, scale: 2, options: [
        'unsigned' => true,
    ])]
    private float $distance = 0.0;

    /**
     * @var int [m]
     */
    #[ORM\Column(name: 'elevation', type: \Doctrine\DBAL\Types\Types::SMALLINT, nullable: false, options: [
        'unsigned' => true,
    ])]
    private int $elevation = 0;

    /**
     * @var int [m]
     */
    #[ORM\Column(name: 'elevation_up', type: \Doctrine\DBAL\Types\Types::SMALLINT, nullable: false, options: [
        'unsigned' => true,
    ])]
    private int $elevationUp = 0;

    /**
     * @var int [m]
     */
    #[ORM\Column(name: 'elevation_down', type: \Doctrine\DBAL\Types\Types::SMALLINT, nullable: false, options: [
        'unsigned' => true,
    ])]
    private int $elevationDown = 0;

    #[ORM\Column(name: 'geohashes', type: 'geohash_array', nullable: true)]
    private ?array $geohashes = null;

    /**
     * @var ?array [m]
     */
    #[ORM\Column(name: 'elevations_original', type: 'pipe_array', nullable: true)]
    private ?array $elevationsOriginal = null;

    /**
     * @var ?array [m]
     */
    #[ORM\Column(name: 'elevations_corrected', type: 'pipe_array', nullable: true)]
    private ?array $elevationsCorrected = null;

    #[ORM\Column(name: 'elevations_source', type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true, options: [
        'default' => '',
    ])]
    private ?string $elevationsSource = null;

    #[ORM\Column(name: 'altitude_barometric', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private bool $altitudeBarometric = false;

    /**
     * @var ?string [geohash]
     */
    #[ORM\Column(name: 'startpoint', type: \Doctrine\DBAL\Types\Types::STRING, length: 10, nullable: true, options: [
        'fixed' => true,
    ])]
    private ?string $startpoint = null;

    /**
     * @var ?string [geohash]
     */
    #[ORM\Column(name: 'endpoint', type: \Doctrine\DBAL\Types\Types::STRING, length: 10, nullable: true, options: [
        'fixed' => true,
    ])]
    private ?string $endpoint = null;

    /**
     * @var ?string [geohash]
     */
    #[ORM\Column(name: 'min', type: \Doctrine\DBAL\Types\Types::STRING, length: 10, nullable: true, options: [
        'fixed' => true,
    ])]
    private ?string $min = null;

    /**
     * @var ?string [geohash]
     */
    #[ORM\Column(name: 'max', type: \Doctrine\DBAL\Types\Types::STRING, length: 10, nullable: true, options: [
        'fixed' => true,
    ])]
    private ?string $max = null;

    #[ORM\Column(name: 'in_routenet', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private bool $inRoutenet = false;

    #[ORM\JoinColumn(name: 'accountid', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Account::class)]
    private Account $account;

    #[ORM\Column(name: '`lock`', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private bool $lock = false;

    private bool $areMinMaxSynchronized = true;

    private RouteAdapter $Adapter;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name ?: '';
        $this->cities = $name ?: '';

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setCities(string $cities): self
    {
        $this->cities = $cities ?: '';
        $this->name = $cities ?: '';

        return $this;
    }

    public function getCities(): string
    {
        return $this->cities;
    }

    /**
     * @param float $distance [km]
     */
    public function setDistance(float $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    /**
     * @return float [km]
     */
    public function getDistance(): float
    {
        return $this->distance;
    }

    /**
     * @param int $elevation [m]
     */
    public function setElevation(int $elevation): self
    {
        $this->elevation = $elevation;

        return $this;
    }

    /**
     * @return int [m]
     */
    public function getElevation(): int
    {
        return $this->elevation;
    }

    /**
     * @param int $elevationUp [m]
     */
    public function setElevationUp(int $elevationUp): self
    {
        $this->elevationUp = $elevationUp;

        return $this;
    }

    /**
     * @return int [m]
     */
    public function getElevationUp(): int
    {
        return $this->elevationUp;
    }

    /**
     * @param int $elevationDown [m]
     */
    public function setElevationDown(int $elevationDown): self
    {
        $this->elevationDown = $elevationDown;

        return $this;
    }

    /**
     * @return int [m]
     */
    public function getElevationDown(): int
    {
        return $this->elevationDown;
    }

    public function setAltitudeBarometric(bool $altitudeBarometric): self
    {
        $this->altitudeBarometric = $altitudeBarometric;

        return $this;
    }

    public function getAltitudeBarometric(): bool
    {
        return $this->altitudeBarometric;
    }

    public function setGeohashes(?array $geohashes = null): self
    {
        $this->areMinMaxSynchronized = false;
        $this->geohashes = $geohashes;

        return $this;
    }

    public function getGeohashes(): ?array
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
     *
     * @throws InvalidArgumentException if the latitude and longitude arrays differ in length
     */
    public function setLatitudesAndLongitudes(array $latitudes, array $longitudes): self
    {
        $size = count($latitudes);

        if ($size !== count($longitudes)) {
            throw new InvalidArgumentException('Latitude and longitude array must be of same length.');
        }

        $latitudes = array_map(fn ($value): float => ($value == '') ? 0.0 : (float) $value, $latitudes);
        $longitudes = array_map(fn ($value): float => ($value == '') ? 0.0 : (float) $value, $longitudes);

        $this->areMinMaxSynchronized = false;
        $this->geohashes = [];

        for ($i = 0; $i < $size; ++$i) {
            /** @var Geohash $geohashObj */
            $geohashObj = new Geohash()->encode(
                new Coordinate([$latitudes[$i], $longitudes[$i]]),
                self::PATH_GEOHASH_PRECISION
            );
            $this->geohashes[] = $geohashObj->getGeohash();
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
                /** @var Geohash $geohashObj */
                $geohashObj = new Geohash()->decode($this->geohashes[$i]);
                $coordinate = $geohashObj->getCoordinate();
                $coordinates[0][] = round((float) $coordinate->getLatitude(), 6);
                $coordinates[1][] = round((float) $coordinate->getLongitude(), 6);
            }
        }

        return $coordinates;
    }

    public function getLatitudes(): array
    {
        return $this->getLatitudesAndLongitudes()[0];
    }

    public function getLongitudes(): array
    {
        return $this->getLatitudesAndLongitudes()[1];
    }

    /**
     * @param ?array $elevationsOriginal [m]
     */
    public function setElevationsOriginal(?array $elevationsOriginal = null): self
    {
        $this->elevationsOriginal = $elevationsOriginal;

        return $this;
    }

    /**
     * @return ?array [m]
     */
    public function getElevationsOriginal(): ?array
    {
        return $this->elevationsOriginal;
    }

    /**
     * @param ?array $elevationsCorrected [m]
     */
    public function setElevationsCorrected(?array $elevationsCorrected = null): self
    {
        $this->elevationsCorrected = $elevationsCorrected;

        return $this;
    }

    /**
     * @return ?array [m]
     */
    public function getElevationsCorrected(): ?array
    {
        return $this->elevationsCorrected;
    }

    /**
     * @return ?array [m]
     */
    public function getElevations(): ?array
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

    public function setElevationsSource(?string $elevationsSource): self
    {
        $this->elevationsSource = $elevationsSource;

        return $this;
    }

    public function getElevationsSource(): ?string
    {
        return $this->elevationsSource;
    }

    /**
     * @param ?string $startpoint [geohash]
     */
    public function setStartpoint(?string $startpoint): self
    {
        $this->startpoint = $startpoint;

        return $this;
    }

    /**
     * @return ?string [geohash]
     */
    public function getStartpoint(): ?string
    {
        return $this->startpoint;
    }

    /**
     * @param ?string $endpoint [geohash]
     */
    public function setEndpoint(?string $endpoint): self
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * @return ?string [geohash]
     */
    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    /**
     * @param ?string $min [geohash]
     */
    public function setMin(?string $min): self
    {
        $this->min = $min;

        return $this;
    }

    /**
     * @return ?string [geohash]
     */
    public function getMin(): ?string
    {
        return $this->min;
    }

    /**
     * @param ?string $max [geohash]
     */
    public function setMax(?string $max): self
    {
        $this->max = $max;

        return $this;
    }

    /**
     * @return ?string [geohash]
     */
    public function getMax(): ?string
    {
        return $this->max;
    }

    public function setInRoutenet(bool $flag): self
    {
        $this->inRoutenet = $flag;

        return $this;
    }

    public function getInRoutenet(): bool
    {
        return $this->inRoutenet;
    }

    public function setAccount(Account $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function setLock(bool $flag): self
    {
        $this->lock = $flag;

        return $this;
    }

    public function getLock(): bool
    {
        return $this->lock;
    }

    public function isLocked(): bool
    {
        return $this->lock;
    }

    public function isEmpty(): bool
    {
        return
            $this->name === ''
            && $this->cities === ''
            && ($this->geohashes === null || empty($this->geohashes))
            && ($this->elevationsOriginal === null || empty($this->elevationsOriginal))
            && ($this->elevationsCorrected === null || empty($this->elevationsCorrected))
        ;
    }

    public function areMinMaxGeohashSynchronized(): bool
    {
        return $this->areMinMaxSynchronized;
    }

    public function getAdapter(): RouteAdapter
    {
        $this->Adapter = new RouteAdapter($this);

        return $this->Adapter;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function synchronize(): void
    {
        $this->setStartEndGeohashes();
        $this->synchronizeMinMaxGeohashIfRequired();

        if ($this->elevationsCorrected === null) {
            $this->elevationsSource = '';
        }
    }

    public function synchronizeMinMaxGeohashIfRequired(): void
    {
        if (! $this->areMinMaxSynchronized) {
            $this->setMinMaxGeohashes();
        }
    }

    public function setMinMaxGeohashes(): void
    {
        $this->min = null;
        $this->max = null;

        $coordinates = $this->getLatitudesAndLongitudes();
        $latitudes = array_filter($coordinates[0]);
        $longitudes = array_filter($coordinates[1]);

        if ($latitudes !== [] && $longitudes !== []) {
            $minCoordinate = new Coordinate([min($latitudes), min($longitudes)]);
            $maxCoordinate = new Coordinate([max($latitudes), max($longitudes)]);

            /** @var Geohash $geohashObjMin */
            $geohashObjMin = new Geohash()->encode($minCoordinate, self::BOUNDARIES_GEOHASH_PRECISION);
            $this->min = $geohashObjMin->getGeohash();
            /** @var Geohash $geohashObjMax */
            $geohashObjMax = new Geohash()->encode($maxCoordinate, self::BOUNDARIES_GEOHASH_PRECISION);
            $this->max = $geohashObjMax->getGeohash();
        }

        $this->areMinMaxSynchronized = true;
    }

    public function setStartEndGeohashes(): void
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
