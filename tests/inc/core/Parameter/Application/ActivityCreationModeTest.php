<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\Group('default')]
class ActivityCreationModeTest extends TestCase
{
    protected ActivityCreationMode $object;

    protected function setUp(): void
    {
        $this->object = new ActivityCreationMode();
    }

    public function testModes(): void
    {
        $this->object->set(ActivityCreationMode::UPLOAD);
        $this->assertEquals(true, $this->object->usesUpload());
        $this->assertEquals(false, $this->object->usesGarminCommunicator());
        $this->assertEquals(false, $this->object->usesForm());

        $this->object->set(ActivityCreationMode::GARMIN);
        $this->assertEquals(false, $this->object->usesUpload());
        $this->assertEquals(true, $this->object->usesGarminCommunicator());
        $this->assertEquals(false, $this->object->usesForm());

        $this->object->set(ActivityCreationMode::FORM);
        $this->assertEquals(false, $this->object->usesUpload());
        $this->assertEquals(false, $this->object->usesGarminCommunicator());
        $this->assertEquals(true, $this->object->usesForm());
    }
}
