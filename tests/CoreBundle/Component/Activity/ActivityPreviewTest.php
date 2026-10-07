<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Activity;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityDecorator;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityPreview;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Parser\Activity\Common\Data\Round\RoundCollection;
use Runalyze\Util\LocalTime;

class ActivityPreviewTest extends TestCase
{
    private function createRoundCollection(bool $isEmpty): RoundCollection
    {
        $collection = $this->createStub(RoundCollection::class);
        $collection->method('isEmpty')
            ->willReturn($isEmpty);

        return $collection;
    }

    private function createSport(string $name = 'Running', string $img = 'running.png'): Sport
    {
        $sport = $this->createStub(Sport::class);
        $sport->method('getName')
            ->willReturn($name);
        $sport->method('getImg')
            ->willReturn($img);

        return $sport;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createTraining(array $overrides = []): Training
    {
        $defaults = [
            'getId' => 42,
            'getS' => 3600.0,
            'getDistance' => 10.5,
            'getPulseAvg' => 150,
            'hasRoute' => true,
        ];
        $config = array_merge($defaults, $overrides);

        unset($config['getSplits'], $config['getSport']);

        $activity = $this->createStub(Training::class);
        foreach ($config as $method => $value) {
            $activity->method($method)
                ->willReturn($value);
        }

        $splits = array_key_exists('getSplits', $overrides)
            ? $overrides['getSplits']
            : $this->createRoundCollection(true);
        $activity->method('getSplits')
            ->willReturn($splits);

        $sport = array_key_exists('getSport', $overrides) ? $overrides['getSport'] : $this->createSport();
        $activity->method('getSport')
            ->willReturn($sport);

        return $activity;
    }

    private function createDecoratorMock(string $title, int $timestamp): ActivityDecorator
    {
        $decorator = $this->createStub(ActivityDecorator::class);
        $decorator->method('getTitle')
            ->willReturnCallback(function (bool $typeWithSport = false) use ($title): string {
                // Replaces ->with(true): a stub has no with()
                $this->assertTrue($typeWithSport);

                return $title;
            });
        $decorator->method('getDateTime')
            ->willReturn(new LocalTime($timestamp));

        return $decorator;
    }

    public function testSimplePropertiesAreCopiedFromActivity(): void
    {
        $activity = $this->createTraining([
            'getId' => 7,
            'getS' => 1800.0,
            'getDistance' => 5.0,
        ]);

        $preview = new ActivityPreview($activity, false, $this->createDecoratorMock('x', 0));

        $this->assertSame(7, $preview->getActivityId());
        $this->assertSame(1800.0, $preview->getDuration());
        $this->assertSame(5.0, $preview->getDistance());
        $this->assertTrue($preview->hasDistance());
    }

    public function testActivityIdIsNullWhenActivityHasNotBeenPersistedYet(): void
    {
        $activity = $this->createTraining([
            'getId' => null,
        ]);

        $preview = new ActivityPreview($activity, false, $this->createDecoratorMock('x', 0));

        $this->assertNull($preview->getActivityId());
    }

    public function testHasDistanceIsFalseWhenDistanceIsNull(): void
    {
        $activity = $this->createTraining([
            'getDistance' => null,
        ]);

        $preview = new ActivityPreview($activity, false, $this->createDecoratorMock('x', 0));

        $this->assertNull($preview->getDistance());
        $this->assertFalse($preview->hasDistance());
    }

    public function testHasHeartRateIsTrueWhenPulseAvgIsPresent(): void
    {
        $activity = $this->createTraining([
            'getPulseAvg' => 145,
        ]);

        $preview = new ActivityPreview($activity, false, $this->createDecoratorMock('x', 0));

        $this->assertTrue($preview->hasHeartRate());
    }

    public function testHasHeartRateIsFalseWhenPulseAvgIsNull(): void
    {
        $activity = $this->createTraining([
            'getPulseAvg' => null,
        ]);

        $preview = new ActivityPreview($activity, false, $this->createDecoratorMock('x', 0));

        $this->assertFalse($preview->hasHeartRate());
    }

    public function testHasRouteReflectsActivityFlag(): void
    {
        $decorator = $this->createDecoratorMock('x', 0);

        $withRoute = new ActivityPreview($this->createTraining([
            'hasRoute' => true,
        ]), false, $decorator);
        $withoutRoute = new ActivityPreview($this->createTraining([
            'hasRoute' => false,
        ]), false, $decorator);

        $this->assertTrue($withRoute->hasRoute());
        $this->assertFalse($withoutRoute->hasRoute());
    }

    public function testHasRoundsIsFalseWhenSplitsAreEmpty(): void
    {
        $activity = $this->createTraining([
            'getSplits' => $this->createRoundCollection(true),
        ]);

        $preview = new ActivityPreview($activity, false, $this->createDecoratorMock('x', 0));

        $this->assertFalse($preview->hasRounds());
    }

    public function testHasRoundsIsTrueWhenSplitsArePresent(): void
    {
        $activity = $this->createTraining([
            'getSplits' => $this->createRoundCollection(false),
        ]);

        $preview = new ActivityPreview($activity, false, $this->createDecoratorMock('x', 0));

        $this->assertTrue($preview->hasRounds());
    }

    public function testSportPropertiesAreCopiedWhenSportIsPresent(): void
    {
        $sport = $this->createSport('Cycling', 'cycling.png');
        $activity = $this->createTraining([
            'getSport' => $sport,
        ]);

        $preview = new ActivityPreview($activity, false, $this->createDecoratorMock('x', 0));

        $this->assertSame('Cycling', $preview->getSportName());
        $this->assertSame('cycling.png', $preview->getSportIcon());
    }

    public function testSetPropertiesFromSportLeavesDefaultsWhenSportIsNull(): void
    {
        $preview = (new \ReflectionClass(ActivityPreview::class))->newInstanceWithoutConstructor();

        $method = new \ReflectionMethod(ActivityPreview::class, 'setPropertiesFromSport');
        $method->invoke($preview, null);

        $this->assertSame('', $preview->getSportName());
        $this->assertSame('', $preview->getSportIcon());
    }

    public function testTitleAndDateTimeComeFromInjectedDecorator(): void
    {
        $timestamp = (new \DateTime('2026-03-10 06:15:00'))->getTimestamp();
        $decorator = $this->createDecoratorMock('Running: Long Run', $timestamp);

        $preview = new ActivityPreview($this->createTraining(), false, $decorator);

        $this->assertSame('Running: Long Run', $preview->getTitle());
        $this->assertSame($timestamp, $preview->getDateTime()->getTimestamp());
    }

    public function testDefaultDecoratorIsUsedWhenNoneIsInjected(): void
    {
        $timestamp = (new \DateTime('2026-01-15 08:00:00'))->getTimestamp();
        $activity = $this->createTraining([
            'getTitle' => 'Long Run',
            'getTime' => $timestamp,
            'getType' => null,
            'getSport' => $this->createSport('Running'),
        ]);

        $preview = new ActivityPreview($activity);

        $this->assertSame('Running: Long Run', $preview->getTitle());
        $this->assertSame($timestamp, $preview->getDateTime()->getTimestamp());
    }

    public function testIsPossibleDuplicateDefaultsToFalseAndCanBeChanged(): void
    {
        $activity = $this->createTraining();
        $decorator = $this->createDecoratorMock('x', 0);

        $preview = new ActivityPreview($activity, false, $decorator);
        $this->assertFalse($preview->isPossibleDuplicate());

        $preview = new ActivityPreview($activity, true, $decorator);
        $this->assertTrue($preview->isPossibleDuplicate());
    }

    public function testSetPossibleDuplicateOverridesTheFlag(): void
    {
        $preview = new ActivityPreview($this->createTraining(), false, $this->createDecoratorMock('x', 0));

        $preview->setPossibleDuplicate(true);
        $this->assertTrue($preview->isPossibleDuplicate());

        $preview->setPossibleDuplicate(false);
        $this->assertFalse($preview->isPossibleDuplicate());
    }
}
