<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Prognose\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Plugins\Panels\Prognose\PrognoseCalculatorResultTableRenderer;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

#[Group('plugin')]
final class PrognoseCalculatorResultTableRendererTest extends TestCase
{
    public function testRendersPredictionAndTrustedPersonalBestActivityLink(): void
    {
        $html = $this->renderer()->render([[
            'distance' => '5.72 km',
            'prognosis' => '0:35:00',
            'prognosis-pace' => '6:07 min/km',
            'prognosis-vo2max' => '40',
            'diff' => '+ 0:01:00',
            'diff-class' => 'plus',
            'pb' => '0:34:00',
            'pb-pace' => '5:57 min/km',
            'pb-vo2max' => '42',
            'pb-date' => '<a href="/activity/51">14.11.2023</a>',
        ]], true);

        $this->assertStringContainsString('class="fullwidth zebra-style"', $html);
        $this->assertStringContainsString('5.72 km', $html);
        $this->assertStringContainsString('0:34:00', $html);
        $this->assertStringContainsString('class="small plus"', $html);
        $this->assertStringContainsString('<a href="/activity/51">14.11.2023</a>', $html);
        $this->assertStringNotContainsString('Your input is invalid', $html);
    }

    public function testEscapesOrdinaryTableValues(): void
    {
        $html = $this->renderer()->render([[
            'distance' => '<script>alert(1)</script>',
            'prognosis' => '-',
            'prognosis-pace' => '-',
            'prognosis-vo2max' => '-',
            'diff' => '-',
            'diff-class' => 'invalid',
            'pb' => '-',
            'pb-pace' => '-',
            'pb-vo2max' => '-',
            'pb-date' => '-',
        ]], true);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('class="small minus"', $html);
    }

    public function testRendersNonbreakingDistanceSpaceWithoutLiteralHtmlEntity(): void
    {
        $html = $this->renderer()->render([[
            'distance' => "5,000\u{00A0}km",
            'prognosis' => '-',
            'prognosis-pace' => '-',
            'prognosis-vo2max' => '-',
            'diff' => '-',
            'diff-class' => 'minus',
            'pb' => '-',
            'pb-pace' => '-',
            'pb-vo2max' => '-',
            'pb-date' => '-',
        ]], true);

        $this->assertStringContainsString("5,000\u{00A0}km", $html);
        $this->assertStringNotContainsString('&amp;nbsp;', $html);
        $this->assertStringNotContainsString('&nbsp;', $html);
    }

    public function testRendersInvalidInputInsteadOfResults(): void
    {
        $html = $this->renderer()->render([], false);

        $this->assertStringContainsString('colspan="9"', $html);
        $this->assertStringContainsString('Your input is invalid', $html);
        $this->assertStringNotContainsString('<tr class="r">', $html);
    }

    private function renderer(): PrognoseCalculatorResultTableRenderer
    {
        $loader = new FilesystemLoader();
        $loader->addPath(dirname(__DIR__, 5) . '/plugins/Panels/Prognose/templates', 'Prognose');

        return new PrognoseCalculatorResultTableRenderer(new Environment($loader));
    }
}
