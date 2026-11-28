<?php

declare(strict_types=1);

namespace Runalyze\View\Window\Laps;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

final class FormTest extends TestCase
{
    #[DataProvider('submittedOptions')]
    public function testManualSplitOverrideRetainsItsSubmittedState(array $post, bool $checked): void
    {
        $previousPost = $_POST;
        $_POST = $post;
        ob_start();
        try {
            $form = new Form();
            $form->activateHandmadeDifference();
            $form->display();
            $html = (string) ob_get_contents();
        } finally {
            ob_end_clean();
            $_POST = $previousPost;
        }

        $checkbox = new Crawler($html)->filter('input[name="calculate-for-splits"]');
        $this->assertCount(1, $checkbox);
        $this->assertSame('checkbox', $checkbox->attr('type'));
        $this->assertSame($checked, $checkbox->attr('checked') !== null);
    }

    public static function submittedOptions(): iterable
    {
        yield 'unchecked by default' => [[], false];
        yield 'checked on resubmission' => [[
            'calculate-for-splits' => 'on',
        ], true];
    }
}
