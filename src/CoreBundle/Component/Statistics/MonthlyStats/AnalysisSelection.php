<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Statistics\MonthlyStats;

use Runalyze\Bundle\CoreBundle\Services\Selection\Selection;

class AnalysisSelection extends Selection
{
    /**
     * @var string
     */
    public const DISTANCE = 'km';

    /**
     * @var string
     */
    public const TIME = 's';

    /**
     * @var string
     */
    public const ELEVATION = 'em';

    /**
     * @var string
     */
    public const ENERGY = 'kcal';

    /**
     * @var string
     */
    public const TRIMP = 'trimp';

    /**
     * @var string
     */
    public const NUMBER = 'n';

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
