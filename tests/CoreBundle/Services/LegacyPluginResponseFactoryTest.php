<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

#[Group('plugin')]
final class LegacyPluginResponseFactoryTest extends TestCase
{
    public function testReturnsContentWithoutWrappingWhenRequestIsNull(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects($this->never())->method('render');

        $response = new LegacyPluginResponseFactory($twig)->create('Legacy content', null);

        $this->assertSame('Legacy content', $response->getContent());
    }

    public function testReturnsContentWithoutWrappingForXmlHttpRequest(): void
    {
        $request = Request::create('/');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $twig = $this->createMock(Environment::class);
        $twig->expects($this->never())->method('render');

        $response = new LegacyPluginResponseFactory($twig)->create('Legacy content', $request);

        $this->assertSame('Legacy content', $response->getContent());
    }

    public function testWrapsContentInLegacyWindowTemplateForRegularRequest(): void
    {
        $request = Request::create('/');
        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with('legacy_window.html.twig', [
                'legacyContent' => 'Legacy content',
            ])
            ->willReturn('<html>Legacy content</html>');

        $response = new LegacyPluginResponseFactory($twig)->create('Legacy content', $request);

        $this->assertSame('<html>Legacy content</html>', $response->getContent());
    }
}
