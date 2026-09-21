<?php

namespace Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation;

use Runalyze\Athlete;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Calculation\Trimp\Calculator;
use Runalyze\Calculation\Trimp\DataCollector;

class TrimpCalculator
{
    public function __construct(
        protected int $MaximalTrimp = 10000,
        protected int $MaximalTrimpPerHour = 400
    ) {
    }

    /**
     * @param int $gender enum, see \Runalyze\Profile\Athlete\Gender
     * @param int $heartRateMaximum [bpm]
     * @param int $heartRateResting [bpm]
     */
    public function calculateFor(Training $activity, int $gender, int $heartRateMaximum, int $heartRateResting): void
    {
        $calculator = new Calculator(
            new Athlete($gender, $heartRateMaximum, $heartRateResting),
            $this->getHeartRateHistogram($activity)
        );

        $value = (int) $calculator->value();

        $this->checkIfValueIsOutOfRange($value, $activity);

        $activity->setTrimp($value);
    }

    protected function getHeartRateHistogram(Training $activity): array
    {
        if ($activity->hasTrackdata() && $activity->getTrackdata()->hasHeartrate() && $activity->getTrackdata()->hasTime()) {
            return (new DataCollector($activity->getTrackdata()->getHeartrate(), $activity->getTrackdata()->getTime()))->result();
        }

        return [
            $activity->getAdapter()
                ->getAverageHeartRateWithFallbackToTypeOrSport() => $activity->getS(),
        ];
    }

    protected function checkIfValueIsOutOfRange(?int &$value, Training $activity)
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
