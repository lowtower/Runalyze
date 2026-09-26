<?php

namespace Runalyze\View\Activity;

use Runalyze\Model\Activity;

#[\PHPUnit\Framework\Attributes\Group('dependsOn')]
#[\PHPUnit\Framework\Attributes\Group('dependsOnOldDatabase')]
class LinkerTest extends \PHPUnit\Framework\TestCase
{
    protected \PDOforRunalyze $PDO;

    protected int $ThisAccountID;

    protected int $OtherAccountID;

    /**
     * @var string[]|bool[]
     */
    protected array $ThisIDs = [];

    protected function setUp(): void
    {
        $this->PDO = \DB::getInstance();
        $this->PDO->exec('DELETE FROM `runalyze_training`');
        $this->PDO->exec('DELETE FROM `runalyze_account` WHERE `username` = "LinkerOther"');

        $this->ThisAccountID = \SessionAccountHandler::getId();
        $this->PDO->exec('INSERT INTO `runalyze_account` (`username`, `name`, `mail`) VALUES ("LinkerOther", "LinkerOther", "linker@other.com")');
        $this->OtherAccountID = (int) $this->PDO->lastInsertId();

        $activities = [
            [0, $this->OtherAccountID],
            [1, $this->ThisAccountID],
            [2, $this->OtherAccountID],
            [3, $this->ThisAccountID],
            [3, $this->ThisAccountID],
            [4, $this->OtherAccountID],
            [5, $this->ThisAccountID],
            [6, $this->OtherAccountID],
        ];
        foreach ($activities as $data) {
            $this->PDO->exec('INSERT INTO `runalyze_training` (`time`, `accountid`, `sportid`, `s`) VALUES (' . $data[0] . ', ' . $data[1] . ', 0, 2)');

            if ($data[1] == $this->ThisAccountID) {
                $this->ThisIDs[] = $this->PDO->lastInsertId();
            }
        }
    }

    protected function tearDown(): void
    {
        $this->PDO->exec('DELETE FROM `runalyze_training`');
    }

    public function testPrevAndNext(): void
    {
        $this->assertEquals('', Linker::prevId($this->ThisIDs[0], 1));
        $this->assertEquals($this->ThisIDs[0], Linker::prevId($this->ThisIDs[1], 3));
        $this->assertEquals($this->ThisIDs[1], Linker::prevId($this->ThisIDs[2], 3));
        $this->assertEquals($this->ThisIDs[2], Linker::prevId($this->ThisIDs[3], 3));

        $this->assertEquals($this->ThisIDs[1], Linker::nextId($this->ThisIDs[0], 3));
        $this->assertEquals($this->ThisIDs[2], Linker::nextId($this->ThisIDs[1], 3));
        $this->assertEquals($this->ThisIDs[3], Linker::nextId($this->ThisIDs[2], 3));
        $this->assertEquals('', Linker::nextId($this->ThisIDs[3], 5));
    }

    public function testPrevAndNextAtEqualTimestamp(): void
    {
        $this->assertEquals($this->ThisIDs[1], Linker::prevId($this->ThisIDs[2], 3));
        $this->assertEquals($this->ThisIDs[2], Linker::nextId($this->ThisIDs[1], 3));
    }

    public function testEditUrlBuildsPathFromActivityId(): void
    {
        $Linker = new Linker(new Activity\Entity([
            'id' => 42,
        ]));

        $this->assertSame('activity/42/edit', $Linker->editUrl());
    }

    public function testUrlToElevationCorrectionBuildsPathFromActivityId(): void
    {
        $Linker = new Linker(new Activity\Entity([
            'id' => 42,
        ]));

        $this->assertSame('activity/42/elevation-correction', $Linker->urlToElevationCorrection());
    }

    public function testUrlToClimbScoreBuildsPathFromActivityId(): void
    {
        $Linker = new Linker(new Activity\Entity([
            'id' => 42,
        ]));

        $this->assertSame('activity/42/climb-score', $Linker->urlToClimbScore());
    }

    public function testUrlToElevationInfoBuildsPathWithoutQueryStringByDefault(): void
    {
        $Linker = new Linker(new Activity\Entity([
            'id' => 42,
        ]));

        $this->assertSame('activity/42/elevation-info', $Linker->urlToElevationInfo());
    }

    public function testUrlToElevationInfoAppendsQueryStringWhenDataIsGiven(): void
    {
        $Linker = new Linker(new Activity\Entity([
            'id' => 42,
        ]));

        $this->assertSame('activity/42/elevation-info?foo=bar', $Linker->urlToElevationInfo('foo=bar'));
    }

    public function testUrlToVO2maxinfoBuildsPathWithoutQueryStringByDefault(): void
    {
        $Linker = new Linker(new Activity\Entity([
            'id' => 42,
        ]));

        $this->assertSame('activity/42/vo2max-info', $Linker->urlToVO2maxinfo());
    }

    public function testUrlToVO2maxinfoAppendsQueryStringWhenDataIsGiven(): void
    {
        $Linker = new Linker(new Activity\Entity([
            'id' => 42,
        ]));

        $this->assertSame('activity/42/vo2max-info?foo=bar', $Linker->urlToVO2maxinfo('foo=bar'));
    }

    public function testUrlToRoundsInfoBuildsPathWithoutQueryStringByDefault(): void
    {
        $Linker = new Linker(new Activity\Entity([
            'id' => 42,
        ]));

        $this->assertSame('activity/42/splits-info', $Linker->urlToRoundsInfo());
    }

    public function testUrlToRoundsInfoAppendsQueryStringWhenDataIsGiven(): void
    {
        $Linker = new Linker(new Activity\Entity([
            'id' => 42,
        ]));

        $this->assertSame('activity/42/splits-info?foo=bar', $Linker->urlToRoundsInfo('foo=bar'));
    }
}
