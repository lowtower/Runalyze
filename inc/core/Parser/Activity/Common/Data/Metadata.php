<?php

namespace Runalyze\Parser\Activity\Common\Data;

use Runalyze\Util\LocalTime;

class Metadata
{
    /**
     * @var int|null timestamp in local time, i.e. assuming the activity was in utc
     */
    protected ?int $Timestamp = null;

    /**
     * @var int|null timezone offset in minutes
     */
    protected ?int $TimezoneOffset = null;

    protected string $SportName = '';

    protected ?int $InternalSportId = null;

    protected string $TypeName = '';

    protected string $Creator = '';

    protected string $CreatorDetails = '';

    /**
     * @var mixed
     */
    protected $ActivityId;

    protected string $Description = '';

    protected string $Notes = '';

    protected string $RouteDescription = '';

    /**
     * @var string[]
     */
    protected array $EquipmentNames = [];

    // TSC: save the uploaded-filename
    protected string $UploadedFilename = '';

    // TSC: only internal for mapping to
    protected string $WorkoutName = '';

    /**
     * @param null|int $timestampAssumingUTC timestamp in local time, i.e. assuming the activity was in utc
     * @param null|int $timezoneOffset [min]
     */
    public function setTimestamp(?int $timestampAssumingUTC, ?int $timezoneOffset = null)
    {
        $this->Timestamp = $timestampAssumingUTC;
        $this->TimezoneOffset = $timezoneOffset;
    }

    /**
     * @return int|null timestamp in local time, i.e. assuming the activity was in utc
     */
    public function getTimestamp(): ?int
    {
        return $this->Timestamp;
    }

    /**
     * @param null|int $timezoneOffset [min]
     */
    public function setTimezoneOffset(?int $timezoneOffset)
    {
        $this->TimezoneOffset = $timezoneOffset;
    }

    /**
     * @return int|null timezone offset in minutes
     */
    public function getTimezoneOffset(): ?int
    {
        return $this->TimezoneOffset;
    }

    public function interpretTimestampAsServerTime(int $timestamp)
    {
        $this->TimezoneOffset = (int) round((new \DateTime())->setTimestamp($timestamp)->getOffset() / 60);
        $this->Timestamp = LocalTime::fromServerTime($timestamp)->getTimestamp();
    }

    public function setTimestampAndTimezoneOffsetFrom(string $stringWithTimezoneInformation)
    {
        try {
            $dateTime = new \DateTime($stringWithTimezoneInformation);

            $this->setTimestamp(
                $dateTime->getTimestamp() + $dateTime->getOffset(),
                round($dateTime->getOffset() / 60)
            );
        } catch (\Exception $exception) {
            // Invalid date
        }
    }

    /**
     * @param string $string if this string ends with 'Z', its interpreted as in server timezone
     */
    public function setTimestampAndTimezoneOffsetWithUtcFixFrom(string $string)
    {
        if (substr($string, -1) == 'Z') {
            $localTimestamp = LocalTime::fromServerTime(strtotime(substr($string, 0, -1) . ' UTC'))->getTimestamp();

            $this->setTimestamp(
                $localTimestamp,
                round((new \DateTime())->setTimestamp($localTimestamp)->getOffset() / 60)
            );
        } else {
            $this->setTimestampAndTimezoneOffsetFrom($string);
        }
    }

    public function setSportName(string $sportName)
    {
        $this->SportName = $sportName;
    }

    public function getSportName(): string
    {
        return $this->SportName;
    }

    public function setInternalSportId(?int $id)
    {
        $this->InternalSportId = $id;
    }

    public function getInternalSportId(): ?int
    {
        return $this->InternalSportId;
    }

    public function setTypeName(string $typeName)
    {
        $this->TypeName = $typeName;
    }

    public function getTypeName(): string
    {
        return $this->TypeName;
    }

    public function setCreator(string $creator, string $creatorDetails = '')
    {
        $this->Creator = $creator;
        $this->CreatorDetails = $creatorDetails;
    }

    public function getCreator(): string
    {
        return $this->Creator;
    }

    public function getCreatorDetails(): string
    {
        return $this->CreatorDetails;
    }

    /**
     * @param mixed $value any vendor-specific identifier
     */
    public function setActivityId($value)
    {
        $this->ActivityId = $value;
    }

    /**
     * @return mixed
     */
    public function getActivityId()
    {
        return $this->ActivityId;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->Description = $description;
    }

    public function getDescription(): string
    {
        return $this->Description;
    }

    public function setNotes(string $notes)
    {
        $this->Notes = $notes;
    }

    public function getNotes(): string
    {
        return $this->Notes;
    }

    public function setRouteDescription(string $description)
    {
        $this->RouteDescription = $description;
    }

    public function getRouteDescription(): string
    {
        return $this->RouteDescription;
    }

    public function addEquipment(string $name)
    {
        $this->EquipmentNames[] = $name;
    }

    /**
     * @return string[]
     */
    public function getEquipmentNames(): array
    {
        return $this->EquipmentNames;
    }

    public function setUploadedFilename(string $filename)
    {
        $this->UploadedFilename = $filename;
    }

    public function getUploadedFilename(): string
    {
        return $this->UploadedFilename;
    }

    public function setWorkoutName(string $workoutName)
    {
        $this->WorkoutName = $workoutName;
    }

    public function getWorkoutName(): string
    {
        return $this->WorkoutName;
    }
}
