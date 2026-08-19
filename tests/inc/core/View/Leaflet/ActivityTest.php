<?php

namespace Runalyze\View\Leaflet;

use Runalyze\Model\Trackdata\Entity;
use Runalyze\Model\Trackdata\Pause;
use Runalyze\View\Activity\FakeContext;

class ActivityTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @see https://github.com/Runalyze/Runalyze/issues/1805
     * @group review-later
     * TODO: This test needs a deeper look later. It currently fails because the $activity might be null.
     */
    public function testThatMapWorksWithPausesButWithoutTimeArray()
    {
        $Context = FakeContext::outdoorContext();
        $Context->trackdata()
            ->set(Entity::TIME, []);
        $Context->trackdata()
            ->pauses()
            ->add(new Pause(123, 10));

        $activity = new Activity('test', $Context->route(), $Context->trackdata(), true);

        $this->assertInstanceOf(
            Activity::class,
            $activity,
            'Activity constructor should return an instance of Activity'
        );
    }
}
