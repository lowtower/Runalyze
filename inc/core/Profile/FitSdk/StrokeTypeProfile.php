<?php

declare(strict_types=1);

namespace Runalyze\Profile\FitSdk;

use Runalyze\Common\Enum\AbstractEnum;

class StrokeTypeProfile extends AbstractEnum
{
    public const int FREESTYLE = 0;

    public const int BACK = 1;

    public const int BREAST = 2;

    public const int BUTTERFLY = 3;

    public const int DRILL = 4;

    public const int MIXED = 5;

    public const int BREAK = 99;
}
