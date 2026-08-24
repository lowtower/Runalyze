<?php

/**
 * This file contains class::SectionHRV
 * @package Runalyze\DataObjects\Training\View\Section
 */

/**
 * Section: HRV
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
class SectionHRV extends TrainingViewSectionTabbedPlot
{
	protected function setHeaderAndRows() {
		$this->Header = __('Heart rate variability') . ' <a class="window" href="glossary/hrv"><i class="fa fa-question-circle-o"></i></a>';

		$this->appendRowTabbedPlot( new SectionHRVRow($this->Context) );
	}

    /**
     * Has the training all required data?
     */
    protected function hasRequiredData(): bool {
		return 
			$this->Context->hasHRV()
		;
	}

    /**
     * CSS-ID
     */
    protected function cssId(): string {
		return 'hrv';
	}
}
