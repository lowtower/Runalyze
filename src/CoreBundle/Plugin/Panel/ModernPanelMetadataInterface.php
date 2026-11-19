<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Plugin\Panel;

/**
 * Metadata required by the plugin administration UI without a legacy Plugin instance.
 */
interface ModernPanelMetadataInterface
{
    public function getName(): string;

    public function getDescription(): string;
}
