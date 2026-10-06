<?php

declare(strict_types=1);

namespace Runalyze\Calculation\Activity;

class HrZonesCalculatorTest extends \PHPUnit\Framework\TestCase
{
    public function testValidateInput(): void
    {
        // ok
        $this->assertFalse(HrZonesCalculator::validateInputZone('t1:130:#12345f'));
        $this->assertFalse(HrZonesCalculator::validateInputZone('t1:130:#1A345c'));
        $this->assertFalse(HrZonesCalculator::validateInputZone('t1:130:#123456'));
        $this->assertFalse(HrZonesCalculator::validateInputZone('t2:13'));
        $this->assertFalse(HrZonesCalculator::validateInputZone('130'));

        // invalid - general format
        $this->assertTrue(HrZonesCalculator::validateInputZone('t1::#000000'));
        $this->assertTrue(HrZonesCalculator::validateInputZone(''));
        $this->assertTrue(HrZonesCalculator::validateInputZone('#000000'));
        $this->assertTrue(HrZonesCalculator::validateInputZone('t1'));
        $this->assertTrue(HrZonesCalculator::validateInputZone('t1:130|#000000'));
        $this->assertTrue(HrZonesCalculator::validateInputZone('t1:130#000000'));

        // invalid - not enough bpm / to much bpm
        $this->assertTrue(HrZonesCalculator::validateInputZone('t1:1:#000000'));
        $this->assertTrue(HrZonesCalculator::validateInputZone('1'));
        $this->assertTrue(HrZonesCalculator::validateInputZone('t1:1234:#000000'));
        $this->assertTrue(HrZonesCalculator::validateInputZone(''));
        $this->assertTrue(HrZonesCalculator::validateInputZone('t1::#000000'));

        // invalid - wrong color
        $this->assertTrue(HrZonesCalculator::validateInputZone('t1:80:#00000')); // less
        $this->assertTrue(HrZonesCalculator::validateInputZone('123:#G00000')); // no hex
        $this->assertTrue(HrZonesCalculator::validateInputZone('123:#000:00')); // no hex

        // invalid text
        $this->assertTrue(HrZonesCalculator::validateInputZone('12345678901234567890123456:80:#00000')); // to long
        $this->assertTrue(HrZonesCalculator::validateInputZone('12345678901234567890123456:80')); // to long
    }

    public function testSorting(): void
    {
        $expected = [
            '1',
            't2:13',
            't1:130:#000000',
        ];
        $this->assertEquals($expected, HrZonesCalculator::sortZones([
            't1:130:#000000',
            't2:13',
            '1',
        ]));
    }

    /**
     * Zones with an identical BPM value (e.g. two sources deliver the same
     * boundary) must not be shuffled.
     */
    public function testSortingWithEqualBpm(): void
    {
        $input = [
            't1:100:#000000',
            't2:100',
            '100:#111111',
            't3:100',
        ];

        // equal BPM values => order stays as in the input (stable sort)
        $this->assertEquals($input, HrZonesCalculator::sortZones($input));

        // mixed with one actually smaller value: only that one moves to the front,
        // the mutually equal elements keep their relative order
        $input2 = [
            't1:100:#000000',
            't2:50',
            't3:100',
        ];
        $this->assertEquals([
            't2:50',
            't1:100:#000000',
            't3:100',
        ], HrZonesCalculator::sortZones($input2));
    }

    /**
     * Values that contain no extractable BPM number (e.g. an empty string or
     * plain text without digits) must not cause sortZones() to abort with an
     * exception/TypeError, nor distort the order of the remaining valid
     * values. They are deterministically treated as BPM=0 and therefore end
     * up at the beginning of the ascending sorted list.
     */
    public function testSortingWithInvalidValues(): void
    {
        $input = [
            't1:130:#000000',
            'invalid',
            't2:13',
            '',
            '1',
        ];

        $this->assertEquals([
            'invalid',
            '',
            '1',
            't2:13',
            't1:130:#000000',
        ], HrZonesCalculator::sortZones($input));
    }

