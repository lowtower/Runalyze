<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Statistics\MonthlyStats;

use Runalyze\Bundle\CoreBundle\Services\Selection\Selection;

class AnalysisSelection extends Selection
{
    public const string DISTANCE = 'km';

    public const string TIME = 's';

    public const string ELEVATION = 'em';

    public const string ENERGY = 'kcal';

    public const string TRIMP = 'trimp';

    public const string NUMBER = 'n';

    public function __construct(mixed $currentKey = null)
    {
        parent::__construct([
            self::DISTANCE => 'by distance',
            self::TIME => 'by time',
            self::ELEVATION => 'by elevation',
            self::ENERGY => 'by energy',
            self::TRIMP => 'by trimp',
            self::NUMBER => 'by number',
        ], $currentKey);
    }
}
