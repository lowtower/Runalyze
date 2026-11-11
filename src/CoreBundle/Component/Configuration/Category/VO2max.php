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

    public function useManualValue(): bool
    {
        return $this->getManualValue() > 0.0;
    }

    public function getManualValue(): float
    {
        return (float) str_replace(',', '.', $this->Variables['VO2MAX_MANUAL_VALUE']);
    }

    public function useManualFactor(): bool
    {
        $factor = $this->Variables['VO2MAX_MANUAL_CORRECTOR'];

        return $factor !== null && $factor !== '';
    }

    public function getManualFactor(): float
    {
        return (float) $this->Variables['VO2MAX_MANUAL_CORRECTOR'];
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

    public function getLegacyCategory(): \Runalyze\Configuration\Category
    {
        return parent::getLegacyCategory();
    }

    protected function getLegacyCategoryName(): string
    {
        return \Runalyze\Configuration\Category\VO2max::class;
    }
}
