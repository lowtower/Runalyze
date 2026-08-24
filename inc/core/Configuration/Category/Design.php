<?php

/**
 * This file contains class::Design
 * @package Runalyze\Configuration\Category
 */

namespace Runalyze\Configuration\Category;

use Runalyze\Configuration\Fieldset;
use Runalyze\Parameter\SelectFile;
use Ajax;

/**
 * Configuration category: Design
 * @author Hannes Christiansen
 * @package Runalyze\Configuration\Category
 */
class Design extends \Runalyze\Configuration\Category
{
    /**
     * Internal key
     */
    protected function key(): string {
		return 'design';
	}

	protected function createHandles() {
		$this->createHandle('DESIGN_BG_FILE', new SelectFile('web/assets/v2.x/img/backgrounds/runalyze.jpg', [
    'folder' => 'web/assets/v2.x/img/backgrounds/',
		    'extensions' => ['jpg', 'png', 'gif', 'jpeg', 'svg', 'tiff', 'bmp'],
]));
	}

	/**
	 * @return string path without leading 'web/'
	 */
	public function backgroundImage(): string {
		return 'assets/v2.x/img/backgrounds/' . basename($this->get('DESIGN_BG_FILE'));
	}

	protected function registerOnchangeEvents() {
		$this->handle('DESIGN_BG_FILE')
		    ->registerOnchangeEvent(self::class . '::setBackgroundImageToBody');
	}

    public function Fieldset(): \Runalyze\Configuration\Fieldset {
		$Fieldset = new Fieldset( __('Design') );
		$Fieldset->addHandle( $this->handle('DESIGN_BG_FILE'), [
		    'label' => __('Background image'),
		]);

		return $Fieldset;
	}

	public static function setBackgroundImageToBody() {
		$url = \Runalyze\Configuration::Design()->backgroundImage();

		echo Ajax::wrapJSasFunction('$("body").css("background-image","url(\'' . $url . '\')");');
	}
}
