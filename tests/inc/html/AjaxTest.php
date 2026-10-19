<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('default')]
final class AjaxTest extends TestCase
{
    #[DataProvider('combinationProvider')]
    public function testCombineReloadFlagsProducesExpectedResult(int $first, int $second, int $expected): void
    {
        $this->assertSame($expected, Ajax::combineReloadFlags($first, $second));
        $this->assertSame($expected, Ajax::combineReloadFlags($second, $first), 'combination must be order-independent');
    }

    /**
     * @return \Iterator<string, array<int, int>>
     */
    public static function combinationProvider(): \Iterator
    {
        yield 'none + databrowser stays databrowser' => [Ajax::$RELOAD_NONE, Ajax::$RELOAD_DATABROWSER, Ajax::$RELOAD_DATABROWSER];
        yield 'databrowser + training merges' => [Ajax::$RELOAD_DATABROWSER, Ajax::$RELOAD_TRAINING, Ajax::$RELOAD_DATABROWSER_AND_TRAINING];
        yield 'databrowser + plugins escalates to all' => [Ajax::$RELOAD_DATABROWSER, Ajax::$RELOAD_PLUGINS, Ajax::$RELOAD_ALL];
        yield 'training + plugins escalates to all' => [Ajax::$RELOAD_TRAINING, Ajax::$RELOAD_PLUGINS, Ajax::$RELOAD_ALL];
        yield 'databrowser and training + plugins escalates to all' => [Ajax::$RELOAD_DATABROWSER_AND_TRAINING, Ajax::$RELOAD_PLUGINS, Ajax::$RELOAD_ALL];
        yield 'databrowser + databrowser and training stays combined' => [Ajax::$RELOAD_DATABROWSER, Ajax::$RELOAD_DATABROWSER_AND_TRAINING, Ajax::$RELOAD_DATABROWSER_AND_TRAINING];
        yield 'all trumps plugins' => [Ajax::$RELOAD_ALL, Ajax::$RELOAD_PLUGINS, Ajax::$RELOAD_ALL];
        yield 'page trumps all' => [Ajax::$RELOAD_PAGE, Ajax::$RELOAD_ALL, Ajax::$RELOAD_PAGE];
        yield 'identical flags are idempotent' => [Ajax::$RELOAD_TRAINING, Ajax::$RELOAD_TRAINING, Ajax::$RELOAD_TRAINING];
    }
}
