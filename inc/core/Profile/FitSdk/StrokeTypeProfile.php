<?php

declare(strict_types=1);

namespace Runalyze\Profile\FitSdk;

use Runalyze\Common\Enum\AbstractEnum;

class StrokeTypeProfile extends AbstractEnum
{
    /**
     * @var int
     */
    public const FREESTYLE = 0;

    /**
     * @var int
     */
    public const BACK = 1;

    /**
     * @var int
     */
    public const BREAST = 2;

    /**
     * @var int
     */
    public const BUTTERFLY = 3;

    /**
     * @var int
     */
    public const DRILL = 4;

    /**
     * @var int
     */
    public const MIXED = 5;

    /**
     * @var int
     */
    public const BREAK = 99;
}
