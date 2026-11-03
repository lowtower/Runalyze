<?php

declare(strict_types=1);

namespace Runalyze\Tests\Parser\Activity\Converter;

use PHPUnit\Framework\TestCase;
use Runalyze\Parser\Activity\Converter\FitConverter;
use Runalyze\Parser\Activity\Converter\TtbinConverter;

final class ShellCommandEscapingTest extends TestCase
{
    public function testFitConverterEscapesFileNameArguments(): void
    {
        $converter = new class('/usr/bin/perl', '/tmp/fit script.pl') extends FitConverter {
            public function buildCommandForTest(string $inputFile, string $outputFile): string
            {
                return $this->buildCommand($inputFile, $outputFile);
            }
        };

        $inputFile = '/tmp/input"$(touch /tmp/runalyze-fit-pwned)".fit';
        $outputFile = '/tmp/output"$(touch /tmp/runalyze-fit-output-pwned)"';

        $command = $converter->buildCommandForTest($inputFile, $outputFile);

        $this->assertStringContainsString(escapeshellarg('/tmp/fit script.pl'), $command);
        $this->assertStringContainsString(escapeshellarg($inputFile), $command);
        $this->assertStringContainsString(escapeshellarg($outputFile), $command);
        $this->assertStringNotContainsString('"' . $inputFile . '"', $command);
        $this->assertStringNotContainsString('"' . $outputFile . '"', $command);
    }

    public function testTtbinConverterEscapesFileNameArguments(): void
    {
        $converter = new class('/tmp/ttbin converter') extends TtbinConverter {
            public function buildCommandForTest(string $inputFile, string $outputFile): string
            {
                return $this->buildCommand($inputFile, $outputFile);
            }
        };

        $inputFile = '/tmp/input"$(touch /tmp/runalyze-ttbin-pwned)".ttbin';
        $outputFile = '/tmp/output"$(touch /tmp/runalyze-ttbin-output-pwned)".tcx';

        $command = $converter->buildCommandForTest($inputFile, $outputFile);

        $this->assertStringContainsString(escapeshellarg('/tmp/ttbin converter'), $command);
        $this->assertStringContainsString(escapeshellarg($inputFile), $command);
        $this->assertStringContainsString(escapeshellarg($outputFile), $command);
        $this->assertStringNotContainsString('"' . $inputFile . '"', $command);
        $this->assertStringNotContainsString('"' . $outputFile . '"', $command);
    }
}
