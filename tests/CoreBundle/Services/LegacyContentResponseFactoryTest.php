<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Services\LegacyContentResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

#[Group('plugin')]
final class LegacyContentResponseFactoryTest extends TestCase
{
    public function testReturnsContentWithoutPageShellWhenRequestIsMissing(): void
    {
        $factory = $this->createFactory();

        $response = $factory->create('<p>Legacy content</p>', null);

        $this->assertSame('<p>Legacy content</p>', $response->getContent());
    }

    public function testReturnsContentWithoutPageShellForXmlHttpRequests(): void
    {
        $factory = $this->createFactory();
        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $factory->create('<p>Legacy content</p>', $request);

        $this->assertSame('<p>Legacy content</p>', $response->getContent());
    }

    public function testRendersPageShellForRegularRequests(): void
    {
        $factory = $this->createFactory();
        $request = new Request();

        $response = $factory->create('<p>Legacy content</p>', $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('<main><p>Legacy content</p></main>', $response->getContent());
    }

    private function createFactory(): LegacyContentResponseFactory
    {
        $twig = new Environment(new ArrayLoader([
            'legacy_window.html.twig' => '<main>{{ legacyContent|raw }}</main>',
        ]));

        return new LegacyContentResponseFactory($twig);
    }
}
