<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Common\Unit;

use Runalyze\Metrics\Common\BaseUnitTrait;
use Runalyze\Metrics\Common\UnitInterface;

class Simple implements UnitInterface
{
    use BaseUnitTrait;

    /**
     * @param string $Appendix
     */
    public function __construct(
        protected $Appendix
    ) {
    }

    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getAppendix()
    {
        return $this->Appendix;
    }
}
