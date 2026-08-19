<?php

namespace Runalyze\Dataset;

class KeysTest extends \PHPUnit\Framework\TestCase
{
    public function testAllIDs()
    {
        foreach (Keys::getEnum() as $id) {
            $this->assertEquals($id, Keys::get($id)->id());
        }
    }

    public function testThatIDsAreUnique()
    {
        $this->assertEquals(
            Keys::getEnum(),
            array_unique(Keys::getEnum())
        );
    }

    public function testThatSummaryWorksForAllKeys()
    {
        foreach (Keys::getEnum() as $key) {
            $KeyObject = Keys::get($key);

            if ($KeyObject->isInDatabase() && $KeyObject->isShownInSummary()) {
                $result = SummaryMode::query(
                    $KeyObject->summaryMode(),
                    $KeyObject->column()
                );
                $this->assertNotSame('', $result, sprintf('SummaryMode::query() returned an empty string for key "%s" (mode "%s") — the summary mode is likely not handled in SummaryMode::query().', $key, $KeyObject->summaryMode()));
            }
        }
    }

    public function testThatLabelIsNeverEmpty()
    {
        foreach (Keys::getEnum() as $id) {
            $this->assertNotEmpty(Keys::get($id)->label());
        }
    }

    public function testEmptyKeysForValuesNotInDatabase()
    {
        foreach (Keys::getEnum() as $id) {
            $Key = Keys::get($id);

            if (! $Key->isInDatabase()) {
                $this->assertEquals('', $Key->column());
            }
        }
    }

    public function testInvalidKeyForGet()
    {
        $this->expectException(\InvalidArgumentException::class);
        Keys::get('key-ids-are-integers');
    }
}
