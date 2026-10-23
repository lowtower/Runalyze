<?php

/**
 * This file contains class::PluginConfiguration
 * @package Runalyze\Plugin
 */
/**
 * Plugin configuration
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
class PluginConfiguration
{
    public const string CACHE_KEY = 'PluginConf';

    /**
     * Values
     * @var PluginConfigurationValue[]
     */
    protected array $Values = [];

    /**
     * Catched values?
     */
    private bool $CatchedValues = false;

    public function __construct(
        protected int $PluginID
    ) {
    }

    /**
     * Catch values if not done yet
     */
    final public function catchValuesFromDatabaseIfNotDoneYet(): void
    {
        if (! $this->CatchedValues) {
            $this->catchValuesFromDatabase();

            $this->CatchedValues = true;
        }
    }

    /**
     * Catch values
     */
    final public function catchValuesFromDatabase(): void
    {
        $ResultFromDB = Cache::get(self::CACHE_KEY);

        if ($ResultFromDB === null) {
            $ResultFromDB = DB::getInstance()->query(
                'SELECT `pluginid`,`config`,`value` ' .
                'FROM `' . PREFIX . 'plugin_conf` ' .
                'WHERE `pluginid` IN (' . implode(',', PluginFactory::allIDs()) . ')'
            )->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

            Cache::set(self::CACHE_KEY, $ResultFromDB, 60);
        }

        $this->checkValuesFromDatabase($ResultFromDB);
    }

    /**
     * Check values
     * @param array<int, mixed> $ResultFromDB
     */
    private function checkValuesFromDatabase(array $ResultFromDB): void
    {
        $ValuesFromDB = [];

        if (isset($ResultFromDB[$this->PluginID])) {
            foreach ($ResultFromDB[$this->PluginID] as $Result) {
                $ValuesFromDB[$Result['config']] = $Result['value'];
            }
        }

        foreach ($this->Values as $Value) {
            if (isset($ValuesFromDB[$Value->key()])) {
                $Value->setValueFromString($ValuesFromDB[$Value->key()]);
            } else {
                $Value->setDefaultValueAsValue();

                $this->insertValueToDatabase($Value);
            }
        }
    }

    private function insertValueToDatabase(PluginConfigurationValue $Value): void
    {
        DB::getInstance()->insert(
            'plugin_conf',
            [
                'pluginid',
                'config',
                'value',
            ],
            [
                $this->PluginID,
                $Value->key(),
                $Value->valueAsString(),
            ]
        );

        Cache::delete(self::CACHE_KEY);
    }

    final public function updateFromPost(): void
    {
        foreach ($this->Values as $Value) {
            $Value->setValueFromPost();

            $this->update($Value->key());
        }

        Cache::delete(self::CACHE_KEY);
    }

    final public function update(string $key): void
    {
        if (isset($this->Values[$key])) {
            DB::getInstance()->updateWhere(
                'plugin_conf',
                '`pluginid`=' . $this->PluginID . ' AND `config`="' . $key . '"',
                'value',
                $this->Values[$key]->valueAsString()
            );
        }
    }

    final public function addValue(PluginConfigurationValue $Value): void
    {
        $this->Values[$Value->key()] = $Value;
    }

    /**
     * Object for value
     * @throws InvalidArgumentException
     */
    final public function object(string $key): PluginConfigurationValue
    {
        if (isset($this->Values[$key])) {
            return $this->Values[$key];
        }

        throw new InvalidArgumentException('There is no value for "' . $key . '".');
    }

    /**
     * All objects
     * @return PluginConfigurationValue[]
     */
    final public function objects(): array
    {
        return $this->Values;
    }

    /**
     * Is empty?
     */
    final public function isEmpty(): bool
    {
        return $this->Values === [];
    }

    final public function value(string $key): mixed
    {
        return $this->object($key)
            ->value();
    }
}
