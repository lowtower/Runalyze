<?php

declare(strict_types=1);

namespace Runalyze\Export\Share;

use Runalyze\Model\Activity;
use Runalyze\View\Activity\FakeContext;

class IFrameTest extends \PHPUnit\Framework\TestCase
{
    public function checkFeasibility()
    {
        $this->assertFalse((new IFrame(FakeContext::onlyWithActivity(
            new Activity\Entity([
                Activity\Entity::IS_PUBLIC => false,
            ])
        )))->isPossible());

        $this->assertTrue((new IFrame(FakeContext::onlyWithActivity(
            new Activity\Entity([
                Activity\Entity::IS_PUBLIC => true,
            ])
        )))->isPossible());
    }

    public function testThatCodeCanBeCreated()
    {
        ob_start();

        $Sharer = new IFrame(FakeContext::emptyContext());
        $Sharer->display();

        $output = ob_get_clean();

        $this->assertNotEmpty($output, 'Output should not be empty after displaying the IFrame.');
        $this->assertStringContainsString('<iframe', $output, 'Output should contain an iframe tag.');
    }
}
