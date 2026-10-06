<?php

/**
 * This file contains class::Types
 * @package Runalyze\Export\Share
 */

namespace Runalyze\Export\Share;

use Runalyze\Common\Enum\AbstractEnum;
use Runalyze\View\Activity\Context;

/**
 * Enum for share types and their internal ids
 *
 * @author Hannes Christiansen
 * @package Runalyze\Export\Share
 */
final class Types extends AbstractEnum
{
    public const int TWITTER = 1;

    public const int FACEBOOK = 2;

    public const int GOOGLE_PLUS = 3;

    public const int HTML = 4;

    public const int IFRAME = 5;

    /**
     * Get sharer
     * @param int $typeid int from internal enum
     * @throws \InvalidArgumentException
     */
    public static function get(int $typeid, Context $context): AbstractSharer
    {
        $classNames = self::classNamesArray();

        if (! isset($classNames[$typeid])) {
            throw new \InvalidArgumentException('Invalid type id "' . $typeid . '".');
        }

        $className = 'Runalyze\\Export\\Share\\' . $classNames[$typeid];

        return new $className($context);
    }

    /**
     * Get array with class names
     * @return array<int, string>
     */
    private static function classNamesArray(): array
    {
        return [
            self::TWITTER => 'Twitter',
            self::FACEBOOK => 'Facebook',
            self::GOOGLE_PLUS => 'GooglePlus',
            self::HTML => 'Html',
            self::IFRAME => 'IFrame',
        ];
    }
}
