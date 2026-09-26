<?php

declare(strict_types=1);

namespace Runalyze\Export\Share;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

class AbstractSharerTestFixture extends AbstractSharer
{
    public function isExternalLink(): bool
    {
        return true;
    }

    public function url(): string
    {
        return 'https://example.com/share';
    }

    public function name(): string
    {
        return 'ExampleService';
    }

    public function iconClass(): string
    {
        return 'fa-example';
    }

    public function isPossible(): bool
    {
        return true;
    }

    public function exposeExternalLink(string $text = ''): string
    {
        return $this->externalLink($text);
    }
}

#[Group('default')]
class AbstractSharerTest extends TestCase
{
    private AbstractSharerTestFixture $sharer;

    protected function setUp(): void
    {
        $this->sharer = new AbstractSharerTestFixture(null);
    }

    public function testExternalLinkUsesNameWhenNoTextIsGiven(): void
    {
        $html = $this->sharer->exposeExternalLink();

        $this->assertSame(
            '<a href="https://example.com/share" target="_blank" style="display:block!important"><i class="fa fa-example"></i> <strong>ExampleService</strong></a>',
            $html
        );
    }

    public function testExternalLinkUsesGivenTextInsteadOfName(): void
    {
        $html = $this->sharer->exposeExternalLink('Custom Share Text');

        $this->assertStringContainsString('<strong>Custom Share Text</strong>', $html);
        $this->assertStringNotContainsString('ExampleService', $html);
    }

    public function testExternalLinkAlwaysUsesUrlAndIconClassRegardlessOfText(): void
    {
        $html = $this->sharer->exposeExternalLink('Custom Share Text');

        $this->assertStringContainsString('href="https://example.com/share"', $html);
        $this->assertStringContainsString('fa fa-example', $html);
    }

    public function testExternalLinkTreatsStringZeroAsEmptyAndFallsBackToName(): void
    {
        $html = $this->sharer->exposeExternalLink('0');

        $this->assertStringContainsString('<strong>ExampleService</strong>', $html);
        $this->assertStringNotContainsString('<strong>0</strong>', $html);
    }
}
