<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Characterization test for RunalyzePluginStat_Trainingszeiten.
 */
#[Group('plugin')]
class RunalyzePluginStat_TrainingszeitenCharacterizationTest extends TestCase
{
    private RunalyzePluginStat_Trainingszeiten $plugin;

    protected function setUp(): void
    {
        $this->plugin = (new \ReflectionClass(RunalyzePluginStat_Trainingszeiten::class))
            ->newInstanceWithoutConstructor();
    }

    #[DataProvider('provideEvenIndices')]
    public function testShouldOpenRowIsTrueForEvenIndices(int $index): void
    {
        $this->assertTrue($this->callShouldOpenRow($index));
    }

    /**
     * @return array<string, int[]>
     */
    public static function provideEvenIndices(): array
    {
        return [
            'index 0' => [0],
            'index 2' => [2],
            'index 18' => [18],
        ];
    }

    #[DataProvider('provideOddIndices')]
    public function testShouldOpenRowIsFalseForOddIndices(int $index): void
    {
        $this->assertFalse($this->callShouldOpenRow($index));
    }

    /**
     * @return array<string, int[]>
     */
    public static function provideOddIndices(): array
    {
        return [
            'index 1' => [1],
            'index 3' => [3],
            'index 19' => [19],
        ];
    }

    public function testShouldCloseRowIsTrueForOddIndexRegardlessOfTotalCount(): void
    {
        $this->assertTrue($this->callShouldCloseRow(1, 10));
        $this->assertTrue($this->callShouldCloseRow(3, 4));
    }

    public function testShouldCloseRowIsFalseForEvenIndexWhenNotTheLastElement(): void
    {
        $this->assertFalse($this->callShouldCloseRow(0, 4));
        $this->assertFalse($this->callShouldCloseRow(2, 20));
    }

    public function testShouldCloseRowIsTrueForEvenIndexWhenItIsTheLastElement(): void
    {
        $this->assertTrue($this->callShouldCloseRow(2, 3));
        $this->assertTrue($this->callShouldCloseRow(4, 5));
        $this->assertTrue($this->callShouldCloseRow(0, 1));
    }

    #[DataProvider('provideTotalCounts')]
    public function testShouldOpenAndCloseRowProduceBalancedPairsForAnyTotalCount(int $totalCount): void
    {
        $openCount = 0;
        $closeCount = 0;

        for ($i = 0; $i < $totalCount; $i++) {
            if ($this->callShouldOpenRow($i)) {
                $openCount++;
            }

            if ($this->callShouldCloseRow($i, $totalCount)) {
                $closeCount++;
            }
        }

        $this->assertSame($openCount, $closeCount);

        if ($totalCount > 0) {
            $this->assertTrue($this->callShouldCloseRow($totalCount - 1, $totalCount));
        }
    }

    public static function provideTotalCounts(): array
    {
        return array_map(static fn (int $n): array => [$n], range(0, 20));
    }

    private function callShouldOpenRow(int $index): bool
    {
        $closure = \Closure::bind(
            fn (int $index): bool => $this->shouldOpenRow($index),
            $this->plugin,
            RunalyzePluginStat_Trainingszeiten::class
        );

        return $closure($index);
    }

    private function callShouldCloseRow(int $index, int $totalCount): bool
    {
        $closure = \Closure::bind(
            fn (int $index, int $totalCount): bool => $this->shouldCloseRow($index, $totalCount),
            $this->plugin,
            RunalyzePluginStat_Trainingszeiten::class
        );

        return $closure($index, $totalCount);
    }
}
