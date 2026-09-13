<?php

namespace Runalyze\Bundle\CoreBundle\Services\Import;

class TimezoneLookup
{
    /**
     * @var \SQLite3|null|false
     */
    protected $SQLite;

    /**
     * @param string $PathToTimeZoneDatabase absolute path to database
     * @param string $SpatialiteExtensionName
     * @param bool $SilentExceptions
     */
    public function __construct(
        protected $PathToTimeZoneDatabase,
        protected $SpatialiteExtensionName,
        protected $SilentExceptions = false
    ) {
    }

    /**
     * @param bool $flag
     */
    public function silentExceptions($flag = true): void
    {
        $this->SilentExceptions = $flag;
    }

    /**
     * @throws TimezoneLookupException
     */
    public function isPossible(): bool
    {
        try {
            $sqLite = $this->getSQLite();

            if ($sqLite instanceof \SQLite3) {
                return true;
            }
        } catch (TimezoneLookupException $timezoneLookupException) {
            if (! $this->SilentExceptions) {
                throw $timezoneLookupException;
            }
        }

        return false;
    }

    /**
     * @param float $longitude
     * @param float $latitude
     * @return null|string
     */
    public function getTimezoneForCoordinate($longitude, $latitude)
    {
        if ($this->isPossible() && is_numeric($longitude) && is_numeric($latitude)) {
            $query = $this->SQLite->query('SELECT `tzid` FROM `tz_world` WHERE ST_Contains(`geometry`, MakePoint(' . $longitude . ', ' . $latitude . '))');

            if (! is_bool($query)) {
                $result = $query->fetchArray();

                if ($result['tzid']) {
                    return $result['tzid'];
                }
            }
        }

        return null;
    }

    /**
     * @return false|null|\SQLite3
     */
    protected function getSQLite()
    {
        if ($this->SQLite === null) {
            $this->tryToConnectToSQLite();
            $this->checkSpatialiteExtension();
        }

        return $this->SQLite;
    }

    protected function tryToConnectToSQLite()
    {
        if (! file_exists($this->PathToTimeZoneDatabase)) {
            throw new TimezoneLookupException(TimezoneLookupException::TIMEZONE_DB_NOT_AVAILABLE);
        }

        if (! class_exists('SQLite3')) {
            throw new TimezoneLookupException(TimezoneLookupException::SQLITE3_NOT_AVAILABLE);
        }

        $this->SQLite = new \SQLite3($this->PathToTimeZoneDatabase);

        try {
            $loaded = @$this->SQLite->loadExtension($this->SpatialiteExtensionName);
        } catch (\Exception) {
            $loaded = false;
        }

        if (! $loaded) {
            $this->SQLite->close();
            $this->SQLite = false;

            throw new TimezoneLookupException(TimezoneLookupException::SPATIALITE_NOT_AVAILABLE);
        }
    }

    protected function checkSpatialiteExtension()
    {
        if (! ($this->SQLite instanceof \SQLite3)) {
            $this->SQLite = false;

            return;
        }

        $query = @$this->SQLite->query('SELECT spatialite_version()');

        if (! $query) {
            $this->SQLite = false;

            throw new TimezoneLookupException(TimezoneLookupException::SPATIALITE_NOT_AVAILABLE);
        }
    }
}
