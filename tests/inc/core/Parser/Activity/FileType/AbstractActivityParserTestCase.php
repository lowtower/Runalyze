<?php

namespace Runalyze\Tests\Parser\Activity\FileType;

use Runalyze\Bundle\CoreBundle\Services\Import\TimezoneLookup;
use Runalyze\Parser\Activity\Common\Data\ActivityDataContainer;
use Runalyze\Parser\Activity\Common\Filter\DefaultFilterCollection;
use Runalyze\Parser\Activity\Common\ParserInterface;
use Runalyze\Parser\Common\FileContentAwareParserInterface;
use Runalyze\Parser\Common\FileTypeConverterInterface;

abstract class AbstractActivityParserTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var null|ActivityDataContainer|ActivityDataContainer[]
     */
    protected $Container;

    protected int $NumberOfActivities = 0;

    /**
     * @var string[]
     */
    protected array $FilesToClear = [];

    protected function tearDown(): void
    {
        foreach ($this->FilesToClear as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    protected function pathToTestFiles(): string
    {
        return TESTS_ROOT . '/testfiles/';
    }

    /**
     * @param string $file file path relative to 'testfiles/'
     * @param string[] $expectedOutputFiles
     */
    protected function convertAndParseFile(FileTypeConverterInterface $converter, FileContentAwareParserInterface $parser, string $file, array $expectedOutputFiles, bool $completeAfterwards = true)
    {
        $outputFile = $converter->convertFile($this->pathToTestFiles() . $file);

        if (! is_array($outputFile)) {
            $outputFile = [$outputFile];
        }

        $this->FilesToClear = $outputFile;
        $path = $this->pathToTestFiles();
        $outputFile = array_map(fn (string $file): string => str_replace($path, '', $file), $outputFile);

        $this->assertEquals($expectedOutputFiles, $outputFile);

        $this->parseFiles($parser, $outputFile, $completeAfterwards);
    }

    /**
     * @param string[] $files
     */
    protected function parseFiles(FileContentAwareParserInterface $parser, array $files, bool $completeAfterwards = true)
    {
        $tmpContainer = [];

        foreach ($files as $currentFile) {
            $this->parseFile($parser, $currentFile, $completeAfterwards);

            if (! is_array($this->Container)) {
                $tmpContainer[] = $this->Container;
            } else {
                $tmpContainer += $this->Container;
            }
        }

        $this->NumberOfActivities = count($tmpContainer);
        $this->Container = $this->NumberOfActivities === 1 ? $tmpContainer[0] : $tmpContainer;
    }

    /**
     * @param string $file file path relative to 'testfiles/'
     */
    protected function parseFile(FileContentAwareParserInterface $parser, string $file, bool $completeAfterwards = true)
    {
        $this->parseFileContent($parser, file_get_contents($this->pathToTestFiles() . $file), $completeAfterwards);
    }

    protected function parseFileContent(FileContentAwareParserInterface $parser, string $fileContent, bool $completeAfterwards = true)
    {
        $parser->setFileContent($fileContent);
        $parser->parse();

        $this->setContainerFrom($parser, $completeAfterwards);
    }

    protected function setContainerFrom(ParserInterface $parser, bool $completeAfterwards = true, bool $runFilter = true)
    {
        $filterCollection = new DefaultFilterCollection();
        $this->NumberOfActivities = $parser->getNumberOfActivities();
        $this->Container = [];

        for ($i = 0; $i < $this->NumberOfActivities; ++$i) {
            $this->Container[] = $parser->getActivityDataContainer($i);

            if ($completeAfterwards) {
                $this->Container[$i]->completeContinuousData();
            }

            if ($runFilter) {
                $this->Container[$i]->filterActivityData($filterCollection);
            }

            if ($completeAfterwards) {
                $this->Container[$i]->completeActivityData();
            }
        }

        if ($this->NumberOfActivities === 1) {
            $this->Container = $this->Container[0];
        }
    }

    /**
     * @param array $expectedRounds [[duration [s], distance [km]], ...]
     * @param float $deltaDuration [s]
     * @param float $deltaDistance [km]
     */
    protected function checkExpectedRoundData(array $expectedRounds, float $deltaDuration = 0.0, float $deltaDistance = 0.0)
    {
        $this->checkExpectedRoundDataFor($this->Container, $expectedRounds, $deltaDuration, $deltaDistance);
    }

    /**
     * @param array $expectedRounds [[duration [s], distance [km]], ...]
     * @param float $deltaDuration [s]
     * @param float $deltaDistance [km]
     */
    protected function checkExpectedRoundDataFor(ActivityDataContainer $container, array $expectedRounds, float $deltaDuration = 0.0, float $deltaDistance = 0.0)
    {
        $this->assertEquals(count($expectedRounds), $container->Rounds->count(), 'The number of rounds does not match.');

        foreach ($expectedRounds as $i => $expectedRoundData) {
            $this->assertEqualsWithDelta($expectedRoundData[0], $container->Rounds[$i]->getDuration(), $deltaDuration, 'Round #' . $i . ' has wrong duration.');
            $this->assertEqualsWithDelta($expectedRoundData[1], $container->Rounds[$i]->getDistance(), $deltaDistance, 'Round #' . $i . ' has wrong distance.');
        }
    }

    /**
     * @param array $expectedPauses [[time index [s], duration [km](, hr start [bpm], hr end [bpm])], ...]
     * @param float $deltaDuration [s]
     */
    protected function checkExpectedPauseData(array $expectedPauses, float $deltaDuration = 0.0)
    {
        $this->assertEquals(count($expectedPauses), $this->Container->Pauses->count(), 'The number of pauses does not match.');

        foreach ($expectedPauses as $i => $expectedPauseData) {
            $this->assertEqualsWithDelta($expectedPauseData[0], $this->Container->Pauses[$i]->getTimeIndex(), $deltaDuration, 'Pause #' . $i . ' has wrong time index.');
            $this->assertEqualsWithDelta($expectedPauseData[1], $this->Container->Pauses[$i]->getDuration(), $deltaDuration, 'Pause #' . $i . ' has wrong duration.');

            if (isset($expectedPauseData[2]) && isset($expectedPauseData[3])) {
                $this->assertEquals($expectedPauseData[2], $this->Container->Pauses[$i]->getHeartRateAtStart(), 'Pause #' . $i . ' has wrong heart rate at start.');
                $this->assertEquals($expectedPauseData[3], $this->Container->Pauses[$i]->getHeartRateAtEnd(), 'Pause #' . $i . ' has wrong heart rate at end.');
            }
        }
    }

    /**
     * Correct the activity timezone based on the first valid GPS coordinate.
     *
     * This mirrors the relevant behavior of
     * ActivityAdapter::guessTimezoneBasedOnCoordinates(), because parser unit
     * tests do not create and persist a Training entity and therefore do not
     * invoke ActivityListener::prePersist().
     *
     * @return bool true if a timezone was found and applied
     */
    protected function applyTimezoneLookup(ActivityDataContainer $container): bool
    {
        if (! RUNALYZE_TEST_TZ_LOOKUP) {
            return false;
        }

        $latitude = null;
        $longitude = null;
        foreach ($container->ContinuousData->Latitude as $index => $value) {
            if (
                is_numeric($value) &&
                isset($container->ContinuousData->Longitude[$index]) &&
                is_numeric($container->ContinuousData->Longitude[$index])
            ) {
                $latitude = (float) $value;
                $longitude = (float) $container->ContinuousData->Longitude[$index];

                break;
            }
        }

        if ($latitude === null || $longitude === null) {
            return false;
        }

        $lookup = new TimezoneLookup(TESTS_ROOT . '/../data/timezone.sqlite', SQLITE_MOD_SPATIALITE);
        $timezoneName = $lookup->getTimezoneForCoordinate($longitude, $latitude);
        if ($timezoneName === null) {
            return false;
        }

        $timestamp = $container->Metadata->getTimestamp();
        $oldOffset = $container->Metadata->getTimezoneOffset();
        $newOffset = (new \DateTime('', new \DateTimeZone($timezoneName)))->setTimestamp($timestamp)
            ->getOffset() / 60;
        if ($oldOffset !== null) {
            $timestamp += 60 * ($newOffset - $oldOffset);
        }

        $container->Metadata->setTimestamp($timestamp, (int) $newOffset);

        return true;
    }
}
