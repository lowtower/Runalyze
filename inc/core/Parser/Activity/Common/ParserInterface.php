<?php

declare(strict_types=1);

namespace Runalyze\Parser\Activity\Common;

use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;

interface ParserInterface
{
    public function parse();

    public function getNumberOfActivities(): int;

    public function getActivityDataContainer(int $index = 0): ActivityDataContainer;
}
