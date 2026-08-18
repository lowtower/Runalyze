<?php

namespace Runalyze\Bundle\CoreBundle\Component\Tool\Backup;

class FilenameHandler
{
    /** @var string */
    const FILENAME_PREFIX = 'runalyze-backup';

    /** @var string */
    const JSON_FORMAT = 'json.gz';

    /** @var string */
    const SQL_FORMAT = 'sql.gz';

    /** @var int */
    protected $AccountId;

    /** @var string */
    protected $RunalyzeVersion = 'x';

    /**
     * @param int $accountId
     *
     * @TODO realize as service using DI
     */
    public function __construct($accountId)
    {
        $this->AccountId = (int) $accountId;
    }

    /**
     * @param string $version
     */
    public function setRunalyzeVersion($version)
    {
        $this->RunalyzeVersion = $version;
    }

    /**
     * @param string $extension
     */
    public function generateInternalFilename($extension): string
    {
        return sprintf(
            '%s-%s-%s-%s-%s.%s',
            $this->AccountId,
            self::FILENAME_PREFIX,
            date('Ymd-Hi'),
            $this->RunalyzeVersion,
            substr(uniqid(rand()), -4),
            $extension
        );
    }

    /**
     * @param string $extension
     */
    public function isValidImportExtension($extension): bool
    {
        return self::JSON_FORMAT === $extension;
    }

    /**
     * @param string $extension
     */
    public function isValidExportExtension($extension): bool
    {
        return in_array($extension, [self::JSON_FORMAT, self::SQL_FORMAT]);
    }

    /**
     * @param string $internalFilename
     */
    public function validateInternalFilename($internalFilename): bool
    {
        return $this->validateInternalPrefix($internalFilename) && self::validateFileExtension($internalFilename);
    }

    /**
     * @param string $internalFilename
     */
    protected function validateInternalPrefix($internalFilename): bool
    {
        $expectedPrefix = $this->AccountId.'-'.self::FILENAME_PREFIX;

        return substr($internalFilename, 0, strlen($expectedPrefix)) === $expectedPrefix;
    }

    /**
     * @param string $completeFilename
     */
    static public function validateFileExtension($completeFilename): bool
    {
        return (
            substr($completeFilename, -strlen(self::JSON_FORMAT)) === self::JSON_FORMAT ||
            substr($completeFilename, -strlen(self::SQL_FORMAT)) === self::SQL_FORMAT
        );
    }

    /**
     * @param string $completeFilename
     */
    static public function validateImportFileExtension($completeFilename): bool
    {
        return substr($completeFilename, -strlen(self::JSON_FORMAT)) === self::JSON_FORMAT;
    }

    /**
     * @param string $internalFilename
     * @return string
     */
    public function generatePublicFilename($internalFilename)
    {
        return substr($internalFilename, strlen($this->AccountId.'-'.self::FILENAME_PREFIX));
    }

    /**
     * @param string $publicFilename
     */
    public function validatePublicFilename($publicFilename): bool
    {
        return $this->validateInternalFilename($this->transformPublicToInternalFilename($publicFilename));
    }

    /**
     * @param string $publicFilename
     */
    public function transformPublicToInternalFilename($publicFilename): string
    {
        return $this->AccountId.'-'.self::FILENAME_PREFIX.'-'.$publicFilename;
    }
}
