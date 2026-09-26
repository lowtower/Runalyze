<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

#[\PHPUnit\Framework\Attributes\Group('default')]
class ActivityRoutePrecisionTest extends \PHPUnit\Framework\TestCase
{
    protected ActivityRoutePrecision $object;

    protected function setUp(): void
    {
        $this->object = new ActivityRoutePrecision();
    }

    public function testDefaultValueIsFive(): void
    {
        self::assertSame('5', $this->object->value());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validOptionProvider')]
    public function testAllDocumentedOptionsAreAccepted(string $option): void
    {
        $this->object->set($option);

        self::assertSame($option, $this->object->value());
    }

    /**
     * @return array<string, string[]>
     */
    public static function validOptionProvider(): array
    {
        return [
            'every data point' => ['1'],
            'every second data point' => ['2'],
            'every fifth data point' => ['5'],
            'every tenth data point' => ['10'],
            'every twentieth data point' => ['20'],
        ];
    }

    public function testUnknownValueFallsBackToDefault(): void
    {
        $this->object->set('10');
        $this->object->set('unknown');

        self::assertSame('5', $this->object->value());
    }
}
