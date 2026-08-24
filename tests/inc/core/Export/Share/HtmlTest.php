<?php

namespace Runalyze\Export\Share;

use Runalyze\Model\Activity;
use Runalyze\View\Activity\FakeContext;

/**
 * @group dependsOn
 * @group dependsOnOldFactory
 */
class HtmlTest extends \PHPUnit\Framework\TestCase
{
    public function checkThatItsAlwaysPossible()
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

    public function testThatCodeCanBeCreated()
    {
        ob_start();

        foreach (FakeContext::examplaryContexts() as $context) {
            $Sharer = new Html($context);
            $Sharer->display();
        }

        ob_end_clean();
    }
}
