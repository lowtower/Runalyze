<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class ActivityRoutePrecisionTest extends \PHPUnit\Framework\TestCase
{
    protected ActivityRoutePrecision $object;

    protected function setUp(): void
    {
        $this->object = new ActivityRoutePrecision();
    }

    public function testDefaultValueIsFive(): void
    {
        $this->assertSame('5', $this->object->value());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validOptionProvider')]
    public function testAllDocumentedOptionsAreAccepted(string $option): void
    {
        $this->object->set($option);

        $this->assertSame($option, $this->object->value());
    }

    /**
     * @return \Iterator<string, array<string>>
     */
    public static function validOptionProvider(): \Iterator
    {
        yield 'every data point' => ['1'];
        yield 'every second data point' => ['2'];
        yield 'every fifth data point' => ['5'];
        yield 'every tenth data point' => ['10'];
        yield 'every twentieth data point' => ['20'];
    }

    public function testUnknownValueFallsBackToDefault(): void
    {
        $this->object->set('10');
        $this->object->set('unknown');

        $this->assertSame('5', $this->object->value());
    }
}
