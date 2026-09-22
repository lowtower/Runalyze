<?php

namespace Runalyze\Bundle\CoreBundle\Model\Trackdata\Climb;

class Climb
{
    /**
     * @var float [km]
     */
    protected float $Distance;

    /**
     * @var ?int [m]
     */
    protected ?int $AltitudeAtTop = null;

    protected ?ClimbProfile $ClimbProfile = null;

    /**
     * @param float $distance [km]
     * @param int $Elevation [m]
     */
    public function __construct(
        float $distance,
        protected int $Elevation,
        protected ?int $TrackdataStartIndex = null,
        protected ?int $TrackdataEndIndex = null
    ) {
        if ($distance <= 0.0) {
            throw new \InvalidArgumentException(sprintf('Climb distance must be positive, %f given.', $distance));
        }

        $this->Distance = $distance;
    }

    public function getTrackdataStartIndex(): ?int
    {
        return $this->TrackdataStartIndex;
    }

    public function knowsTrackdataStartIndex(): bool
    {
        return $this->TrackdataStartIndex !== null;
    }

    public function getTrackdataEndIndex(): ?int
    {
        return $this->TrackdataEndIndex;
    }

    public function knowsTrackdataEndIndex(): bool
    {
        return $this->TrackdataEndIndex !== null;
    }

    /**
     * @return float [km]
     */
    public function getDistance(): float
    {
        return $this->Distance;
    }

    /**
     * @return int [m]
     */
    public function getElevation(): int
    {
        return $this->Elevation;
    }

    public function getGradient(): float
    {
        return $this->Elevation / $this->Distance / 1000;
    }

    /**
     * @param int $altitude [m]
     */
    public function setAltitudeAtTop(int $altitude): self
    {
        $this->AltitudeAtTop = $altitude;

        return $this;
    }

    /**
     * @return ?int [m]
     */
    public function getAltitudeAtTop(): ?int
    {
        return $this->AltitudeAtTop;
    }

    public function knowsAltitudeAtTop(): bool
    {
        return $this->AltitudeAtTop !== null;
    }

    public function setClimbProfile(ClimbProfile $profile): self
    {
        $this->ClimbProfile = $profile;

        return $this;
    }

    public function getClimbProfile(): ?ClimbProfile
    {
        return $this->ClimbProfile;
    }

    public function knowsClimbProfile(): bool
    {
        return $this->ClimbProfile instanceof \Runalyze\Bundle\CoreBundle\Model\Trackdata\Climb\ClimbProfile;
    }
}
