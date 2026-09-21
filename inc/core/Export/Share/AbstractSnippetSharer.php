<?php

declare(strict_types=1);

/**
 * This file contains class::AbstractSnippetSharer
 * @package Runalyze\Export\Share
 */

namespace Runalyze\Export\Share;

/**
 * Exporter for: code snippets
 *
 * @author Hannes Christiansen
 * @package Runalyze\Export\Share
 */
abstract class AbstractSnippetSharer extends AbstractSharer
{
    /**
     * @var string
     */
    public const URL = 'activity/{id}/export/social/{enum}';

    public function iconClass(): string
    {
        return 'fa-code';
    }

    public function isExternalLink(): bool
    {
        return false;
    }

    public function url(): string
    {
        return str_replace(
            ['{id}', '{enum}'],
            [(string) $this->Context->activity()->id(), (string) $this->enum()],
            self::URL
        );
    }

    abstract public function enum(): int;

    abstract public function display(): void;

    abstract protected function codeSnippet(): string;
}
