<?php

namespace Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation;

use Runalyze\Athlete;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Calculation\Trimp\Calculator;
use Runalyze\Calculation\Trimp\DataCollector;

class TrimpCalculator
{
    /**
     * @param int $MaximalTrimp
     * @param int $MaximalTrimpPerHour
     */
    public function __construct(
        protected $MaximalTrimp = 10000,
        protected $MaximalTrimpPerHour = 400
    ) {
    }

    /**
     * @param int $gender enum, see \Runalyze\Profile\Athlete\Gender
     * @param int $heartRateMaximum [bpm]
     * @param int $heartRateResting [bpm]
     */
    public function calculateFor(Training $activity, $gender, $heartRateMaximum, $heartRateResting): void
    {
        $calculator = new Calculator(
            new Athlete($gender, $heartRateMaximum, $heartRateResting),
            $this->getHeartRateHistogram($activity)
        );

        $value = (int) $calculator->value();

        $this->checkIfValueIsOutOfRange($value, $activity);

        $activity->setTrimp($value);
    }

    /**
     * @return array
     */
    protected function getHeartRateHistogram(Training $activity)
    {
        if ($activity->hasTrackdata() && $activity->getTrackdata()->hasHeartrate() && $activity->getTrackdata()->hasTime()) {
            return (new DataCollector($activity->getTrackdata()->getHeartrate(), $activity->getTrackdata()->getTime()))->result();
        }

        return [
            $activity->getAdapter()
                ->getAverageHeartRateWithFallbackToTypeOrSport() => $activity->getS(),
        ];
    }

    /**
     * @param int|null $value
     */
    protected function checkIfValueIsOutOfRange(&$value, Training $activity)
    {
        if ($value !== null) {
            if ($value > $this->MaximalTrimp) {
                $value = null;
            } elseif ($activity->getS() > 0 && $value / ($activity->getS() / 3600) > $this->MaximalTrimpPerHour) {
                $value = null;
            }
        }
    }
}
