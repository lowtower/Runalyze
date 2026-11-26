<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Goals\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

#[Group('plugin')]
final class GoalsPanelTemplateTest extends TestCase
{
    public function testRendersRangeNavigationAndHidesInactiveRanges(): void
    {
        $html = $this->twig()->render('@Goals/panel.html.twig', [
            'plugin_id' => 5,
            'panel_data' => [
                'ranges' => [
                    [
                        'key' => 'woche',
                        'name' => 'Week',
                        'start' => '16.11.2026',
                        'end' => '22.11.2026',
                        'duration_days' => 7,
                        'rows' => [
                            [
                                'name' => 'Current',
                                'lvl' => 1,
                                'sep' => false,
                                'km' => 10.0,
                                'anz' => 2.0,
                            ],
                            [
                                'name' => 'Me',
                                'type' => 'bar',
                                'val' => 50.0,
                                'max' => 100.0,
                                'color' => '#9f9',
                                'add' => null,
                                'addcolor' => null,
                            ],
                            [
                                'name' => 'Pace&nbsp;Bunny',
                                'type' => 'bar',
                                'val' => 30.0,
                                'max' => 100.0,
                                'color' => '#ccf',
                                'add' => 20.0,
                                'addcolor' => '#ddf',
                            ],
                        ],
                    ],
                    [
                        'key' => 'mon',
                        'name' => 'Month',
                        'start' => '01.11.2026',
                        'end' => '30.11.2026',
                        'duration_days' => 30,
                        'rows' => [],
                    ],
                ],
            ],
        ]);

        $this->assertStringContainsString('target="bunny" href="#bunny_woche"', $html);
        $this->assertStringContainsString('target="bunny" href="#bunny_mon"', $html);
        $this->assertStringContainsString('id="bunny_woche" class="change"', $html);
        $this->assertStringContainsString('id="bunny_mon" class="change" style="display:none;"', $html);
        $this->assertStringContainsString('10.0 km', $html);
        $this->assertStringContainsString('width:50%', $html);
        $this->assertStringContainsString('width:20%', $html);
        $this->assertStringContainsString('16.11.2026 to 22.11.2026 (7 days)', $html);
    }

    public function testRendersAnEmptyPanelWithoutRangeNavigation(): void
    {
        $html = $this->twig()->render('@Goals/panel.html.twig', [
            'plugin_id' => 5,
            'panel_data' => [
                'ranges' => [],
            ],
        ]);

        $this->assertStringContainsString('id="bunny"', $html);
        $this->assertStringNotContainsString('target="bunny"', $html);
    }

    public function testConfigurationFormPreservesLegacySettingNames(): void
    {
        $html = $this->twig()->render('@Goals/configuration.html.twig', [
            'plugin_id' => 5,
            'config_data' => [
                'ranges' => [
                    [
                        'key' => 'woche',
                        'name' => 'Week',
                        'start' => '16.11.2026',
                        'end' => '22.11.2026',
                        'note' => null,
                        'show' => true,
                        'goal' => 120,
                    ],
                    [
                        'key' => 'mon',
                        'name' => 'Month',
                        'start' => '01.11.2026',
                        'end' => '30.11.2026',
                        'note' => null,
                        'show' => false,
                        'goal' => 0,
                    ],
                ],
                'show_details' => false,
                'active' => 1,
            ],
        ]);

        $this->assertStringContainsString('name="ziel_show_woche" value="1" checked', $html);
        $this->assertStringContainsString('name="ziel_woche" min="0" step="1" value="120"', $html);
        $this->assertStringContainsString('name="ziel_show_mon" value="1"', $html);
        $this->assertStringNotContainsString('name="ziel_show_mon" value="1" checked', $html);
        $this->assertStringContainsString('name="details" value="1"', $html);
        $this->assertStringNotContainsString('name="details" value="1" checked', $html);
        $this->assertStringContainsString('name="edit" value="true"', $html);
    }

    private function twig(): Environment
    {
        $projectDirectory = dirname(__DIR__, 5);
        $loader = new FilesystemLoader($projectDirectory . '/templates');
        $loader->addPath($projectDirectory . '/plugins/Panels/Goals/templates', 'Goals');

        $twig = new Environment($loader, [
            'strict_variables' => true,
        ]);
        $twig->addFilter(new TwigFilter('trans', static fn (string $message): string => $message));
        $twig->addFunction(new TwigFunction('path', static fn (string $route, array $parameters = []): string => '/plugin/config?id=' . ($parameters['id'] ?? 0)));
        $twig->addFunction(new TwigFunction('url', static fn (string $route, array $parameters = []): string => '/plugin/config?id=' . ($parameters['id'] ?? 0)));
        $twig->addFunction(new TwigFunction('distance', static fn (float $km, mixed $unit = null, int $decimals = 2): string => number_format($km, $decimals) . ' km'));

        return $twig;
    }
}
