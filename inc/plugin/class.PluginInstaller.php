<?php

/**
 * Plugin installer
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
class PluginInstaller
{
    public const int ID = -1;

    public const string INSTALL_URL = 'call/call.Plugin.install.php';

    /**
     * Get install link
     * @param string $name, default: add-icon
     */
    public static function link(string $key, string $name = ''): string
    {
        if ($name === '') {
            $name = Icon::$ADD;
        }

        return Ajax::window('<a href="' . self::INSTALL_URL . '?key=' . $key . '">' . Ajax::tooltip($name, __('Install plugin')) . '</a>');
    }
}
