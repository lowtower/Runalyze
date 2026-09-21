<?php

declare(strict_types=1);

/**
 * This file contains class::AbstractSocialSharer
 * @package Runalyze\Export\Share
 */

namespace Runalyze\Export\Share;

/**
 * Exporter for: social sharing
 *
 * @author Hannes Christiansen
 * @package Runalyze\Export\Share
 */
abstract class AbstractSocialSharer extends AbstractSharer
{
    public function isExternalLink(): bool
    {
        return true;
    }
}
