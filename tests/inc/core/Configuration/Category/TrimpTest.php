<?php

declare(strict_types=1);

namespace Runalyze\Configuration\Category;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class TrimpTest extends \PHPUnit\Framework\TestCase
{
    protected Trimp $object;

    protected function setUp(): void
    {
        $this->object = new Trimp();
    }

    public function testDaysForATLDefaultsToSeven(): void
    {
        $this->assertSame(7, $this->object->daysForATL());
    }

    public function testDaysForATLCanBeChanged(): void
    {
        $this->object->setValues([
            'ATL_DAYS' => '10',
        ]);

        $this->assertSame(10, $this->object->daysForATL());
    }

    public function testDaysForCTLDefaultsToFortyTwo(): void
    {
        $this->assertSame(42, $this->object->daysForCTL());
    }

    public function testDaysForCTLCanBeChanged(): void
    {
        $this->object->setValues([
            'CTL_DAYS' => '28',
        ]);

        $this->assertSame(28, $this->object->daysForCTL());
    }

    public function testShowInPercentDefaultsToTrue(): void
    {
        $this->assertTrue($this->object->showInPercent());
    }

    public function testShowInPercentCanBeDisabled(): void
    {
        $this->object->setValues([
            'TRIMP_MODEL_IN_PERCENT' => 'false',
        ]);

        $this->assertFalse($this->object->showInPercent());
    }

    public function testShowTSBinPercentDefaultsToFalse(): void
    {
        $this->assertFalse($this->object->showTSBinPercent());
    }

    public function testShowTSBinPercentCanBeEnabled(): void
    {
        $this->object->setValues([
            'TSB_IN_PERCENT' => 'true',
        ]);

        $this->assertTrue($this->object->showTSBinPercent());
    }

    public function testKeysReturnsAllRegisteredHandlesInDeclarationOrder(): void
    {
        $this->assertSame([
            'ATL_DAYS',
            'CTL_DAYS',
            'TRIMP_MODEL_IN_PERCENT',
            'TSB_IN_PERCENT',
        ], $this->object->keys());
    }

    public function testUpdateFromPostForPercentFlagsOnlySetsReloadFlagWithoutError(): void
    {
        $backup = $_POST;
        $_POST = [
            'TSB_IN_PERCENT' => '1',
            'TSB_IN_PERCENT_sent' => '1',
        ];

        $this->object->updateFromPost();

        $this->assertTrue($this->object->showTSBinPercent());

        $_POST = $backup;
    }
}
