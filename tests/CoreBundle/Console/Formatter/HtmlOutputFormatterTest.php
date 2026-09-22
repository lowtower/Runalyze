<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Console\Formatter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Console\Formatter\HtmlOutputFormatter;

class HtmlOutputFormatterTest extends TestCase
{
    private function esc(string $codes): string
    {
        // Builds a raw ANSI escape sequence, e.g. esc('31') === "\033[31m".
        return "\033[" . $codes . 'm';
    }

    /**
     * @param string[] $matches
     */
    private function invokeReplaceFormat(HtmlOutputFormatter $formatter, array $matches): string
    {
        $reflection = new \ReflectionObject($formatter);
        $method = $reflection->getMethod('replaceFormat');

        return $method->invoke($formatter, $matches);
    }

    public function testFormatWrapsSingleKnownForegroundColorCodeInSpan(): void
    {
        $formatter = new HtmlOutputFormatter();

        $message = $this->esc('31') . 'Hello' . $this->esc('0');
        $result = $formatter->format($message);

        $this->assertSame('<span style="color:rgba(230,50,50,1)">Hello</span>', $result);
    }

    public function testFormatWrapsKnownBackgroundColorCodeInSpan(): void
    {
        $formatter = new HtmlOutputFormatter();

        $message = $this->esc('41') . 'Warning' . $this->esc('0');
        $result = $formatter->format($message);

        $this->assertSame('<span style="background-color:rgba(230,50,50,1)">Warning</span>', $result);
    }

    public function testFormatReturnsEmptyStyleAttributeForUnrecognizedCode(): void
    {
        $formatter = new HtmlOutputFormatter();

        $message = $this->esc('99') . 'Plain' . $this->esc('0');
        $result = $formatter->format($message);

        $this->assertSame('<span style="">Plain</span>', $result);
    }

    public function testFormatOrdersCssPropertiesByReplaceStyleDeclarationOrder(): void
    {
        $formatter = new HtmlOutputFormatter();

        $message = $this->esc('1;31') . 'Bold Red' . $this->esc('0');
        $result = $formatter->format($message);

        $this->assertSame(
            '<span style="color:rgba(230,50,50,1);font-weight:bold">Bold Red</span>',
            $result
        );
    }

    public function testFormatCombinesForegroundBackgroundAndOption(): void
    {
        $formatter = new HtmlOutputFormatter();

        // Input order deliberately mixed (bg, fg, bold) to further confirm
        // the output order is dictated by ReplaceStyle, not input order.
        $message = $this->esc('41;31;1') . 'Alert' . $this->esc('0');
        $result = $formatter->format($message);

        $this->assertSame(
            '<span style="color:rgba(230,50,50,1);background-color:rgba(230,50,50,1);font-weight:bold">Alert</span>',
            $result
        );
    }

    public function testFormatEscapesPlainTextWithoutAnyAnsiCodes(): void
    {
        $formatter = new HtmlOutputFormatter();

        $result = $formatter->format('Tom & Jerry "Fun"');

        $this->assertSame('Tom &amp; Jerry &quot;Fun&quot;', $result);
    }

    public function testFormatEscapesHtmlSpecialCharactersEmbeddedInColoredText(): void
    {
        $formatter = new HtmlOutputFormatter();

        $message = $this->esc('31') . 'Tom & Jerry "Fun"' . $this->esc('0');
        $result = $formatter->format($message);

        $this->assertSame(
            '<span style="color:rgba(230,50,50,1)">Tom &amp; Jerry &quot;Fun&quot;</span>',
            $result
        );
    }

    public function testFormatHandlesMultipleColoredSegmentsInOneMessage(): void
    {
        $formatter = new HtmlOutputFormatter();

        $message = $this->esc('31') . 'Red' . $this->esc('0')
            . ' and '
            . $this->esc('32') . 'Green' . $this->esc('0');

        $result = $formatter->format($message);

        $this->assertSame(
            '<span style="color:rgba(230,50,50,1)">Red</span> and <span style="color:rgba(50,230,50,1)">Green</span>',
            $result
        );
    }

    public function testFormatReturnsEmptyStringForEmptyInput(): void
    {
        $formatter = new HtmlOutputFormatter();

        $this->assertSame('', $formatter->format(''));
    }

    #[DataProvider('provideSingleCodeMappings')]
    public function testReplaceFormatMapsKnownAnsiCodeToExpectedCssProperty(string $code, string $expectedCss): void
    {
        $formatter = new HtmlOutputFormatter();

        $matches = [
            0 => '',
            1 => $code,
            2 => '',
            3 => 'Text',
            4 => '0',
            5 => '',
        ];
        $result = $this->invokeReplaceFormat($formatter, $matches);

        $this->assertSame('<span style="' . $expectedCss . '">Text</span>', $result);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideSingleCodeMappings(): array
    {
        return [
            'black foreground (30)' => ['30', 'color:rgba(0,0,0,1)'],
            'red foreground (31)' => ['31', 'color:rgba(230,50,50,1)'],
            'green foreground (32)' => ['32', 'color:rgba(50,230,50,1)'],
            'yellow foreground (33)' => ['33', 'color:rgba(230,230,50,1)'],
            'blue foreground (34)' => ['34', 'color:rgba(50,50,230,1)'],
            'magenta foreground (35)' => ['35', 'color:rgba(230,50,150,1)'],
            'cyan foreground (36)' => ['36', 'color:rgba(50,230,230,1)'],
            'white foreground (37)' => ['37', 'color:rgba(250,250,250,1)'],
            'black background (40)' => ['40', 'color:rgba(0,0,0,1)'],
            'red background (41)' => ['41', 'background-color:rgba(230,50,50,1)'],
            'bold (1)' => ['1', 'font-weight:bold'],
            'underline (4)' => ['4', 'text-decoration:underline'],
            'conceal (8)' => ['8', 'visibility:hidden'],
        ];
    }

    public function testReplaceFormatReturnsEmptyStyleAttributeForUnknownCode(): void
    {
        $formatter = new HtmlOutputFormatter();

        $matches = [
            0 => '',
            1 => '99',
            2 => '',
            3 => 'Text',
            4 => '0',
            5 => '',
        ];
        $result = $this->invokeReplaceFormat($formatter, $matches);

        $this->assertSame('<span style="">Text</span>', $result);
    }

    public function testReplaceFormatIgnoresClosingCodesEntirely(): void
    {
        $formatter = new HtmlOutputFormatter();

        $matches = [
            0 => '',
            1 => '32',
            2 => '',
            3 => 'Text',
            4 => '31',
            5 => '',
        ];
        $result = $this->invokeReplaceFormat($formatter, $matches);

        $this->assertSame('<span style="color:rgba(50,230,50,1)">Text</span>', $result);
    }

    public function testReplaceFormatPreservesEmptyText(): void
    {
        $formatter = new HtmlOutputFormatter();

        $matches = [
            0 => '',
            1 => '31',
            2 => '',
            3 => '',
            4 => '0',
            5 => '',
        ];
        $result = $this->invokeReplaceFormat($formatter, $matches);

        $this->assertSame('<span style="color:rgba(230,50,50,1)"></span>', $result);
    }
}
