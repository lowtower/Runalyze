<?php

namespace Runalyze\Parser\Activity\Common;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;

abstract class AbstractSingleParser implements ParserInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected ActivityDataContainer $Container;

    public function __construct()
    {
        $this->Container = new ActivityDataContainer();
        $this->setLogger(new NullLogger());
    }

    public function getNumberOfActivities(): int
    {
        return 1;
    }

    public function getActivityDataContainer(int $index = 0): ActivityDataContainer
    {
        return $this->Container;
    }
}
