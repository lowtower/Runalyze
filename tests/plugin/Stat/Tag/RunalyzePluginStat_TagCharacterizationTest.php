<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Characterization test for RunalyzePluginStat_Tag's HTML rendering.
 */
#[Group('plugin')]
#[Group('requiresDoctrine')]
final class RunalyzePluginStat_TagCharacterizationTest extends TestCase
{
    private RunalyzePluginStat_Tag $plugin;

    protected function setUp(): void
    {
        $this->plugin = new class() extends RunalyzePluginStat_Tag {
            public function __construct()
            {
            }

            public function getInnerLink(string $name, int $sport = 0, int $year = 0, string $dat = ''): string
            {
                return '<a href="?dat=' . $dat . '">' . $name . '</a>';
            }
        };
    }

    public function testToolbarNavigationLinksSnapshotForMultipleTagsWithOneActive(): void
    {
        $this->setProtectedProperty('AllTags', [
            1 => 'Trailrun',
            2 => 'Intervalle',
            3 => 'Long Run',
        ]);
        $this->setProtectedProperty('TagId', 2);

        $links = $this->callPrivateMethod('getToolbarNavigationLinks');

        $this->assertSame(
            [
                '<li class="with-submenu"><span class="link">Intervalle</span><ul class="submenu">',
                '<li><a href="?dat=1">Trailrun</a></li>',
                '<li class="active"><a href="?dat=2">Intervalle</a></li>',
                '<li><a href="?dat=3">Long Run</a></li>',
                '</ul></li>',
            ],
            $links
        );
    }

    public function testToolbarNavigationLinksSnapshotWhenNoTagIsSelected(): void
    {
        $this->setProtectedProperty('AllTags', [
            1 => 'Trailrun',
        ]);
        $this->setProtectedProperty('TagId', 0);

        $links = $this->callPrivateMethod('getToolbarNavigationLinks');

        $this->assertSame([], $links);
    }

    public function testToolbarNavigationLinksSnapshotWhenNoTagsExistAtAll(): void
    {
        $this->setProtectedProperty('AllTags', []);
        $this->setProtectedProperty('TagId', 0);

        $links = $this->callPrivateMethod('getToolbarNavigationLinks');

        $this->assertSame([], $links);
    }

    private function setProtectedProperty(string $name, mixed $value): void
    {
        $property = new \ReflectionProperty(RunalyzePluginStat_Tag::class, $name);
        $property->setValue($this->plugin, $value);
    }

    private function callPrivateMethod(string $name, mixed ...$arguments): mixed
    {
        $method = new \ReflectionMethod(RunalyzePluginStat_Tag::class, $name);

        return $method->invoke($this->plugin, ...$arguments);
    }
}
