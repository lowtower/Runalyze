<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Prognose\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Plugins\Prognose\PrognoseCalculatorModelHintRenderer;
use Runalyze\Sports\Running\Prognosis\Bock;
use Runalyze\Sports\Running\Prognosis\VO2max;

#[Group('plugin')]
final class PrognoseCalculatorModelHintRendererTest extends TestCase
{
    private PrognoseCalculatorModelHintRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new PrognoseCalculatorModelHintRenderer();
    }

    public function testRobertBockHintShowsFittedConstantsForValidReferenceResults(): void
    {
        $prognosis = new Bock(5.0, 1200.0, 10.0, 2600.0);
        $this->assertTrue($prognosis->areValuesValid());

        $html = $this->renderer->render('robert-bock', $prognosis);

        $this->assertStringContainsString('K =', $html);
        $this->assertStringContainsString('e =', $html);
        $this->assertStringNotContainsString('K must be between', $html);
    }

    public function testRobertBockHintExplainsInvalidReferenceResults(): void
    {
        $prognosis = new Bock();
        $this->assertFalse($prognosis->areValuesValid());

        $html = $this->renderer->render('robert-bock', $prognosis);

        $this->assertStringContainsString('K =', $html);
        $this->assertStringContainsString('K must be between', $html);
    }

    public function testInvalidVO2maxShowsAllowedRange(): void
    {
        $prognosis = new VO2max(0.0);
        $this->assertFalse($prognosis->areValuesValid());

        $html = $this->renderer->render('vo2max', $prognosis);

        $this->assertStringContainsString('Effective VO2max must be between', $html);
    }

    public function testValidVO2maxDoesNotShowWarning(): void
    {
        $prognosis = new VO2max(50.0);
        $this->assertTrue($prognosis->areValuesValid());

        $this->assertSame('', $this->renderer->render('vo2max', $prognosis));
    }

    public function testHintsAreNotRenderedForAnUnrelatedModel(): void
    {
        $this->assertSame('', $this->renderer->render('david-cameron', new Bock()));
    }
}
