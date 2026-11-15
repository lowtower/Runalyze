<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginOutputRenderer;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

#[Group('plugin')]
final class LegacyPluginOutputRendererTest extends TestCase
{
    public function testReturnsLegacyOutputAsResponse(): void
    {
        $renderer = new LegacyPluginOutputRenderer();

        $response = $renderer->render(function (): void {
            echo '<div>Legacy plugin content</div>';
        });

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('<div>Legacy plugin content</div>', $response->getContent());
    }

    public function testRestoresOutputBufferWhenLegacyCallbackThrows(): void
    {
        $renderer = new LegacyPluginOutputRenderer();
        $outputBufferLevel = ob_get_level();

        try {
            $renderer->render(function (): void {
                throw new RuntimeException('Legacy plugin rendering failed.');
            });
            $this->fail('Expected the legacy callback to throw.');
        } catch (RuntimeException $runtimeException) {
            $this->assertSame('Legacy plugin rendering failed.', $runtimeException->getMessage());
        }

        $this->assertSame($outputBufferLevel, ob_get_level());
    }
}
