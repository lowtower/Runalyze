<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Common\Unit;

use Runalyze\Metrics\Common\BaseUnitTrait;
use Runalyze\Metrics\Common\UnitInterface;

class Simple implements UnitInterface
{
    use BaseUnitTrait;

    public function __construct(
        protected string $Appendix
    ) {}

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return $this->Appendix;
    }
}
