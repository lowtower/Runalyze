<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Common;

use Runalyze\Metrics\Common\BaseUnitTrait;

abstract class BaseTraitTestSubject
{
    use BaseUnitTrait;
}

class BaseUnitTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testThatConversionWorksAsExpected()
    {
        /** @var BaseTraitTestSubject&\PHPUnit\Framework\MockObject\MockObject $mock */
        $mock = $this->getMockForTrait(BaseUnitTrait::class);

        $this->assertEquals(3.14, $mock->fromBaseUnit(3.14));
        $this->assertEquals(42.195, $mock->toBaseUnit(42.195));
    }
}
