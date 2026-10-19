<?php

declare(strict_types=1);

namespace Runalyze\Data\Cadence;

final class GeneralTest extends \PHPUnit\Framework\TestCase
{
    public function testValue(): void
    {
        $Cadence = new General(90);

        $this->assertSame(90, $Cadence->value());
    }
}
