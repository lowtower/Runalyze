<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Configuration\Category;

class VO2max extends AbstractCategory
{
    public function getDefaultVariables(): array
    {
        return [
            'VO2MAX_DAYS' => '30',
            'VO2MAX_MANUAL_CORRECTOR' => '',
            'VO2MAX_MANUAL_VALUE' => '',
            'VO2MAX_USE_CORRECTION_FOR_ELEVATION' => 'false',
            'VO2MAX_CORRECTION_POSITIVE_ELEVATION' => '2',
            'VO2MAX_CORRECTION_NEGATIVE_ELEVATION' => '-1',
        ];
    }

    public function getNumberOfDaysToConsider(): int
    {
        return (int) $this->Variables['VO2MAX_DAYS'];
    }

    public function useCorrectionForElevation(): bool
    {
        return $this->Variables['VO2MAX_USE_CORRECTION_FOR_ELEVATION'] == 'true';
    }

    /**
     * @return int [m]
     */
    public function getAdditionalDistancePerPositiveElevation(): int
    {
        return (int) $this->Variables['VO2MAX_CORRECTION_POSITIVE_ELEVATION'];
    }

    /**
     * @return int [m]
     */
    public function getAdditionalDistancePerNegativeElevation(): int
    {
        return (int) $this->Variables['VO2MAX_CORRECTION_NEGATIVE_ELEVATION'];
    }

    /**
     * @return \Runalyze\Configuration\Category\VO2max
     */
    public function getLegacyCategory()
    {
        return parent::getLegacyCategory();
    }

    protected function getLegacyCategoryName(): string
    {
        return \Runalyze\Configuration\Category\VO2max::class;
    }
}
