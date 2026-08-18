<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Configuration\Category;

class General extends AbstractCategory
{
    public function getDefaultVariables(): array
    {
        return [
            'WEEK_START' => '1',
            'DISTANCE_UNIT_SYSTEM' => 'metric',
            'WEIGHT_UNIT' => 'kg',
            'ENERGY_UNIT' => 'kcal',
            'TEMPERATURE_UNIT' => '°C',
            'HEART_RATE_UNIT' => 'hfmax',
            'MAINSPORT' => '1',
            'RUNNINGSPORT' => '1',
        ];
    }

    public function getRunningSport(): int
    {
        return (int) $this->Variables['RUNNINGSPORT'];
    }

    public function getMainSport(): int
    {
        return (int) $this->Variables['MAINSPORT'];
    }

    protected function getLegacyCategoryName(): string
    {
        return \Runalyze\Configuration\Category\General::class;
    }
}
