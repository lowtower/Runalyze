<?php

namespace Runalyze\Dataset;

class KeysTest extends \PHPUnit\Framework\TestCase
{
    public function testAllIDs(): void
    {
        foreach (Keys::getEnum() as $id) {
            $this->assertEquals($id, Keys::get($id)->id());
        }
    }

    public function testThatIDsAreUnique(): void
    {
        $this->assertEquals(
            Keys::getEnum(),
            array_unique(Keys::getEnum())
        );
    }

    public function testThatSummaryWorksForAllKeys(): void
    {
        foreach (Keys::getEnum() as $key) {
            $KeyObject = Keys::get($key);
            $column = is_array($KeyObject->column()) ? $KeyObject->column()[0] : $KeyObject->column();

            if ($KeyObject->isInDatabase() && $KeyObject->isShownInSummary()) {
                $result = SummaryMode::query(
                    $KeyObject->summaryMode(),
                    $column
                );
                $this->assertNotSame('', $result, sprintf('SummaryMode::query() returned an empty string for key "%s" (mode "%s") — the summary mode is likely not handled in SummaryMode::query().', $key, $KeyObject->summaryMode()));
            }
        }
    }

    public function testThatLabelIsNeverEmpty(): void
    {
        foreach (Keys::getEnum() as $id) {
            $this->assertNotEmpty(Keys::get($id)->label());
        }
    }

    public function testEmptyKeysForValuesNotInDatabase(): void
    {
        foreach (Keys::getEnum() as $id) {
            $Key = Keys::get($id);

            if (! $Key->isInDatabase()) {
                $this->assertEquals('', $Key->column());
            }
        }
    }

    public function testInvalidKeyForGet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Keys::get('key-ids-are-integers');
    }
}
