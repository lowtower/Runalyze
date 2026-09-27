<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Backup;

use Runalyze\Util\File\GZipReader;

class JsonBackupAnalyzer
{
    protected string $VersionString = '';

    /**
     * @var array ['table' => #rows]
     */
    protected array $NumberOf = [];

    /**
     * Construct
     * @param string $fileName absolute path
     * @param string $RunalyzeVersion runalyze version string
     */
    public function __construct(
        string $fileName,
        protected string $RunalyzeVersion
    ) {
        $reader = new GZipReader($fileName);

        while (! $reader->eof()) {
            $line = trim($reader->readLine());
            $tableName = substr($line, 10, -2);

            if (str_starts_with($line, '{"TABLE"')) {
                $this->NumberOf[$tableName] = 0;
            } elseif ($line !== '' && $line[0] == '{' && isset($this->NumberOf[$tableName])) {
                $this->NumberOf[$tableName]++;
            } elseif (str_starts_with($line, 'version=')) {
                $this->VersionString = substr($line, 8);
            }
        }

        $reader->close();
    }

    /**
     * Count rows for table
     */
    public function count(string $tableName): int
    {
        return $this->NumberOf[$tableName] ?? 0;
    }

    public function fileIsOkay(): bool
    {
        return $this->versionIsOkay() && $this->expectedTablesAreThere();
    }

    public function versionIsOkay(): bool
    {
        return self::extractMajorAndMinorVersion($this->RunalyzeVersion) === self::extractMajorAndMinorVersion($this->VersionString);
    }

    public function fileVersion(): string
    {
        if ($this->VersionString === '') {
            return 'unknown';
        }

        return 'v' . $this->VersionString;
    }

    protected function expectedTablesAreThere(): bool
    {
        $Expected = $this->expectedTables();
        $Found = array_keys($this->NumberOf);

        sort($Expected);
        sort($Found);

        return $Expected === $Found;
    }

    /**
     * @return array error messages
     */
    public function errors(): array
    {
        $Expected = $this->expectedTables();
        $Found = array_keys($this->NumberOf);
        $Errors = [];

        foreach (array_diff($Expected, $Found) as $MissingKey) {
            $Errors[] = 'Missing table: ' . $MissingKey;
        }

        foreach (array_diff($Found, $Expected) as $AdditionalKey) {
            $Errors[] = 'Additional table: ' . $AdditionalKey;
        }

        return $Errors;
    }

    /**
     * @return array<int, string> table names with default prefix 'runalyze_'
     */
    protected function expectedTables(): array
    {
        return [
            'runalyze_account',
            'runalyze_conf',
            'runalyze_dataset',
            'runalyze_hrv',
            'runalyze_plugin',
            'runalyze_plugin_conf',
            'runalyze_route',
            'runalyze_sport',
            'runalyze_swimdata',
            'runalyze_training',
            'runalyze_trackdata',
            'runalyze_type',
            'runalyze_user',
            'runalyze_equipment_type',
            'runalyze_equipment_sport',
            'runalyze_equipment_to_sport',
            'runalyze_equipment',
            'runalyze_activity_equipment',
            'runalyze_activity_tag',
            'runalyze_tag',
            'runalyze_raceresult',
        ];
    }

    /**
     * Extract major and minor version, i.e. 'X.Y' of any 'X.Y[.Z][-abc]'
     */
    public static function extractMajorAndMinorVersion(string $versionString): string
    {
        if (preg_match('/^(\d+\.\d+)/', $versionString, $matches)) {
            return $matches[1];
        }

        return '';
    }
}
