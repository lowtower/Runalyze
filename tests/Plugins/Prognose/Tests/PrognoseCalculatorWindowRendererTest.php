<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Prognose\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Parameter\Application\DistanceUnitSystem;
use Runalyze\Parameter\Application\PaceUnit;
use Runalyze\Plugins\Prognose\PrognoseCalculatorFormInput;
use Runalyze\Plugins\Prognose\PrognoseCalculatorModelFactory;
use Runalyze\Plugins\Prognose\PrognoseCalculatorModelHintRenderer;
use Runalyze\Plugins\Prognose\PrognoseCalculatorPersonalBestProvider;
use Runalyze\Plugins\Prognose\PrognoseCalculatorPredictionRows;
use Runalyze\Plugins\Prognose\PrognoseCalculatorResultTableRenderer;
use Runalyze\Plugins\Prognose\PrognoseCalculatorWindowRenderer;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

#[Group('plugin')]
final class PrognoseCalculatorWindowRendererTest extends TestCase
{
    public function testGetRendersDefaultValuesAndInitialVisibilityScript(): void
    {
        $html = $this->renderer()->render(
            $this->account(),
            3,
            [],
            [5.0, 10.0],
            [],
            50.0,
            80,
            new DistanceUnitSystem(DistanceUnitSystem::METRIC),
            PaceUnit::MIN_PER_KM
        );

        $this->assertStringContainsString('id="prognosis-calculator"', $html);
        $this->assertStringContainsString('action="/plugin/RunalyzePluginPanel_Prognose/window.php"', $html);
        $this->assertStringContainsString('value="5, 10"', $html);
        $this->assertStringContainsString('value="50"', $html);
        $this->assertStringContainsString('name="endurance" value="1" checked', $html);
        $this->assertStringContainsString('Your two best results:', $html);
        $this->assertStringContainsString('none', $html);
        $this->assertStringContainsString('only-vo2max', $html);
        $this->assertStringContainsString("$('#prognosis-model').on('change'", $html);
    }

    public function testSubmittedBockModelPreservesFieldsAndDisplaysReferenceResults(): void
    {
        $html = $this->renderer()->render(
            $this->account(),
            3,
            [
                ['distance' => 5.0, 's' => 1200.0, 'time' => 1700000000],
                ['distance' => 10.0, 's' => 2600.0, 'time' => 1700001000],
            ],
            [5.0],
            [
                'model' => 'robert-bock',
                'distances' => '5',
                'best-result-km' => '5.0',
                'best-result-time' => '20:00',
                'second-best-result-km' => '10.0',
                'second-best-result-time' => '43:20',
            ],
            50.0,
            80,
            new DistanceUnitSystem(DistanceUnitSystem::METRIC),
            PaceUnit::MIN_PER_KM
        );

        $this->assertStringContainsString('value="robert-bock" selected', $html);
        $this->assertStringContainsString('value="5,00"', $html);
        $this->assertStringContainsString('value="10,00"', $html);
        $this->assertStringContainsString('Your two best results:', $html);
        $this->assertStringContainsString(' and ', $html);
        $this->assertStringContainsString('K =', $html);
        $this->assertStringContainsString('only-robert-bock', $html);
    }

    public function testSingleTopResultDoesNotRenderSecondResultInfo(): void
    {
        $html = $this->renderer()->render(
            $this->account(),
            3,
            [['distance' => 5.0, 's' => 1200.0, 'time' => 1700000000]],
            [5.0],
            [],
            50.0,
            80,
            new DistanceUnitSystem(DistanceUnitSystem::METRIC),
            PaceUnit::MIN_PER_KM
        );

        $this->assertStringContainsString('Your two best results:', $html);
        $this->assertStringContainsString('20:00', $html);
        $this->assertStringNotContainsString(' and ', $html);
    }

    public function testImperialDefaultsConvertConfiguredDistancesToMiles(): void
    {
        $html = $this->renderer()->render(
            $this->account(),
            3,
            [],
            [5.0],
            [],
            50.0,
            80,
            new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL),
            PaceUnit::MIN_PER_MILE
        );

        $this->assertStringContainsString('name="distances" value="3.11"', $html);
        $this->assertStringContainsString('class="input-unit">mi</label>', $html);
    }

    private function renderer(): PrognoseCalculatorWindowRenderer
    {
        $loader = new FilesystemLoader();
        $loader->addPath(dirname(__DIR__, 4) . '/plugins/Prognose/templates', 'Prognose');

        $twig = new Environment($loader);
        $twig->addFilter(new TwigFilter('trans', static fn (string $value): string => $value));
        $twig->addFunction(new TwigFunction(
            'path',
            static fn (string $name): string => '/plugin/RunalyzePluginPanel_Prognose/window.php'
        ));

        $raceResults = $this->createStub(RaceresultRepository::class);
        $raceResults->method('findPersonalBestDetailsForDistances')->willReturn([]);
        $trainings = $this->createStub(TrainingRepository::class);

        return new PrognoseCalculatorWindowRenderer(
            new PrognoseCalculatorFormInput(),
            new PrognoseCalculatorModelFactory(),
            new PrognoseCalculatorPredictionRows(
                new PrognoseCalculatorPersonalBestProvider($raceResults, $trainings)
            ),
            new PrognoseCalculatorResultTableRenderer($twig),
            new PrognoseCalculatorModelHintRenderer(),
            $twig
        );
    }

    private function account(): Account
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')->willReturn(12);

        return $account;
    }
}
