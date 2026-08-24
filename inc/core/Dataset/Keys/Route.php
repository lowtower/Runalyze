<?php

/**
 * This file contains class::Route
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;

/**
 * Dataset key: Route
 *
 * @author Hannes Christiansen
 * @package Runalyze\Dataset\Keys
 */
class Route extends AbstractKey
{
    /**
     * @var int
     */
    public const DEFAULT_CUT = 20;

    /**
     * @var string
     */
    public const ROUTE_NAME_KEY = 'route_name';

    /**
     * Enum id
     */
    public function id(): int
    {
        return \Runalyze\Dataset\Keys::ROUTE;
    }

    /**
     * Database key
     */
    public function column(): string
    {
        return '';
    }

    public function requiresJoin(): bool
    {
        return true;
    }

    /**
     * @return array array('column' => '...', 'join' => 'LEFT JOIN ...', 'field' => '`x`.`y`)
     */
    public function joinDefinition(): array
    {
        return [
            'column' => self::ROUTE_NAME_KEY,
            'join' => 'LEFT JOIN `' . PREFIX . 'route` AS `route` ON `t`.`routeid` = `route`.id',
            'field' => '`route`.`name` AS `' . self::ROUTE_NAME_KEY . '`',
        ];
    }

    /**
     * @codeCoverageIgnore
     */
    public function label(): string
    {
        return __('Route');
    }

    /**
     * @codeCoverageIgnore
     */
    public function description(): string
    {
        return sprintf(
            __('Route names are automatically cut after %u characters.'),
            self::DEFAULT_CUT
        );
    }

    /**
     * Get string to display this dataset value
     * @return string
     */
    public function stringFor(Context $context)
    {
        if ($context->hasData(self::ROUTE_NAME_KEY) && $context->data(self::ROUTE_NAME_KEY) != '') {
            return \Helper::Cut(
                $context->data(self::ROUTE_NAME_KEY),
                self::DEFAULT_CUT
            );
        }

        return '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function cssClass(): string
    {
        return 'small l';
    }
}
