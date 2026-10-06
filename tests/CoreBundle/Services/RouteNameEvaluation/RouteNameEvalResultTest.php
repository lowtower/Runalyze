<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use Runalyze\Service\RouteNameEvaluation\RouteNameEvalResult;

/**
 * Testcase for RouteNameEvalResult.
 */
class RouteNameEvalResultTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
    }

    public function testAppendNotesExistingNull(): void
    {
        $r = new RouteNameEvalResult('thename', 'thenote');

        $note = $r->appendNotes(null);
        $this->assertSame(RouteNameEvalResult::NOTES_START . "\nthenote\n" . RouteNameEvalResult::NOTES_END, $note);
    }

    // existing notes but eval-note is empty -> use only existing one
    public function testAppendNotesExistNewNoteEmpty(): void
    {
        $r = new RouteNameEvalResult('thename', '');

        $note = $r->appendNotes('x');
        $this->assertSame('x', $note);
    }

    // no existing notes exists -> set evaluation note as new
    public function testAppendNotesExistIsEmpty(): void
    {
        $r = new RouteNameEvalResult('thename', "multi\nline\nnotes\n");

        $note = $r->appendNotes('');
        $this->assertSame(RouteNameEvalResult::NOTES_START . "\nmulti\nline\nnotes\n" . RouteNameEvalResult::NOTES_END, $note);
    }

    // existing notes exists, but no evaluation note -> append evaluation note to the end
    public function testAppendNotesExistNotEmpty01(): void
    {
        $r = new RouteNameEvalResult('thename', "multi\nline\nnotes");

        $note = $r->appendNotes("Alread\nhas some\n");

        $this->assertSame("Alread\nhas some\n\n" .
            RouteNameEvalResult::NOTES_START . "\nmulti\nline\nnotes\n" . RouteNameEvalResult::NOTES_END, $note);
    }

    // existing notes exists, but no evaluation note -> append evaluation note to the end
    public function testAppendNotesExistNotEmpty02(): void
    {
        $r = new RouteNameEvalResult('thename', "multi\nline\nnotes");

        $note = $r->appendNotes("Alread\nhas some");

        $this->assertSame("Alread\nhas some\n\n" .
            RouteNameEvalResult::NOTES_START . "\nmulti\nline\nnotes\n" . RouteNameEvalResult::NOTES_END, $note);
    }

    // existing notes with existing evaluation not exists -> replace eval note
    public function testAppendNotesExist01(): void
    {
        $r = new RouteNameEvalResult('thename', "multi\nline\nnotes\n");

        $note = $r->appendNotes("Alread\nhas some\n" .
            RouteNameEvalResult::NOTES_START . "old-existing-info\n" . RouteNameEvalResult::NOTES_END);

        $this->assertSame("Alread\nhas some\n" .
            RouteNameEvalResult::NOTES_START . "\nmulti\nline\nnotes\n" . RouteNameEvalResult::NOTES_END, $note);
    }

    // existing notes with existing evaluation not exists; with further text after the eval note -> replace eval note
    public function testAppendNotesExist02(): void
    {
        $r = new RouteNameEvalResult('thename', 'new');

        $note = $r->appendNotes("Alread\n" .
            RouteNameEvalResult::NOTES_START . "old\n" . RouteNameEvalResult::NOTES_END .
            'end text');

        // check also a PHP_EOL is added to the eval-note!
        $this->assertSame("Alread\n" .
            RouteNameEvalResult::NOTES_START . "\nnew\n" . RouteNameEvalResult::NOTES_END .
            'end text', $note);
    }

    // existing notes with existing evaluation exists -> replace eval note
    public function testAppendNotesExist03(): void
    {
        $r = new RouteNameEvalResult('thename', "multi\nline\nnotes\n");

        $note = $r->appendNotes(RouteNameEvalResult::NOTES_START . "old-existing-info\n" . RouteNameEvalResult::NOTES_END);

        $this->assertSame(RouteNameEvalResult::NOTES_START . "\nmulti\nline\nnotes\n" . RouteNameEvalResult::NOTES_END, $note);
    }
}
