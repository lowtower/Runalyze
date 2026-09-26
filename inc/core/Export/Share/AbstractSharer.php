<?php

/**
 * This file contains class::AbstractSharer
 * @package Runalyze\Export\Share
 */

namespace Runalyze\Export\Share;

use HTML;
use Runalyze\Export\AbstractExporter;
use Runalyze\View\Activity\Linker;
use System;

/**
 * Create sharing links for given type
 *
 * @author Hannes Christiansen
 * @package Runalyze\Export\Share
 */
abstract class AbstractSharer extends AbstractExporter
{
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
            $Text .= '(' . $this->Context->dataview()->pace()->valueWithAppendix() . ')';
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

    final protected function publicURL(): string
    {
        $Linker = new Linker($this->Context->activity());

        return $Linker->publicUrl();
    }

    final protected function externalLink(string $text = ''): string
    {
        if (empty($text)) {
            $text = $this->name();
        }

        return '<a href="' . $this->url() . '" target="_blank" style="display:block!important"><i class="fa ' . $this->iconClass() . '"></i> <strong>' . $text . '</strong></a>';
    }

    final protected function errorForLocalhost(): string
    {
        if (System::isAtLocalhost()) {
            return HTML::error(
                __('Runalyze is running on a local server.') . ' ' .
                __('Linking your activity in a social network does not make sense - nobody will be able to see your activity.')
            );
        }

        return '';
    }
}
