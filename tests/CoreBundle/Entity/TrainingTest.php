<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use Runalyze\Bundle\CoreBundle\Entity\Training;

class TrainingTest extends \PHPUnit\Framework\TestCase
{
    protected Training $Activity;

    protected function setUp(): void
    {
        $this->Activity = new Training();
    }

    public function testCloningSplits()
    {
        $oldSplits = $this->Activity->getSplits();
        $this->Activity->setSplitsToClone();

        $this->assertNotSame($this->Activity->getSplits(), $oldSplits);
    }
}
