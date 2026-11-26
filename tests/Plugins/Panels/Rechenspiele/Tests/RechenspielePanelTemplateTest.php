<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFilter;

#[Group('plugin')]
final class RechenspielePanelTemplateTest extends TestCase
{
    public function testEscapedNonBreakingSpaceInTranslatedLabelRendersAsWhitespace(): void
    {
        $template = file_get_contents(
            dirname(__DIR__, 5) . '/plugins/Panels/Rechenspiele/templates/panel.html.twig'
        );

        $this->assertIsString($template);

        $twig = new Environment(new ArrayLoader([
            'my/panels/base.html.twig' => '{% block panelContent %}{% endblock %}',
            '@Rechenspiele/panel.html.twig' => $template,
        ]), [
            'autoescape' => 'html',
        ]);

        $twig->addFilter(new TwigFilter('trans', static fn (string $label): string => $label === 'Easy TRIMP'
            ? 'Entspannter&nbsp;TRIMP'
            : $label));

        $output = $twig->render('@Rechenspiele/panel.html.twig', [
            'panel_data' => [
                'rows' => [[
                    'label' => 'Easy TRIMP',
                    'abbreviation' => '',
                    'value' => '4',
                    'percent' => 4,
                    'tone' => 'blue',
                    'glossary' => null,
                ]],
                'missing_vo2max' => false,
                'insufficient_activities' => false,
            ],
        ]);

        $this->assertStringContainsString('Entspannter TRIMP', $output);
        $this->assertStringNotContainsString('&amp;nbsp;', $output);
        $this->assertStringNotContainsString('&nbsp;', $output);
    }
}
