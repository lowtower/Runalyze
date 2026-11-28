<?php

declare(strict_types=1);

/**
 * This file contains class::SharedLinker
 * @package Runalyze\System
 */

/**
 * Class for handling links to shared activities
 * @author Hannes Christiansen
 * @package Runalyze\System
 * @deprecated since v3.1
 */
class SharedLinker
{
    public const string ACCOUNT_ATTRIBUTE = '_shared_athlete_account_id';

    /**
     * URL base for shared activities
     * @var string
     */
    public static $URL = 'shared/';

    /**
     * Is the user on the shared page?
     */
    public static function isOnSharedPage(): bool
    {
        return self::getUserId() > 0;
    }

    public static function getUserId(): int
    {
        $accountId = Request::attribute(self::ACCOUNT_ATTRIBUTE);

        return is_int($accountId) && $accountId > 0 ? $accountId : -1;
    }

    /**
     * Get URL for a given training
     */
    public static function getUrlFor(?int $trainingID): string
    {
        return self::$URL . self::idToUrl($trainingID);
    }

    /**
     * Transform given ID to url
     */
    private static function idToUrl(?int $id): string
    {
        return base_convert((string) $id, 10, 35);
    }
}
