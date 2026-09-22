<?php

namespace Runalyze\Export\Share;

use Runalyze\Model\Activity;
use Runalyze\View\Activity\FakeContext;

#[\PHPUnit\Framework\Attributes\Group('dependsOn')]
#[\PHPUnit\Framework\Attributes\Group('dependsOnOldFactory')]
class HtmlTest extends \PHPUnit\Framework\TestCase
{
    public function testThatItsAlwaysPossible(): void
    {
        $this->assertTrue((new Html(FakeContext::onlyWithActivity(
            new Activity\Entity([
                Activity\Entity::IS_PUBLIC => false,
            ])
        )))->isPossible());

        $this->assertTrue((new Html(FakeContext::onlyWithActivity(
            new Activity\Entity([
                Activity\Entity::IS_PUBLIC => true,
            ])
        )))->isPossible());
    }

    public function testThatCodeCanBeCreated(): void
    {
        foreach (FakeContext::examplaryContexts() as $context) {
            $Sharer = new Html($context);

            ob_start();
            $Sharer->display();
            $output = ob_get_clean();

            $this->assertIsString($output);
            $this->assertNotSame('', $output, 'Html::display() should produce non-empty output.');
        }
    }
}
