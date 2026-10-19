<?php

declare(strict_types=1);

namespace Runalyze\Export\Share;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('default')]
final class FacebookTest extends TestCase
{
    public function testIconClassReturnsFacebookSpecificCssClasses(): void
    {
        $Facebook = new Facebook(null);

        $this->assertSame('fab fa-facebook color-facebook', $Facebook->iconClass());
    }

    public function testNameReturnsFacebook(): void
    {
        $Facebook = new Facebook(null);

        $this->assertSame('Facebook', $Facebook->name());
    }

    public function testIsExternalLinkIsTrue(): void
    {
        $Facebook = new Facebook(null);

        $this->assertTrue($Facebook->isExternalLink());
    }
}
