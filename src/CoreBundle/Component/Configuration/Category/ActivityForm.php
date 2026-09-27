<?php

namespace Runalyze\Bundle\CoreBundle\Component\Configuration\Category;

use Runalyze\Configuration\Category\ActivityForm as LegacyActivityForm;
use Runalyze\Parameter\Application\DatabaseOrder;

class ActivityForm extends AbstractCategory
{
    public function getDefaultVariables(): array
    {
        return [
            'TRAINING_CREATE_MODE' => 'upload',
            'TRAINING_DO_ELEVATION' => 'true',
            'TRAINING_LOAD_WEATHER' => 'true',
            'PLZ' => '',
            'COMPUTE_KCAL' => 'true',
            'TRAINING_SORT_SPORTS' => 'id-asc',
            'TRAINING_SORT_TYPES' => 'id-asc',
            'TRAINING_SORT_SHOES' => 'id-asc',
            'GARMIN_IGNORE_IDS' => '',
            'DETECT_PAUSES' => 'true',
            'IMPORT_MAX_HR_USER' => 'false',
            'FORMULAR_SHOW_SPORT' => 'true',
            'FORMULAR_SHOW_GENERAL' => 'true',
            'FORMULAR_SHOW_DISTANCE' => 'true',
            'FORMULAR_SHOW_SPLITS' => 'true',
            'FORMULAR_SHOW_WEATHER' => 'true',
            'FORMULAR_SHOW_OTHER' => 'true',
            'FORMULAR_SHOW_NOTES' => 'false',
            'FORMULAR_SHOW_PUBLIC' => 'false',
            'FORMULAR_SHOW_ELEVATION' => 'false',
            'FORMULAR_SHOW_GPS' => 'false',
        ];
    }

    public function fieldSetIsCollapsed(string $key): bool
    {
        return isset($this->Variables['FORMULAR_SHOW_' . $key]) && $this->Variables['FORMULAR_SHOW_' . $key] !== 'true';
    }

    public function isAutomaticElevationCorrectionActivated(): bool
    {
        return $this->Variables['TRAINING_DO_ELEVATION'] === 'true';
    }

    public function isAutomaticWeatherForecastActivated(): bool
    {
        return $this->Variables['TRAINING_LOAD_WEATHER'] === 'true';
    }

    public function getDefaultLocationForWeatherForecast(): string
    {
        return $this->Variables['PLZ'];
    }

    public function isComputingEnergyActivated(): bool
    {
        return $this->Variables['COMPUTE_KCAL'] === 'true';
    }

    public function isAutomaticPauseDetectionActivated(): bool
    {
        return $this->Variables['DETECT_PAUSES'] === 'true';
    }

    public function getDatabaseOrderForSport(): DatabaseOrder
    {
        return $this->getDatabaseOrderFor('TRAINING_SORT_SPORTS');
    }

    public function getDatabaseOrderForTypes(): DatabaseOrder
    {
        return $this->getDatabaseOrderFor('TRAINING_SORT_TYPES');
    }

    public function getDatabaseOrderForEquipment(): DatabaseOrder
    {
        return $this->getDatabaseOrderFor('TRAINING_SORT_SHOES');
    }

    protected function getDatabaseOrderFor(string $key): DatabaseOrder
    {
        $order = new DatabaseOrder();
        $order->set($this->Variables[$key]);

        return $order;
    }

    public function getIgnoredActivityIds(): array
    {
        return array_filter(
            explode(',', $this->Variables['GARMIN_IGNORE_IDS'])
        );
    }

    protected function getLegacyCategoryName(): string
    {
        return LegacyActivityForm::class;
    }
}