    public function testFitZones(): void
    {
        // we need 6 zones without text&color
        $underTest = new HrZonesCalculator(['90', '100', '120', '130', '135', '180'], null);
        $result = $underTest->getZones(25, 130, 180);
        $this->assertEquals([
            [
                'hr' => 25,
            ],
            [
                't' => 'B1 Warm up',
                'hr' => 90,
                'c' => '#a6a6a6',
            ],
            [
                't' => 'B2 Easy',
                'hr' => 100,
                'c' => '#3b97f3',
            ],
            [
                't' => 'B3 Aerob',
                'hr' => 120,
                'c' => '#82c91e',
            ],
            [
                't' => 'B4 Intensiv',
                'hr' => 130,
                'c' => '#f98925',
            ],
            [
                't' => 'B5 Maximal',
                'hr' => 135,
                'c' => '#d32020',
            ],
            [
                't' => 'B6 Override',
                'hr' => 180,
                'c' => '#b80000',
            ],
        ], $result);
        $this->assertSame('activity stored zones', $underTest->getSourceHint());
        // sort of array
        $this->assertEquals(6, array_key_first($result));
        $this->assertEquals(0, array_key_last($result));
        $this->assertEquals([
            'hr' => 25,
        ], end($result));

        // with min/max
        $result = $underTest->getZones(95, 190, 180);
        $this->assertEquals([
            [
                'hr' => 0,
            ],
            [
                't' => 'B1 Warm up',
                'hr' => 90,
                'c' => '#a6a6a6',
            ],
            [
                't' => 'B2 Easy',
                'hr' => 100,
                'c' => '#3b97f3',
            ],
            [
                't' => 'B3 Aerob',
                'hr' => 120,
                'c' => '#82c91e',
            ],
            [
                't' => 'B4 Intensiv',
                'hr' => 130,
                'c' => '#f98925',
            ],
            [
                't' => 'B5 Maximal',
                'hr' => 135,
                'c' => '#d32020',
            ],
            [
                't' => 'B6 Override',
                'hr' => 180,
                'c' => '#b80000',
            ],
            [
                'hr' => 191,
            ],
        ], $result);
        $this->assertSame('activity stored zones', $underTest->getSourceHint());
    }

    public function testDefaultZones(): void
    {
        $underTest = new HrZonesCalculator(null, null);
        $result = $underTest->getZones(5, 80, 100);
        $this->assertEquals([
            [
                'hr' => 5,
            ],
            [
                'hr' => 31,
            ],
            [
                'hr' => 41,
            ],
            [
                'hr' => 51,
                'c' => '#a6a6a6',
            ],
            [
                'hr' => 61,
                'c' => '#3b97f3',
            ],
            [
                'hr' => 71,
                'c' => '#82c91e',
            ],
            [
                'hr' => 81,
                'c' => '#f98925',
            ],
            [
                'hr' => 91,
                'c' => '#d32020',
            ],
            [
                'hr' => 101,
                'c' => '#b80000',
            ],
        ], $result);
        $this->assertSame('default zones', $underTest->getSourceHint());
        // sort of array
        $this->assertEquals(8, array_key_first($result));
        $this->assertEquals(0, array_key_last($result));
        $this->assertEquals([
            'hr' => 5,
        ], end($result));

        // with min/max
        $result = $underTest->getZones(55, 110, 100);
        $this->assertEquals([
            [
                'hr' => 0,
            ],
            [
                'hr' => 31,
            ],
            [
                'hr' => 41,
            ],
            [
                'hr' => 51,
                'c' => '#a6a6a6',
            ],
            [
                'hr' => 61,
                'c' => '#3b97f3',
            ],
            [
                'hr' => 71,
                'c' => '#82c91e',
            ],
            [
                'hr' => 81,
                'c' => '#f98925',
            ],
            [
                'hr' => 91,
                'c' => '#d32020',
            ],
            [
                'hr' => 101,
                'c' => '#b80000',
            ],
            [
                'hr' => 111,
            ],
        ], $result);
        $this->assertSame('default zones', $underTest->getSourceHint());
    }

    public function testSportZones(): void
    {
        $underTest = new HrZonesCalculator(null, ['t1:70:#000011', '100:#002200', 't3:120']);
        $result = $underTest->getZones(71, 110, 150);
        $this->assertEquals([
            [
                'hr' => 0,
            ],
            [
                't' => 't1',
                'hr' => 70,
                'c' => '#000011',
            ],
            [
                'hr' => 100,
                'c' => '#002200',
            ],
            [
                't' => 't3',
                'hr' => 120,
            ],
        ], $result);
        $this->assertSame('sport stored zones', $underTest->getSourceHint());
        // sort of array
        $this->assertEquals(3, array_key_first($result));
        $this->assertEquals(0, array_key_last($result));
        $this->assertEquals([
            'hr' => 0,
        ], end($result));

        // with min/max
        $result = $underTest->getZones(52, 160, 150);
        $this->assertEquals([
            [
                'hr' => 52,
            ],
            [
                't' => 't1',
                'hr' => 70,
                'c' => '#000011',
            ],
            [
                'hr' => 100,
                'c' => '#002200',
            ],
            [
                't' => 't3',
                'hr' => 120,
            ],
            [
                'hr' => 161,
            ],
        ], $result);
        $this->assertSame('sport stored zones', $underTest->getSourceHint());
        $this->assertEquals([
            'hr' => 52,
        ], end($result));
    }
}
