<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Twig;

use Runalyze\Bundle\CoreBundle\Twig\HtmlExtension;

class HtmlExtensionTest extends \PHPUnit\Framework\TestCase
{
    protected HtmlExtension $Html;

    protected function setUp(): void
    {
        $this->Html = new HtmlExtension();
    }

    public function testAddingNoBreakSpaceCharacters(): void
    {
        $this->assertSame('test&nbsp;foo&nbsp;&nbsp;bar', $this->Html->nbsp('test foo  bar'));
    }

    public function testRemovingNoBreakSpaceCharacters(): void
    {
        $this->assertSame('test foo  bar', $this->Html->nonbsp('test&nbsp;foo&nbsp;&nbsp;bar'));
    }
}
