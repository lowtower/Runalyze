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
        return match ($Type) {
            self::PANEL => 'panel',
            default => 'stat',
        };
    }

    /**
     * @param int $Type enum
     */
    public static function readableString(int $Type): string
    {
        return match ($Type) {
            self::PANEL => __('Panel'),
            default => __('Statistics'),
        };
    }
}
