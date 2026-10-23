<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services\Import;

class TimezoneLookupException extends \Exception
{
    public const string TIMEZONE_DB_NOT_AVAILABLE = 'Timezone database is missing.';

    public const string SQLITE3_NOT_AVAILABLE = 'SQLite3 is not available.';

    public const string SPATIALITE_NOT_AVAILABLE = "SQLite3 extension 'spatialite' can't be loaded.";
}
