<?php

/**
 * This file contains class::AbstractSharer
 * @package Runalyze\Export\Share
 */

namespace Runalyze\Export\Share;

use Runalyze\Bundle\CoreBundle\Component\Activity\PaceFormatter;
use Runalyze\Export\AbstractExporter;
use Runalyze\View\Activity\Context;

/**
 * Create sharing links for given type
 *
 * @author Hannes Christiansen
 * @package Runalyze\Export\Share
 */
abstract class AbstractSharer extends AbstractExporter
{
    public function __construct(
        ?Context $context,
        protected readonly ?string $publicActivityUrl = null,
        protected readonly ?string $shareImageUrl = null
    ) {
        parent::__construct($context);
    }

    abstract public function isExternalLink(): bool;

    abstract public function url(): string;

    abstract public function name(): string;

    final protected function text()
    {
        $Text = __('I did sport: ');

        if ($this->Context->activity()->distance() > 0) {
            $Text .= $this->Context->dataview()
                ->distance() . ' ';
            $Text .= $this->Context->dataview()
                ->titleByTypeOrSport() . __(' in ');
            $Text .= $this->Context->dataview()
                ->duration()
                ->string() . ' ';
            $Text .= '(' . $this->formattedPace() . ')';
        } else {
            $Text .= $this->Context->dataview()
                ->duration()
                ->string() . ' ';
            $Text .= $this->Context->dataview()
                ->titleByTypeOrSport();
        }

        if ($this->Context->activity()->title() !== '') {
            $Text .= ' - ' . $this->Context->activity()->title();
        }

        return strip_tags(str_replace('&nbsp;', '', $Text));
    }

    final protected function formattedPace(): string
    {
        return new PaceFormatter($this->Context->sport()->getSpeedUnit())
            ->format($this->Context->dataview()->paceInSecondsPerKilometer());
    }

    final protected function externalLink(string $text = ''): string
    {
        if (empty($text)) {
            $text = $this->name();
        }

        return '<a href="' . $this->url() . '" target="_blank" style="display:block!important"><i class="fa ' . $this->iconClass() . '"></i> <strong>' . $text . '</strong></a>';
    }
}
