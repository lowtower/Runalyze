<?php

declare(strict_types=1);

/**
 * Additional helper functions
 *
 * This file is included by composer's autoload mechanism.
 * Requiring global functions is considered bad practice
 * but this will stay until all old code smells are removed.
 */
/**
 * Returns the translation for a textstring
 */
function __(string $text): string
{
    return gettext($text);
}

/**
 * Echo the translation for a textstring
 */
function _e(string $text): void
{
    echo gettext($text);
}

/**
 * Return singular/plural translation for a textstring
 */
function _n(string $msg1, string $msg2, int $n): string
{
    return ngettext($msg1, $msg2, $n);
}
