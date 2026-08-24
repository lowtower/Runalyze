<?php

/**
 * This file contains class::PluginInstaller
 * @package Runalyze\Plugin
 */
/**
 * Plugin installer
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
class PluginInstaller
{
	/**
	 * Installer id
	 */
	const ID = -1;

	/**
	 * Install URL
	 */
	const INSTALL_URL = 'call/call.Plugin.install.php';

	/**
	 * Key
	 * @var string
	 */
	protected $Key;

	/**
	 * @param string $Key
	 */
	public function __construct($Key) {
		$this->Key = $Key;
	}

    /**
     * To install a new plugin, it is only needed to insert it into the database.
     * Configuration variables will be inserted on the fly.
     */
    public function install(): bool {
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
	 * @param string $name [optional] default: add-icon
	 * @return string
	 */
	public static function link($key, $name = '') {
		if ($name == '') {
			$name = Icon::$ADD;
		}

		return Ajax::window('<a href="' . self::INSTALL_URL . '?key=' . $key . '">' . Ajax::tooltip($name, __('Install plugin') ) . '</a>');
	}
}
