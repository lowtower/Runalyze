<?php

use Runalyze\Bundle\CoreBundle\Services\LegacyPluginConfigurationStorage;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;

/**
 * Plugin configuration
 */
class PluginConfiguration
{
    public const string CACHE_KEY = 'PluginConf';

    /**
     * @var PluginConfigurationValue[]
     */
    protected array $Values = [];

    private bool $CatchedValues = false;

    private readonly PluginConfigurationStorageInterface $Storage;

    public function __construct(protected int $PluginID, ?PluginConfigurationStorageInterface $Storage = null)
    {
        $this->Storage = $Storage ?? new LegacyPluginConfigurationStorage();
    }

    final public function catchValuesFromDatabaseIfNotDoneYet(): void
    {
        if (! $this->CatchedValues) {
            $this->catchValuesFromDatabase();
            $this->CatchedValues = true;
        }
    }

    final public function catchValuesFromDatabase(): void
    {
        $this->checkValuesFromDatabase($this->Storage->loadForPlugins(PluginFactory::allIDs()));
    }

    /**
     * @param array<int, array<string, string>> $valuesFromDatabase
     */
    private function checkValuesFromDatabase(array $valuesFromDatabase): void
    {
        $pluginValues = $valuesFromDatabase[$this->PluginID] ?? [];

        foreach ($this->Values as $value) {
            if (array_key_exists($value->key(), $pluginValues)) {
                $value->setValueFromString($pluginValues[$value->key()]);
            } else {
                $value->setDefaultValueAsValue();
                $this->insertValueToDatabase($value);
            }
        }
    }

    private function insertValueToDatabase(PluginConfigurationValue $value): void
    {
        $this->Storage->insert($this->PluginID, $value->key(), $value->valueAsString());
    }

    final public function updateFromPost(): void
    {
        foreach ($this->Values as $value) {
            $value->setValueFromPost();
            $this->update($value->key());
        }

        $this->Storage->clearCache();
    }

    final public function update(string $key): void
    {
        if (isset($this->Values[$key])) {
            $this->Storage->update($this->PluginID, $key, $this->Values[$key]->valueAsString());
        }
    }

    final public function addValue(PluginConfigurationValue $Value): void
    {
        $this->Values[$Value->key()] = $Value;
    }

    /**
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
     * @return PluginConfigurationValue[]
     */
    final public function objects(): array
    {
        return $this->Values;
    }

    final public function isEmpty(): bool
    {
        return $this->Values === [];
    }

    final public function value(string $key): mixed
    {
        return $this->object($key)->value();
    }
}
