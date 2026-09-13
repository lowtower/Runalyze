<?php

declare(strict_types=1);

namespace Runalyze\Sports\Performance\Model;

/**
 * Model for human performance: Busso model
 *
 * For a short presentation of this model, see [1].
 *
 * @see [1] http://fellrnr.com/wiki/Modeling_Human_Performance#The_Busso_Model
 * @see [2] http://journals.lww.com/acsm-msse/pages/articleviewer.aspx?year=2003&issue=07000&article=00018&type=abstract
 */
class BussoModel extends AbstractModel
{
    /**
     * @param array $trimpData array('days back' => 'trimp value')
     * @param int $r1
     * @param int $r2
     * @param int $r3
     * @param int $k1
     * @param int $k3
     */
    public function __construct(
        array $trimpData,
        protected $r1 = 42,
        protected $r2 = 7,
        protected $r3 = 4,
        protected $k1 = 1,
        protected $k3 = 2
    ) {
        parent::__construct($trimpData);
    }

    protected function calculateArrays()
    {
        $k2 = 0;

        for ($i = $this->Range['from']; $i <= $this->Range['to']; ++$i) {
            $t = $this->Trimp[$i] ?? 0;

            $k2 = $t + exp(-1 / $this->r3) * $k2;

            $this->Fitness[$i] = $t + exp(-1 / $this->r1) * $this->Fitness[$i - 1];
            $this->Fatigue[$i] = $t + exp(-1 / $this->r2) * $this->Fatigue[$i - 1];
            $this->Performance[$i] = $this->Fitness[$i] * $this->k1 - $this->Fatigue[$i] * $this->k3 * $k2;
        }
    }
}
