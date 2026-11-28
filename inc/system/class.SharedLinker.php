<?php

declare(strict_types=1);

/**
 * This file contains class::SharedLinker
 * @package Runalyze\System
 */

use Runalyze\Configuration;

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

    /**
     * Get link to shared list for current user
     * @param ?string $text [optional]
     */
    public static function getListLinkForCurrentUser(?string $text = null): string
    {
        $text ??= '<i class="fa far fa-fw fa-address-card"></i>';

        if (! Configuration::Privacy()->listIsPublic()) {
            return '<a class=window tab" href="settings?key=config_tab_general" ' . Ajax::tooltip('', __('You can activate your public athlete page at <em>Configuration->Privacy</em>'), false, true) . '>' . $text . '</a>';
        }

        return '<a href="athlete/' . SessionAccountHandler::getUsername() . '" target="_blank" ' . Ajax::tooltip('', __('Your public athlete page'), false, true) . '>' . $text . '</a>';
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
