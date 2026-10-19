<?php

declare(strict_types=1);

namespace Runalyze\Export\Share;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

class AbstractSnippetSharerTestFixture extends AbstractSnippetSharer
{
    public function name(): string
    {
        return 'FixtureSnippet';
    }

    public function isPossible(): bool
    {
        return true;
    }

    public function enum(): int
    {
        return 1;
    }

    public function display(): void
    {
    }

    protected function codeSnippet(): string
    {
        return '';
    }
}

#[Group('default')]
final class AbstractSnippetSharerTest extends TestCase
{
    public function testIconClassReturnsFaCode(): void
    {
        $Sharer = new AbstractSnippetSharerTestFixture(null);

        $this->assertSame('fa-code', $Sharer->iconClass());
    }

    public function testIsExternalLinkIsFalse(): void
    {
        $Sharer = new AbstractSnippetSharerTestFixture(null);

        $this->assertFalse($Sharer->isExternalLink());
    }
}
