<?php

declare(strict_types=1);

/**
 * This file contains class::Twitter
 * @package Runalyze\Export\Share
 */

namespace Runalyze\Export\Share;

use System;

/**
 * Exporter for: Twitter
 *
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\Export\Share
 */
class Twitter extends AbstractSocialSharer
{
    public function iconClass(): string
    {
        return 'fab fa-twitter color-twitter';
    }

    public function isPossible(): bool
    {
        return true;
    }

    public function url(): string
    {
        if ($this->Context->activity()->isPublic() && ! System::isAtLocalhost()) {
            $urlPart = 'url=' . urlencode($this->publicURL() . '?utm_medium=referral&utm_source=twitter') . '&';
        } else {
            $urlPart = '';
        }

        return 'https://twitter.com/intent/tweet?' . $urlPart . 'text=' . $this->text() . '&via=RunalyzeDE';
    }

    public function name(): string
    {
        return __('Twitter');
    }
}
