<?php

declare(strict_types=1);

namespace Runalyze\Export\Share;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Velocity\Unit\PaceEnum;
use Runalyze\Model\Activity\Entity as Activity;
use Runalyze\Model\Sport\Entity as Sport;
use Runalyze\View\Activity\Context;
use Runalyze\View\Activity\Dataview;

final class AbstractSharerTestFixture extends AbstractSharer
{
    public function isExternalLink(): bool
    {
        return true;
    }

    public function url(): string
    {
        return 'https://example.com/share';
    }

    public function name(): string
    {
        return 'ExampleService';
    }

    public function iconClass(): string
    {
        return 'fa-example';
    }

    public function isPossible(): bool
    {
        return true;
    }

    public function exposeExternalLink(string $text = ''): string
    {
        return $this->externalLink($text);
    }

    public function exposeText(): string
    {
        return $this->text();
    }
}

#[Group('default')]
final class AbstractSharerTest extends TestCase
{
    private AbstractSharerTestFixture $sharer;

    protected function setUp(): void
    {
        $this->sharer = new AbstractSharerTestFixture(null);
    }

    public function testExternalLinkUsesNameWhenNoTextIsGiven(): void
    {
        $html = $this->sharer->exposeExternalLink();

        $this->assertSame(
            '<a href="https://example.com/share" target="_blank" style="display:block!important"><i class="fa fa-example"></i> <strong>ExampleService</strong></a>',
            $html
        );
    }

    public function testExternalLinkUsesGivenTextInsteadOfName(): void
    {
        $html = $this->sharer->exposeExternalLink('Custom Share Text');

        $this->assertStringContainsString('<strong>Custom Share Text</strong>', $html);
        $this->assertStringNotContainsString('ExampleService', $html);
    }

    public function testExternalLinkAlwaysUsesUrlAndIconClassRegardlessOfText(): void
    {
        $html = $this->sharer->exposeExternalLink('Custom Share Text');

        $this->assertStringContainsString('href="https://example.com/share"', $html);
        $this->assertStringContainsString('fa fa-example', $html);
    }

    public function testExternalLinkTreatsStringZeroAsEmptyAndFallsBackToName(): void
    {
        $html = $this->sharer->exposeExternalLink('0');

        $this->assertStringContainsString('<strong>ExampleService</strong>', $html);
        $this->assertStringNotContainsString('<strong>0</strong>', $html);
    }

    public function testShareTextUsesTheSportPaceUnit(): void
    {
        $activity = new Activity([
            Activity::DISTANCE => 5.0,
            Activity::TIME_IN_SECONDS => 1500,
        ]);
        $dataview = new class($activity) extends Dataview {
            #[\Override]
            public function titleByTypeOrSport(): string
            {
                return 'Running';
            }
        };

        $context = $this->createStub(Context::class);
        $context->method('activity')->willReturn($activity);
        $context->method('dataview')->willReturn($dataview);
        $context->method('sport')->willReturn(new Sport([
            Sport::PACE_UNIT => PaceEnum::SECONDS_PER_MILE,
        ]));

        $this->assertStringContainsString('(8:03/mi)', new AbstractSharerTestFixture($context)->exposeText());
    }
}
