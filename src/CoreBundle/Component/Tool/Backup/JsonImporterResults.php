<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Backup;

class JsonImporterResults
{
    /**
     * @var array
     */
    protected $Results = [];

    public function addUpdates(string $tableName, int $counter)
    {
        $this->add($tableName, $counter, 'update');
    }

    public function addDeletes(string $tableName, int $counter)
    {
        $this->add($tableName, $counter, 'delete');
    }

    public function addInserts(string $tableName, int $counter)
    {
        $this->add($tableName, $counter, 'insert');
    }

    /**
     * Increase internal counter
     */
    protected function add(string $tableName, int $counter, string $key)
    {
        $this->Results[$tableName] ??= [
            'update' => 0,
            'delete' => 0,
            'insert' => 0,
        ];

        $this->Results[$tableName][$key] += $counter;
    }

    /**
     * Get internal counter
     */
    protected function get(string $tableName, string $key): int
    {
        if (isset($this->Results[$tableName])) {
            return $this->Results[$tableName][$key];
        }

        return 0;
    }

    /**
     * Are there results?
     */
    public function hasResultsFor(string $tableName): bool
    {
        if (isset($this->Results[$tableName])) {
            return
                $this->Results[$tableName]['delete'] > 0 ||
                $this->Results[$tableName]['update'] > 0 ||
                $this->Results[$tableName]['insert'] > 0
            ;
        }

        return false;
    }

    /**
     * Get complete string
     */
    public function completeString(): string
    {
        $Rows = [];

        foreach ($this->tables() as $tableName => $String) {
            if ($this->hasResultsFor($tableName)) {
                $Rows[] = $this->stringFor($tableName, $String);
            }
        }

        if ($Rows === []) {
            return 'Nothing changed.';
        }

        return implode('<br>', $Rows);
    }

    /**
     * Get result string
     */
    protected function stringFor(string $tableName, string $string): string
    {
        $format = '<strong>%s</strong>: %d deleted, %d updated, %d inserted';

        return sprintf($format, $string, $this->Results[$tableName]['delete'], $this->Results[$tableName]['update'], $this->Results[$tableName]['insert']);
    }

    /**
     * Tables with string
     * @return array<string, string>
     */
    protected function tables(): array
    {
        return [
            'runalyze_conf' => __('Configuration'),
            'runalyze_dataset' => __('Dataset'),
            'runalyze_plugin' => __('Plugin'),
            'runalyze_plugin_conf' => __('Plugin configuration'),
            'runalyze_sport' => __('Sports'),
            'runalyze_type' => __('Activity types'),
            'runalyze_user' => __('Body data'),
            'runalyze_training' => __('Activities'),
            'runalyze_trackdata' => __('Trackdata'),
            'runalyze_swimdata' => __('Swimdata'),
            'runalyze_route' => __('Routes'),
            'runalyze_hrv' => __('HRV data'),
            'runalyze_equipment' => __('Equipment'),
            'runalyze_equipment_type' => __('Equipment types'),
            'runalyze_equipment_sport' => __('Relation: equipment types to sports'),
            'runalyze_equipment_to_sport' => __('Relation: equipment to sports'),
            'runalyze_activity_equipment' => __('Relation: equipment to activities'),
            'runalyze_tag' => __('Tags'),
            'runalyze_activtiy_tag' => __('Relation: tag to activities'),
            'runalyze_raceresult' => __('Race results'),
        ];
    }
}
