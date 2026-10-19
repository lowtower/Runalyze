<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Import;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerFilter;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class ActivityDataContainerFilterTest extends TestCase
{
    private function getLogger(ActivityDataContainerFilter $filter): LoggerInterface
    {
        $property = new ReflectionProperty($filter, 'logger');

        return $property->getValue($filter);
    }

    public function testUsesANullLoggerByDefault(): void
    {
        $filter = new ActivityDataContainerFilter();

        $this->assertInstanceOf(NullLogger::class, $this->getLogger($filter));
    }

    public function testUsesTheGivenLoggerWhenProvided(): void
    {
        $logger = $this->createStub(LoggerInterface::class);

        $filter = new ActivityDataContainerFilter($logger);

        $this->assertSame($logger, $this->getLogger($filter));
    }
}
