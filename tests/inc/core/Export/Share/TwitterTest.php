<?php

declare(strict_types=1);

namespace Runalyze\Export\Share;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('default')]
final class TwitterTest extends TestCase
{
    public function testIconClassReturnsTwitterSpecificCssClasses(): void
    {
        $Twitter = new Twitter(null);

        $this->assertSame('fab fa-twitter color-twitter', $Twitter->iconClass());
    }

    public function testNameReturnsTwitter(): void
    {
        $Twitter = new Twitter(null);

        $this->assertSame('Twitter', $Twitter->name());
    }

    public function testIsExternalLinkIsTrue(): void
    {
        $Twitter = new Twitter(null);

        $this->assertTrue($Twitter->isExternalLink());
    }

    public function testIsPossibleIsUnconditionallyTrue(): void
    {
        $Twitter = new Twitter(null);

        $this->assertTrue($Twitter->isPossible());
    }
}
