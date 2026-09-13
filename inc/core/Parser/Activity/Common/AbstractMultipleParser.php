<?php

namespace Runalyze\Parser\Activity\Common;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;

abstract class AbstractMultipleParser implements ParserInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ActivityDataContainer[]
     */
    protected array $Container = [];

    public function __construct()
    {
        $this->setLogger(new NullLogger());
    }

    public function getNumberOfActivities(): int
    {
        return count($this->Container);
    }

    public function getActivityDataContainer(int $index = 0): ActivityDataContainer
    {
        return $this->Container[$index];
    }
}
