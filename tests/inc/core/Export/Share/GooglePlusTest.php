<?php

declare(strict_types=1);

namespace Runalyze\Export\Share;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('default')]
class GooglePlusTest extends TestCase
{
    public function testIconClassReturnsGooglePlusSpecificCssClasses(): void
    {
        $GooglePlus = new GooglePlus(null);

        $this->assertSame('fab fa-google-plus color-google-plus', $GooglePlus->iconClass());
    }

    public function testNameReturnsGooglePlus(): void
    {
        $GooglePlus = new GooglePlus(null);

        $this->assertSame('Google+', $GooglePlus->name());
    }

    public function testIsExternalLinkIsTrue(): void
    {
        $GooglePlus = new GooglePlus(null);

        $this->assertTrue($GooglePlus->isExternalLink());
    }
}
