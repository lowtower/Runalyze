<?php

declare(strict_types=1);

/**
 * @deprecated since v3.1
 */
class FrontendShared extends Frontend
{
    /**
     * @var bool
     */
    public static $IS_SHOWN = false;

    public function displayHeader(): void
    {
        self::$IS_SHOWN = true;
    }

    public function displayFooter(): void
    {
    }

    protected function initSessionAccountHandler(): void
    {
    }
}
