<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Form\Type;

use Runalyze\Bundle\CoreBundle\Form\Type\DurationNullableType;

final class DurationNullableTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DurationNullableType
     */
    protected $Type;

    protected function setUp(): void
    {
        $this->Type = new DurationNullableType();
    }

    public function testReverseTransform(): void
    {
        $this->assertNull($this->Type->reverseTransform('0:00'));
    }
}
