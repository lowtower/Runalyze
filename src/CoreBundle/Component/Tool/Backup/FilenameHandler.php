<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Backup;

class FilenameHandler
{
    /**
     * @var string
     */
    public const FILENAME_PREFIX = 'runalyze-backup';

    /**
     * @var string
     */
    public const JSON_FORMAT = 'json.gz';

    /**
     * @var string
     */
    public const SQL_FORMAT = 'sql.gz';

    protected int $AccountId;

    protected string $RunalyzeVersion = 'x';

    /**
     * @TODO realize as service using DI
     */
    public function __construct(int $accountId)
    {
        $this->AccountId = $accountId;
    }

    public function setRunalyzeVersion(string $version)
    {
        $this->RunalyzeVersion = $version;
    }

    public function generateInternalFilename(string $extension): string
    {
        return sprintf(
            '%s-%s-%s-%s-%s.%s',
            $this->AccountId,
            self::FILENAME_PREFIX,
            date('Ymd-Hi'),
            $this->RunalyzeVersion,
            substr(uniqid((string) random_int(0, mt_getrandmax())), -4),
            $extension
        );
    }

    public function isValidImportExtension(string $extension): bool
    {
        return $extension === self::JSON_FORMAT;
    }

    public function isValidExportExtension(string $extension): bool
    {
        return in_array($extension, [self::JSON_FORMAT, self::SQL_FORMAT], true);
    }

    public function validateInternalFilename(string $internalFilename): bool
    {
        return $this->validateInternalPrefix($internalFilename) && self::validateFileExtension($internalFilename);
    }

    protected function validateInternalPrefix(string $internalFilename): bool
    {
        $expectedPrefix = $this->AccountId . '-' . self::FILENAME_PREFIX;

        return substr($internalFilename, 0, strlen($expectedPrefix)) === $expectedPrefix;
    }

    public static function validateFileExtension(string $completeFilename): bool
    {
        return
            substr($completeFilename, -strlen(self::JSON_FORMAT)) === self::JSON_FORMAT ||
            substr($completeFilename, -strlen(self::SQL_FORMAT)) === self::SQL_FORMAT
        ;
    }

    public static function validateImportFileExtension(string $completeFilename): bool
    {
        return substr($completeFilename, -strlen(self::JSON_FORMAT)) === self::JSON_FORMAT;
    }

    public function generatePublicFilename(string $internalFilename): string
    {
        return substr($internalFilename, strlen($this->AccountId . '-' . self::FILENAME_PREFIX));
    }

    public function validatePublicFilename(string $publicFilename): bool
    {
        return $this->validateInternalFilename($this->transformPublicToInternalFilename($publicFilename));
    }

    public function transformPublicToInternalFilename(string $publicFilename): string
    {
        return $this->AccountId . '-' . self::FILENAME_PREFIX . '-' . $publicFilename;
    }
}
