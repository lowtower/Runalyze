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

    public function __construct(
        protected string $Key
    ) {}

    /**
     * To install a new plugin, it is only needed to insert it into the database.
     * Configuration variables will be inserted on the fly.
     */
    public function install(): bool
    {
        $Factory = new PluginFactory();
        $Plugin = $Factory->newInstallerInstance($this->Key);

        DB::getInstance()->insert('plugin', [
            'key',
            'type',
            'active',
            'order',
        ], [
            $Plugin->key(),
            $Plugin->typeString(),
            '1',
            '99',
        ]);

        PluginFactory::clearCache();

        return true;
    }

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
