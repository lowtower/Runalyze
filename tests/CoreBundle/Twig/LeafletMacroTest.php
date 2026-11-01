<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Twig;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class LeafletMacroTest extends \PHPUnit\Framework\TestCase
{
    private function renderMapWithLayer(string $layer): string
    {
        $twig = new Environment(
            new FilesystemLoader(dirname(__DIR__, 3) . '/templates'),
            [
                'autoescape' => 'html',
            ]
        );
        $twig->addFunction(new TwigFunction('config', fn (): null => null));

        return $twig->createTemplate(
            '{% import "_macro/leaflet.html.twig" as leaflet %}{{ leaflet.map("map", {"layer": layer}) }}'
        )->render([
            'layer' => $layer,
        ]);
    }

    public function testMapLayerIsEncodedForJavaScript(): void
    {
        $html = $this->renderMapWithLayer('x");alert(1);//</script>');

        $this->assertStringContainsString(
            'setDefaultLayer("x\u0022);alert(1);\/\/\u003C\/script\u003E")',
            $html
        );
        $this->assertStringNotContainsString(
            'setDefaultLayer("x");alert(1);//</script>")',
            $html
        );
    }
}
