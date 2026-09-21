<?php

namespace Runalyze\Bundle\CoreBundle\Component\Activity;

use Runalyze\Bundle\CoreBundle\Services\Import\WeatherDataToActivityConverter;
use Runalyze\Parameter\Application\Timezone;
use Runalyze\Service\WeatherForecast\Location;
use Runalyze\Util\LocalTime;

class ActivityContextAdapter
{
    public function __construct(
        protected \Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext $Context,
        protected \Runalyze\Bundle\CoreBundle\Services\Import\WeatherForecast $WeatherForecast,
        protected \Runalyze\Bundle\CoreBundle\Services\Import\DuplicateFinder $DuplicateFinder,
        protected \Runalyze\Service\RouteNameEvaluation\RouteNameEval $RouteNameEval
    ) {
    }

    /**
     * @param object $object
     */
    protected function getStrategyName($object): string
    {
        $fullClassName = $object::class;

        return substr($fullClassName, strrpos($fullClassName, '\\') + 1);
    }

    public function guessWeatherConditions(string $defaultLocationName, $account): void
    {
        // #TSC if not outside, no weather needed
        if (! $this->Context->getSport()->getOutside()) {
            return;
        }

        $location = new Location();
        $location->setLocationName($defaultLocationName);

        // #TSC set the time to the mid of the activity
        $time = new LocalTime($this->Context->getActivity()->getTime());
        // we add here the elapsed time, not the duration; half this time to set the mid of the activity
        $time->add(new \DateInterval('PT' . round($this->Context->getActivity()->getElapsedTime() / 2) . 'S'));

        $location->setDateTime($time);

        if ($this->Context->hasRoute() && $this->Context->getRoute()->hasGeohashes()) {
            $this->Context->getRoute()
                ->setStartEndGeohashes();

            $location->setGeohash($this->Context->getRoute()->getStartpoint());
        }

        // #TSC set also the timezone for fetch historical time based data
        $timezone = (int) $account->getTimezone();
        $timezoneName = Timezone::getFullNameByEnum($timezone);
        $location->setTimezone($timezoneName);

        // if we have no valid location-object, no forecast is meaningful
        if (! $location->isValid()) {
            return;
        }

        $weather = $this->WeatherForecast->loadForecast($location);

        if ($weather instanceof \Runalyze\Parser\Activity\Common\Data\WeatherData) {
            $converter = new WeatherDataToActivityConverter();
            $converter->setActivityWeatherDataFor($this->Context->getActivity(), $weather);
        }
    }

    /**
     * #TSC get details of the route and set it on the relevant activity fields.
     */
    public function guessRouteDetails(): bool
    {
        $activity = $this->Context->getActivity();
        if ($activity->hasRoute() && $activity->getRoute()->hasGeohashes()) {
            $result = $this->RouteNameEval->evaluate($activity->getSport(), $activity->getRoute(), $activity->getDistance());

            if ($result != null) {
                // set the name not directly on route, because on Training there is also a "route(name)" attribute
                $activity->setRouteName($result->getNames());
                $activity->setNotes($result->appendNotes($activity->getNotes()));
                return true;
            }
        }

        return false;
    }

    public function isPossibleDuplicate(): bool
    {
        return $this->DuplicateFinder->isPossibleDuplicate($this->Context->getActivity());
    }
}
