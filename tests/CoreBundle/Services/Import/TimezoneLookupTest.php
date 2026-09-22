<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Import;

use Runalyze\Bundle\CoreBundle\Services\Import\TimezoneLookup;
use Runalyze\Bundle\CoreBundle\Services\Import\TimezoneLookupException;

#[\PHPUnit\Framework\Attributes\Group('dependsOn')]
#[\PHPUnit\Framework\Attributes\Group('dependsOnTimezoneDatabase')]
#[\PHPUnit\Framework\Attributes\Group('requiresSqlite')]
class TimezoneLookupTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TimezoneLookup
     */
    protected $Lookup;

    protected function setUp(): void
    {
        $this->Lookup = new TimezoneLookup(TESTS_ROOT . '/../data/timezone.sqlite', SQLITE_MOD_SPATIALITE);
    }

    public function testSilenceConstructor(): void
    {
        $lookup = new TimezoneLookup('here/is/no/timezone/database.sqlite', SQLITE_MOD_SPATIALITE);
        $lookup->silentExceptions();

        $this->assertFalse($lookup->isPossible());
        $this->assertNull($lookup->getTimezoneForCoordinate(13.41, 52.52));
    }

    public function testConstructorWithException(): void
    {
        $lookup = new TimezoneLookup('here/is/no/timezone/database.sqlite', SQLITE_MOD_SPATIALITE);

        $this->expectException(TimezoneLookupException::class);

        $lookup->isPossible();
    }

    public function testConstructorWithWrongExtensionName(): void
    {
        $lookup = new TimezoneLookup(TESTS_ROOT . '/../data/timezone.sqlite', 'non-existant-extension.so');

        $this->expectException(TimezoneLookupException::class);

        $lookup->isPossible();
    }

    public function testSimpleLocations(): void
    {
        try {
            $this->assertEquals('Europe/Berlin', $this->Lookup->getTimezoneForCoordinate(13.41, 52.52));
            $this->assertEquals('America/Los_Angeles', $this->Lookup->getTimezoneForCoordinate(-122.420706, 37.776685));
        } catch (TimezoneLookupException $timezoneLookupException) {
            $this->markTestSkipped('Timezone lookup is not possible: ' . $timezoneLookupException->getMessage());
        }
    }

    public function testInvalidLocations(): void
    {
        try {
            $this->assertNull($this->Lookup->getTimezoneForCoordinate('foo', 'bar'));
        } catch (TimezoneLookupException $timezoneLookupException) {
            $this->markTestSkipped('Timezone lookup is not possible: ' . $timezoneLookupException->getMessage());
        }
    }
}
