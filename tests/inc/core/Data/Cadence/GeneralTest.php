<?php

declare(strict_types=1);

namespace Runalyze\Data\Cadence;

class GeneralTest extends \PHPUnit\Framework\TestCase {
	public function testValue() {
		$Cadence = new General(90);

		$this->assertEquals(90, $Cadence->value());
	}

}
