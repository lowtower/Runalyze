<?php

declare(strict_types=1);

namespace Runalyze\Sports\ClimbQuantification;

interface AdditiveClimbQuantificationInterface extends ClimbQuantificationInterface
{
    /**
     * @param array $distancesAndGradients [[km, gradient in 0.00 .. 1.00], ...]
     * @param int $altitudeAtTop [m]
     * @return mixed
     */
    public function getScoreForProfile(array $distancesAndGradients, int $altitudeAtTop = 0);
}
