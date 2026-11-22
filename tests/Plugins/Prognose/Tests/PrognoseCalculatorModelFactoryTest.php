<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Prognose\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Plugins\Prognose\PrognoseCalculatorModelFactory;
use Runalyze\Sports\Running\Prognosis\Bock;
use Runalyze\Sports\Running\Prognosis\Cameron;
use Runalyze\Sports\Running\Prognosis\Steffny;
use Runalyze\Sports\Running\Prognosis\VO2max;

#[Group('plugin')]
final class PrognoseCalculatorModelFactoryTest extends TestCase
{
    #[DataProvider('modelInputs')]
    public function testSelectsExistingPrognosisModel(string $input, string $expectedModel, string $expectedClass): void
    {
        $factory = new PrognoseCalculatorModelFactory();

        $this->assertSame($expectedModel, $factory->normalizeModel($input));
        $this->assertInstanceOf($expectedClass, $factory->create([
            'model' => $input,
            'vo2max' => '50',
            'best-result-km' => '5',
            'best-result-time' => '20:00',
            'second-best-result-km' => '10',
            'second-best-result-time' => '42:30',
        ]));
    }

    /**
     * @return iterable<string, array{string, string, class-string}>
     */
    public static function modelInputs(): iterable
    {
        yield 'effective VO2max' => ['vo2max', 'vo2max', VO2max::class];
        yield 'Robert Bock' => ['robert-bock', 'robert-bock', Bock::class];
        yield 'Herbert Steffny' => ['herbert-steffny', 'herbert-steffny', Steffny::class];
        yield 'David Cameron' => ['david-cameron', 'david-cameron', Cameron::class];
        yield 'unsupported model' => ['not-a-model', 'vo2max', VO2max::class];
        yield 'script injection' => ["vo2max');alert(1);//", 'vo2max', VO2max::class];
    }

    public function testParsesCommaDecimalDistancesForRobertBock(): void
    {
        $model = new PrognoseCalculatorModelFactory()->create([
            'model' => 'robert-bock',
            'best-result-km' => '5,0',
            'best-result-time' => '20:00',
            'second-best-result-km' => '10,0',
            'second-best-result-time' => '42:30',
        ]);

        $this->assertInstanceOf(Bock::class, $model);
        $this->assertTrue($model->areValuesValid());
    }

    public function testUsesMarathonShapeOnlyWhenRequested(): void
    {
        $factory = new PrognoseCalculatorModelFactory();
        $without = $factory->create([
            'model' => 'vo2max',
            'vo2max' => '50',
            'endurance-value' => '50',
        ]);
        $with = $factory->create([
            'model' => 'vo2max',
            'vo2max' => '50',
            'endurance-value' => '50',
            'endurance' => true,
        ]);

        $this->assertInstanceOf(VO2max::class, $without);
        $this->assertInstanceOf(VO2max::class, $with);
        $this->assertNotNull($without->getSeconds(42.195));
        $this->assertNotNull($with->getSeconds(42.195));
        $this->assertGreaterThan($without->getSeconds(42.195), $with->getSeconds(42.195));
    }
}
