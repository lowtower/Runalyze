<?php

declare(strict_types=1);

namespace Runalyze\Export\Share;

use Runalyze\Model\Activity;
use Runalyze\View\Activity\FakeContext;

final class IFrameTest extends \PHPUnit\Framework\TestCase
{
    public function testFeasibility(): void
    {
        $this->assertFalse(new IFrame(FakeContext::onlyWithActivity(
            new Activity\Entity([
                Activity\Entity::IS_PUBLIC => false,
            ])
        ))->isPossible());

        $this->assertTrue(new IFrame(FakeContext::onlyWithActivity(
            new Activity\Entity([
                Activity\Entity::IS_PUBLIC => true,
            ])
        ))->isPossible());
    }

    public function testThatCodeCanBeCreated(): void
    {
        ob_start();

        $context = FakeContext::emptyContext();
        $Sharer = new IFrame($context);
        $Sharer->display('https://example.test/shared/1', false);

        $output = ob_get_clean();

        $this->assertNotEmpty($output, 'Output should not be empty after displaying the IFrame.');
        $this->assertStringContainsString('<iframe', (string) $output, 'Output should contain an iframe tag.');
        $this->assertFalse($context->activity()->isPublic());
    }
}
