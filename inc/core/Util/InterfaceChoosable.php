<?php

declare(strict_types=1);

namespace Runalyze\Util;

interface InterfaceChoosable
{
    /**
     * @return array (name => database/form value)
     */
    public static function getChoices();
}
