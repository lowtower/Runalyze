<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Poster;

use Doctrine\ORM\Query;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\GeneratePoster;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;

class GeneratePosterTest extends TestCase
{
    private TrainingRepository&MockObject $trainingRepositoryMock;

    protected function setUp(): void
    {
        $this->trainingRepositoryMock = $this->createMock(TrainingRepository::class);
    }

    private function createGenerator(string $kernelRootDir = '/app', string $python3Path = '/usr/bin/python3'): GeneratePoster
    {
        return new GeneratePoster($kernelRootDir, $python3Path, $this->trainingRepositoryMock);
    }

    /**
     * @param string[] $args
     */
    private function invokePrivate(object $object, string $method, array $args = []): mixed
    {
        $reflection = new \ReflectionObject($object);
        $refMethod = $reflection->getMethod($method);

        return $refMethod->invokeArgs($object, $args);
    }

    private function getPrivateProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionObject($object);
        $prop = $reflection->getProperty($property);

        return $prop->getValue($object);
    }

    public function testPathToRepositoryAppendsExpectedRelativePath(): void
    {
        $generator = $this->createGenerator('/app');

        $result = $this->invokePrivate($generator, 'pathToRepository');

        $this->assertSame('/app/../vendor/runalyze/gpxtrackposter/', $result);
    }

    public function testPathToSvgDirectoryAppendsExpectedRelativePath(): void
    {
        $generator = $this->createGenerator('/app');

        $result = $this->invokePrivate($generator, 'pathToSvgDirectory');

        $this->assertSame('/app/../var/poster/', $result);
    }

    public function testAvailablePosterTypesReturnsExpectedFixedList(): void
    {
        $generator = $this->createGenerator();

        $this->assertSame(
            ['grid', 'calendar', 'circular', 'heatmap'],
            $generator->availablePosterTypes()
        );
    }

    public function testGenerateRandomFileNameProducesAnMd5HashWithSvgExtension(): void
    {
        $generator = $this->createGenerator();

        $this->invokePrivate($generator, 'generateRandomFileName', ['john', '2024']);
        $filename = $this->getPrivateProperty($generator, 'Filename');

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}\.svg$/', $filename);
    }

    public function testGenerateRandomFileNameDiffersForDifferentAthletes(): void
    {
        $generator = $this->createGenerator();

        $this->invokePrivate($generator, 'generateRandomFileName', ['john', '2024']);
        $first = $this->getPrivateProperty($generator, 'Filename');

        $this->invokePrivate($generator, 'generateRandomFileName', ['jane', '2024']);
        $second = $this->getPrivateProperty($generator, 'Filename');

        $this->assertNotSame($first, $second);
    }

    public function testGenerateRandomFileNameCollidesForSameAthleteAndYearWithinSameSecond(): void
    {
        $generator = $this->createGenerator();

        $this->invokePrivate($generator, 'generateRandomFileName', ['john', '2024']);
        $first = $this->getPrivateProperty($generator, 'Filename');

        $this->invokePrivate($generator, 'generateRandomFileName', ['john', '2024']);
        $second = $this->getPrivateProperty($generator, 'Filename');

        $this->assertSame($first, $second);
    }

    private function mockStatsResult(): void
    {
        $query = $this->createMock(Query::class);
        $query->method('getArrayResult')
            ->willReturn([
                [
                    'num' => 12,
                    'total_distance' => 150.5,
                    'min_distance' => 3.2,
                    'max_distance' => 21.1,
                ],
            ]);

        $this->trainingRepositoryMock
            ->method('getStatsForPoster')
            ->willReturn($query);
    }

    public function testBuildCommandAssemblesExpectedParameters(): void
    {
        $this->mockStatsResult();

        $account = $this->createMock(Account::class);
        $account->method('getUsername')
            ->willReturn('johndoe');
        $sport = $this->createMock(Sport::class);

        $generator = $this->createGenerator('/app');
        $generator->buildCommand(
            'grid',
            '/tmp/json',
            2024,
            $account,
            $sport,
            'My Title',
            '#000000',
            '#FF0000',
            '#FFFFFF',
            '#00FF00'
        );

        $parameters = $this->getPrivateProperty($generator, 'Parameter');

        $this->assertContains('--json-dir /tmp/json', $parameters);
        $this->assertContains('--athlete johndoe', $parameters);
        $this->assertContains('--year 2024', $parameters);
        $this->assertContains('--type grid', $parameters);
        $this->assertContains("--title 'My Title'", $parameters);
        $this->assertContains("--background-color  '#000000'", $parameters);
        $this->assertContains("--track-color '#FF0000'", $parameters);
        $this->assertContains("--text-color '#FFFFFF'", $parameters);
        $this->assertContains("--special-color '#00FF00'", $parameters);
        $this->assertContains('--stat-num 12', $parameters);
        $this->assertContains('--stat-total 150.5', $parameters);
        $this->assertContains('--stat-min 3.2', $parameters);
        $this->assertContains('--stat-max 21.1', $parameters);
    }

    public function testBuildCommandTreatsNullTitleAsEmptyString(): void
    {
        $this->mockStatsResult();

        $account = $this->createMock(Account::class);
        $account->method('getUsername')
            ->willReturn('johndoe');
        $sport = $this->createMock(Sport::class);

        $generator = $this->createGenerator('/app');
        $generator->buildCommand(
            'grid',
            '/tmp/json',
            2024,
            $account,
            $sport,
            null,
            '#000000',
            '#FF0000',
            '#FFFFFF',
            '#00FF00'
        );

        $parameters = $this->getPrivateProperty($generator, 'Parameter');

        $this->assertContains("--title ''", $parameters);
    }

    public function testBuildCommandEscapesShellMetacharactersInTitle(): void
    {
        $this->mockStatsResult();

        $account = $this->createMock(Account::class);
        $account->method('getUsername')
            ->willReturn('johndoe');
        $sport = $this->createMock(Sport::class);

        $generator = $this->createGenerator('/app');
        $generator->buildCommand(
            'grid',
            '/tmp/json',
            2024,
            $account,
            $sport,
            "'; rm -rf /; echo '",
            '#000000',
            '#FF0000',
            '#FFFFFF',
            '#00FF00'
        );

        $parameters = $this->getPrivateProperty($generator, 'Parameter');
        $titleParameter = current(array_filter($parameters, static fn ($p): bool => str_starts_with((string) $p, '--title ')));

        $this->assertSame('--title ' . escapeshellarg("'; rm -rf /; echo '"), $titleParameter);
    }

    #[DataProvider('providePosterTypes')]
    public function testBuildCommandIncludesEachRequestedType(string $type): void
    {
        $this->mockStatsResult();

        $account = $this->createMock(Account::class);
        $account->method('getUsername')
            ->willReturn('johndoe');
        $sport = $this->createMock(Sport::class);

        $generator = $this->createGenerator('/app');
        $generator->buildCommand($type, '/tmp/json', 2024, $account, $sport, 'Title', '#000', '#111', '#222', '#333');

        $parameters = $this->getPrivateProperty($generator, 'Parameter');

        $this->assertContains('--type ' . $type, $parameters);
    }

    /**
     * @return array<string, string[]>
     */
    public static function providePosterTypes(): array
    {
        return [
            'grid' => ['grid'],
            'calendar' => ['calendar'],
            'circular' => ['circular'],
            'heatmap' => ['heatmap'],
        ];
    }
}
