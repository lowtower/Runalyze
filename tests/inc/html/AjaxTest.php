<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('default')]
class AjaxTest extends TestCase
{
    #[DataProvider('combinationProvider')]
    public function testCombineReloadFlagsProducesExpectedResult(int $first, int $second, int $expected): void
    {
        self::assertSame($expected, Ajax::combineReloadFlags($first, $second));
        self::assertSame($expected, Ajax::combineReloadFlags($second, $first), 'combination must be order-independent');
    }

    /**
     * @return array<string, array<int, int>>
     */
    public static function combinationProvider(): array
    {
        return [
            'none + databrowser stays databrowser' => [Ajax::$RELOAD_NONE, Ajax::$RELOAD_DATABROWSER, Ajax::$RELOAD_DATABROWSER],
            'databrowser + training merges' => [Ajax::$RELOAD_DATABROWSER, Ajax::$RELOAD_TRAINING, Ajax::$RELOAD_DATABROWSER_AND_TRAINING],
            'databrowser + plugins escalates to all' => [Ajax::$RELOAD_DATABROWSER, Ajax::$RELOAD_PLUGINS, Ajax::$RELOAD_ALL],
            'training + plugins escalates to all' => [Ajax::$RELOAD_TRAINING, Ajax::$RELOAD_PLUGINS, Ajax::$RELOAD_ALL],
            'databrowser and training + plugins escalates to all' => [Ajax::$RELOAD_DATABROWSER_AND_TRAINING, Ajax::$RELOAD_PLUGINS, Ajax::$RELOAD_ALL],
            'databrowser + databrowser and training stays combined' => [Ajax::$RELOAD_DATABROWSER, Ajax::$RELOAD_DATABROWSER_AND_TRAINING, Ajax::$RELOAD_DATABROWSER_AND_TRAINING],
            'all trumps plugins' => [Ajax::$RELOAD_ALL, Ajax::$RELOAD_PLUGINS, Ajax::$RELOAD_ALL],
            'page trumps all' => [Ajax::$RELOAD_PAGE, Ajax::$RELOAD_ALL, Ajax::$RELOAD_PAGE],
            'identical flags are idempotent' => [Ajax::$RELOAD_TRAINING, Ajax::$RELOAD_TRAINING, Ajax::$RELOAD_TRAINING],
        ];
    }
}
