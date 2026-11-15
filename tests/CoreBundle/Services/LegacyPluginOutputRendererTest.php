<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use Plugin;
use PluginInstaller;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginOutputRenderer;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class LegacyPluginOutputRendererFixture extends Plugin
{
    public function __construct(private readonly string $output, private readonly bool $throw = false)
    {
        parent::__construct(PluginInstaller::ID);
    }

    public function name(): string
    {
        return 'Fixture';
    }

    public function description(): string
    {
        return 'Legacy plugin output renderer fixture.';
    }

    public function type(): int
    {
        return 0;
    }

    public function display(): void
    {
        if ($this->throw) {
            throw new RuntimeException('Legacy plugin rendering failed.');
        }

        echo $this->output;
    }

    protected function displayContent(): void {}

    public function getLink(): string
    {
        return '';
    }

    protected function getInnerLink(string $name, int $sport = 0, int $year = 0, string $dat = ''): string
    {
        return '';
    }
}

#[Group('plugin')]
final class LegacyPluginOutputRendererTest extends TestCase
{
    public function testReturnsLegacyPluginOutputAsResponse(): void
    {
        $renderer = new LegacyPluginOutputRenderer();
        $plugin = new LegacyPluginOutputRendererFixture('<div>Legacy plugin content</div>');

        $response = $renderer->render($plugin);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('<div>Legacy plugin content</div>', $response->getContent());
    }

    public function testRestoresOutputBufferWhenLegacyPluginThrows(): void
    {
        $renderer = new LegacyPluginOutputRenderer();
        $plugin = new LegacyPluginOutputRendererFixture('', true);
        $outputBufferLevel = ob_get_level();

        try {
            $renderer->render($plugin);
            $this->fail('Expected the legacy plugin to throw.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Legacy plugin rendering failed.', $exception->getMessage());
        }

        $this->assertSame($outputBufferLevel, ob_get_level());
    }
}
