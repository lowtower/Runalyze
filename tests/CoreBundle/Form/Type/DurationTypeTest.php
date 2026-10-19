<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Form\Type;

use Runalyze\Bundle\CoreBundle\Form\Type\DurationType;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class DurationTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DurationType
     */
    protected $Type;

    protected function setUp(): void
    {
        $this->Type = new DurationType();
    }

    public function testTransform(): void
    {
        $this->assertEquals('0:00', $this->Type->transform(null));
        $this->assertEquals('0:00', $this->Type->transform(0));
        $this->assertEquals('0:27', $this->Type->transform(27));
        $this->assertEquals('1:00:00', $this->Type->transform(3600));
        $this->assertEquals('1d 00:00:12', $this->Type->transform(86412));
    }

    public function testThatNullIsNotPossible(): void
    {
        $this->expectException(TransformationFailedException::class);

        $this->Type->setRequired(true);
        $this->Type->reverseTransform(null);
    }

    public function testReverseTransform(): void
    {
        $this->assertEquals(0, $this->Type->reverseTransform('0:00'));
        $this->assertEquals(194, $this->Type->reverseTransform('3:14'));
        $this->assertEquals(10799, $this->Type->reverseTransform('2:59:59'));
        $this->assertEquals(310500, $this->Type->reverseTransform('3d 14:15:00'));
    }
}
