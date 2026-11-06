<?php

declare(strict_types=1);

namespace Runalyze\Tests\View\Splits;

use Runalyze\Model\Activity\Splits\Entity;
use Runalyze\Model\Activity\Splits\Split;
use Runalyze\View\Splits\Table;

final class TableTest extends \PHPUnit\Framework\TestCase
{
    public function testThatEmptySplitsResultInAnEmptyTable(): void
    {
        $data = (new Table(new Entity()))->templateData();

        $this->assertSame('fullwidth zebra-style', $data['class']);
        $this->assertSame('', $data['wrapper_style']);
        $this->assertSame('', $data['warning']);

        $this->assertCount(5, $data['header']);
        $this->assertSame('', $data['header'][0]['content']);
        $this->assertSame('', $data['header'][0]['style']);

        $this->assertSame([], $data['bodies'][0]['rows']);
        $this->assertSame('top-spacer', $data['bodies'][1]['class']);
        $this->assertSame([], $data['bodies'][1]['rows']);
    }

    public function testThatRestingSplitsAreSeparatedFromActiveSplits(): void
    {
        $splits = new Entity([
            new Split(1.0, 300, true),
            new Split(0.5, 60, false),
            new Split(1.0, 330, true),
        ]);

        $data = (new Table($splits))->templateData();

        $rows = $data['bodies'][0]['rows'];
        $this->assertCount(4, $rows);

        $this->assertSame('r', $rows[0]['class']);
        $this->assertSame('c', $rows[0]['cells'][0]['class']);
        $this->assertSame('1.', $rows[0]['cells'][0]['content']);
        $this->assertCount(5, $rows[0]['cells']);

        $this->assertSame('space-line', $rows[1]['class']);
        $this->assertSame(5, $rows[1]['cells'][0]['colspan']);
        $this->assertSame('space-line zebra-corrector', $rows[2]['class']);

        $this->assertSame('2.', $rows[3]['cells'][0]['content']);
    }

    public function testThatDemandedPaceAndAverageAreAppendedAsSummaryRows(): void
    {
        $table = new Table(new Entity([
            new Split(1.0, 300, true),
        ]));
        $table->setDemandedPace(300.0);

        $data = $table->templateData();

        $rows = $data['bodies'][1]['rows'];
        $this->assertCount(2, $rows);

        $this->assertSame('small r no-zebra', $rows[0]['class']);
        $this->assertSame(3, $rows[0]['cells'][0]['colspan']);
        $this->assertStringEndsWith(': ', (string) $rows[0]['cells'][0]['content']);
        $this->assertNotSame('', $rows[0]['cells'][1]['content']);
        $this->assertSame('', $rows[0]['cells'][2]['content']);

        $this->assertSame('small r no-zebra', $rows[1]['class']);
        $this->assertSame(3, $rows[1]['cells'][0]['colspan']);
        $this->assertNotSame('', $rows[1]['cells'][1]['content']);
        $this->assertSame('', $rows[1]['cells'][2]['content']);
    }

    public function testThatHalfsOfCompetitionAreAppendedAsSummaryRows(): void
    {
        $table = new Table(new Entity([
            new Split(2.0, 630, true),
        ]));
        $table->setHalfsOfCompetition([
            ['s' => 300.0, 'km' => 1.0],
            ['s' => 330.0, 'km' => 1.0],
        ]);

        $data = $table->templateData();

        $rows = $data['bodies'][1]['rows'];
        $this->assertCount(5, $rows);

        $this->assertSame('small r no-zebra', $rows[0]['class']);

        $this->assertSame('no-zebra', $rows[1]['class']);
        $this->assertSame('&nbsp;', $rows[1]['cells'][0]['content']);

        $this->assertSame('b bottom-spacer no-zebra', $rows[2]['class']);
        $this->assertSame(5, $rows[2]['cells'][0]['colspan']);

        $this->assertSame('r', $rows[3]['class']);
        $this->assertCount(5, $rows[3]['cells']);
        $this->assertNotSame('', $rows[3]['cells'][3]['content']);

        $this->assertSame('r', $rows[4]['class']);
    }
}
