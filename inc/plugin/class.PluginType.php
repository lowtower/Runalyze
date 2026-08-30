<?php

declare(strict_types=1);

/**
 * This file contains class::PluginType
 * @package Runalyze\Plugin
 */
/**
 * Types for plugins
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
abstract class PluginType
{
    /**
     * @var int Enum: Stat
     */
    public const STAT = 0;

    /**
     * @var int Enum: Panel
     */
    public const PANEL = 1;

    /**
     * @param int $Type enum
     */
    public static function string(int $Type): string
    {
        switch ($Type) {
            case self::PANEL:
                return 'panel';

            case self::STAT:
            default:
                return 'stat';
        }
    }

    /**
     * @param int $Type enum
     */
    public static function readableString(int $Type): string
    {
        switch ($Type) {
            case self::PANEL:
                return __('Panel');

            case self::STAT:
            default:
                return __('Statistics');
        }
    }
}
