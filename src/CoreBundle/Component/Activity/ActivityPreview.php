<?php

namespace Runalyze\Bundle\CoreBundle\Component\Activity;

use DateTime;
use Error;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;

class ActivityPreview
{
    protected ?int $ActivityId = null;

    protected string $Title;

    protected DateTime $DateTime;

    /**
     * @var float [s]
     */
    protected float $Duration;

    /**
     * @var ?float [km]
     */
    protected ?float $Distance = null;

    protected string $SportName = '';

    protected string $SportIcon = '';

    protected bool $HasHeartRate;

    protected bool $HasRoute;

    protected bool $HasRounds;

    public function __construct(
        Training $activity,
        protected bool $IsPossibleDuplicate = false,
        ?ActivityDecorator $decorator = null
    ) {
        $this->setSimplePropertiesFromActivity($activity);
        $this->setPropertiesFromSport($this->getSportOrNull($activity));
        $this->setPropertiesFromDecorator(
            $decorator ?? new ActivityDecorator(new ActivityContext($activity))
        );
    }

    private function getSportOrNull(Training $activity): ?Sport
    {
        try {
            return $activity->getSport();
        } catch (Error) {
            return null;
        }
    }

    protected function setSimplePropertiesFromActivity(Training $activity): void
    {
        $this->ActivityId = $activity->getId();
        $this->Duration = $activity->getS();
        $this->Distance = $activity->getDistance();
        $this->HasHeartRate = $activity->getPulseAvg() !== null;
        $this->HasRoute = $activity->hasRoute();
        $this->HasRounds = ! $activity->getSplits()
            ->isEmpty();
    }

    protected function setPropertiesFromSport(?Sport $sport = null): void
    {
        if (! $sport instanceof Sport) {
            return;
        }

        $this->SportName = $sport->getName();
        $this->SportIcon = $sport->getImg();
    }

    protected function setPropertiesFromDecorator(ActivityDecorator $decorator): void
    {
        $this->Title = $decorator->getTitle(true);
        $this->DateTime = $decorator->getDateTime();
    }

    public function setPossibleDuplicate(bool $flag): void
    {
        $this->IsPossibleDuplicate = $flag;
    }

    public function getActivityId(): ?int
    {
        return $this->ActivityId;
    }

    public function getTitle(): string
    {
        return $this->Title;
    }

    public function getDateTime(): DateTime
    {
        return $this->DateTime;
    }

    /**
     * @return float [s]
     */
    public function getDuration(): float
    {
        return $this->Duration;
    }

    /**
     * @return ?float [km]
     */
    public function getDistance(): ?float
    {
        return $this->Distance;
    }

    public function hasDistance(): bool
    {
        return $this->Distance !== null;
    }

    public function getSportName(): string
    {
        return $this->SportName;
    }

    public function getSportIcon(): string
    {
        return $this->SportIcon;
    }

    public function hasHeartRate(): bool
    {
        return $this->HasHeartRate;
    }

    public function hasRoute(): bool
    {
        return $this->HasRoute;
    }

    public function hasRounds(): bool
    {
        return $this->HasRounds;
    }

    public function isPossibleDuplicate(): bool
    {
        return $this->IsPossibleDuplicate;
    }
}
