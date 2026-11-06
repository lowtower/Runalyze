<?php

declare(strict_types=1);

/**
 * This file contains class::TrainingViewSectionRow
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\View\Activity\Context;

/**
 * Row of the training view
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
abstract class TrainingViewSectionRow extends TrainingViewSectionRowAbstract
{
    /**
     * Plot
     * @var \Runalyze\View\Activity\Plot\ActivityPlot
     */
    protected $Plot;

    /**
     * Boxed values
     * @var BoxedValue[]
     */
    protected $BoxedValues = [];

    /**
     * Additional code
     * @var string
     */
    protected $Code = '';

    /**
     * Additional content
     * @var string
     */
    protected $Header = '';

    /**
     * Additional content
     * @var string
     */
    protected $Footer = '';

    /**
     * With shadow?
     * @var bool
     */
    protected $withShadow = false;

    /**
     * Disable scrolling and show full height
     * @var bool
     */
    protected $big = false;

    public function __construct(?Context $Context = null)
    {
        parent::__construct($Context);

        $this->setPlot();
    }

    abstract protected function setPlot();


    /**
     * Data for the twig template `activity/training_view/row.html.twig`
     *
     * @return array<string, mixed>
     */
    final public function templateData(): array
    {
        if (\Runalyze\Configuration::ActivityView()->showSectionsFullheight()) {
            $this->big = true;
            $this->withShadow = false;
        }

        return [
            'kind' => 'row',
            'with_shadow' => $this->withShadow,
            'info' => $this->infoTemplateData(),
            'plot' => $this->plotTemplateData(),
        ];
    }

    /**
     * Info part, i.e. boxed values and additional content
     *
     * @return array<string, mixed>
     */
    protected function infoTemplateData(): array
    {
        return [
            'class' => $this->big ? 'fullheight' : ($this->withShadow ? 'with-shadow' : ''),
            'boxed_values_html' => $this->boxedValuesHtml(),
            'header' => $this->Header,
            'code' => $this->Code,
            'footer' => $this->Footer,
        ];
    }

    /**
     * Plot part, can be overridden to show other content than a single plot
     *
     * @return array<string, mixed>
     */
    protected function plotTemplateData(): array
    {
        if ($this->Plot === null) {
            return [
                'kind' => 'plot',
                'key' => '',
                'html' => '',
            ];
        }

        return [
            'kind' => 'plot',
            'key' => $this->Plot->getKey(),
            'html' => $this->plotHtml($this->Plot),
        ];
    }

    private function boxedValuesHtml(): string
    {
        $Code = '';

        foreach ($this->BoxedValues as &$Value) {
            $Code .= $Value->getCode();
        }

        return BoxedValue::getWrappedValues($Code);
    }

    /**
     * The plots still echo their content directly, therefore their output is
     * captured here. This is transitional and will be removed when the plots
     * are ported to twig templates.
     */
    final protected function plotHtml(\Runalyze\View\Activity\Plot\ActivityPlot $Plot): string
    {
        ob_start();

        try {
            $Plot->display();
        } catch (Throwable $throwable) {
            ob_end_clean();

            throw $throwable;
        }

        return (string) ob_get_clean();
    }
}
