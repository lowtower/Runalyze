<?php

declare(strict_types=1);

namespace Runalyze\Data\Weather;

final class SourcesTest extends \PHPUnit\Framework\TestCase
{
    public function testThatStringsAreDefined(): void
    {
        foreach (Sources::getEnum() as $id) {
            $stringValue = Sources::stringFor($id);
            $this->assertIsString($stringValue, "stringFor() should return a string for ID {$id}");
        }
    }
}
