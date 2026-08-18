<?php

/**
 * This file contains class::SectionComposite
 * @package Runalyze\DataObjects\Training\View\Section
 */
/**
 * Section: Composite
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
class SectionComposite extends TrainingViewSectionTabbedPlot {
	/**
	 * Set header and rows
	 */
	protected function setHeaderAndRows() {
		$this->Header = __('Composite data');

		$this->appendRowTabbedPlot( new SectionCompositeRow($this->Context) );
	}

    /**
     * Has the training all required data?
     */
    protected function hasRequiredData(): bool {
		return true;
	}

    /**
     * CSS-ID
     */
    protected function cssId(): string {
		return 'composite';
	}
}
