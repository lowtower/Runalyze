<?php

namespace Runalyze\Bundle\CoreBundle\Component\Configuration\Category;

class Data extends AbstractCategory
{
    public function getDefaultVariables(): array
    {
        return [
            'START_TIME' => '0',
            'HF_MAX' => '200',
            'HF_REST' => '60',
            'VO2MAX_FORM' => '0.0',
            'VO2MAX_CORRECTOR' => '1.0',
            'BASIC_ENDURANCE' => '0',
            'MAX_ATL' => '0',
            'MAX_CTL' => '0',
            'MAX_TRIMP' => '0',
        ];
    }

    /**
     * @return int timestamp
     */
    public function getStartTime(): int
    {
        return (int) $this->Variables['START_TIME'];
    }

    /**
     * @return int [bpm]
     */
    public function getMaximalHeartRate(): int
    {
        return (int) $this->Variables['HF_MAX'];
    }

    /**
     * @return int [bpm]
     */
    public function getRestingHeartRate(): int
    {
        return (int) $this->Variables['HF_REST'];
    }

    public function getCurrentVO2maxShape(): int
    {
        return (int) $this->Variables['VO2MAX_FORM'];
    }

    public function getVO2maxCorrectionFactor(): float
    {
        return (float) $this->Variables['VO2MAX_CORRECTOR'];
    }

    protected function getLegacyCategoryName(): string
    {
        return \Runalyze\Configuration\Category\Data::class;
    }
}
