<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Backup;

class FilenameHandler
{
    public const string FILENAME_PREFIX = 'runalyze-backup';

    public const string JSON_FORMAT = 'json.gz';

    public const string SQL_FORMAT = 'sql.gz';

    protected string $RunalyzeVersion = 'x';

    /**
     * @TODO realize as service using DI
     */
    public function __construct(
        protected int $AccountId
    ) {}

    public function setRunalyzeVersion(string $version): void
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

        return str_starts_with($internalFilename, $expectedPrefix);
    }

    public static function validateFileExtension(string $completeFilename): bool
    {
        return
            str_ends_with($completeFilename, self::JSON_FORMAT)
            || str_ends_with($completeFilename, self::SQL_FORMAT)
        ;
    }

    public static function validateImportFileExtension(string $completeFilename): bool
    {
        return str_ends_with($completeFilename, self::JSON_FORMAT);
    }

    public function generatePublicFilename(string $internalFilename): string
    {
        return substr($internalFilename, strlen($this->AccountId . '-' . self::FILENAME_PREFIX) + 1);
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
