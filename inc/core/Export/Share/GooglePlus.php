<?php

declare(strict_types=1);

/**
 * This file contains class::GooglePlus
 * @package Runalyze\Export\Share
 */

namespace Runalyze\Export\Share;

use System;

/**
 * Exporter for: Google+
 *
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\Export\Share
 */
class GooglePlus extends AbstractSocialSharer
{
    public function iconClass(): string
    {
        return 'fa-google-plus color-google-plus';
    }

    public function isPossible(): bool
    {
        return $this->Context->activity()
            ->isPublic() && ! System::isAtLocalhost();
    }

    public function url(): string
    {
        return 'https://plus.google.com/share?url=' . urlencode($this->publicURL() . '?utm_medium=referral&utm_source=googleplus');
    }

    public function name(): string
    {
        return __('Google+');
    }
}
