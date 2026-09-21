<?php

namespace Runalyze\Bundle\CoreBundle\Model\Trackdata\Climb;

class Climb
{
    /**
     * @var float [km]
     */
    protected $Distance;

    /**
     * @var int|null [m]
     */
    protected $AltitudeAtTop;

    /**
     * @var ClimbProfile|null
     */
    protected $ClimbProfile;

    /**
     * @param float $distance [km]
     * @param int $Elevation [m]
     * @param int|null $TrackdataStartIndex
     * @param int|null $TrackdataEndIndex
     */
    public function __construct(
        $distance,
        protected $Elevation,
        protected $TrackdataStartIndex = null,
        protected $TrackdataEndIndex = null
    ) {
        if ($distance <= 0.0) {
            throw new \InvalidArgumentException(sprintf('Climb distance must be positive, %f given.', $distance));
        }

        $this->Distance = $distance;
    }

    /**
     * @return int|null
     */
    public function getTrackdataStartIndex()
    {
        return $this->TrackdataStartIndex;
    }

    public function knowsTrackdataStartIndex(): bool
    {
        return $this->TrackdataStartIndex !== null;
    }

    /**
     * @return int|null
     */
    public function getTrackdataEndIndex()
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
    public function getDistance()
    {
        return $this->Distance;
    }

    /**
     * @return int [m]
     */
    public function getElevation()
    {
        return $this->Elevation;
    }

    /**
     * @return float
     */
    public function getGradient(): int|float
    {
        return $this->Elevation / $this->Distance / 1000;
    }

    /**
     * @param int $altitude [m]
     */
    public function setAltitudeAtTop($altitude): self
    {
        $this->AltitudeAtTop = $altitude;

        return $this;
    }

    /**
     * @return int|null [m]
     */
    public function getAltitudeAtTop()
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

    /**
     * @return null|ClimbProfile
     */
    public function getClimbProfile()
    {
        return $this->ClimbProfile;
    }

    public function knowsClimbProfile(): bool
    {
        return $this->ClimbProfile !== null;
    }
}
