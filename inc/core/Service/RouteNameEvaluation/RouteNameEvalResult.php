<?php

namespace Runalyze\Service\RouteNameEvaluation;

/**
 * Result of the route-name evaluation to be used/stored on the training/activity.
 * #TSC
 */
class RouteNameEvalResult
{
    public const string NOTES_START = 'Strecken-Info>>';

    public const string NOTES_END = '<<Strecken-Info';

    public const string NOTES_PATTERN = '/(.*)(' . self::NOTES_START . '.*' . self::NOTES_END . ')(.*)/is';

    protected string $names;

    public function __construct(
        string $names,
        protected string $notes
    ) {
        // it should never occur, but limit it to 255 (same as the database attribute)
        $this->names = mb_strimwidth($names, 0, 255, '...');
    }

    public function getNames(): string
    {
        return $this->names;
    }

    /**
     * Gets the plain notes text without prefix and suffix.
     */
    public function getNotes(): string
    {
        return $this->notes;
    }

    /**
     * Appends or replace the existing note with the new notes text based on pre-/suffix.
     */
    public function appendNotes(?string $existingNote): string
    {
        if (empty($this->notes)) {
            return $existingNote ?? '';
        }

        $taggedNote = self::NOTES_START . PHP_EOL . $this->notes . ($this->endsWith($this->notes, PHP_EOL) ? '' : PHP_EOL) . self::NOTES_END;

        if (empty($existingNote)) {
            return $taggedNote;
        }

        if (! str_contains($existingNote, self::NOTES_START)) {
            // when append the eval-note the first time, make a newline between for better usability
            return $existingNote . ($this->endsWith($existingNote, PHP_EOL) ? '' : PHP_EOL) . PHP_EOL . $taggedNote;
        }

        // replace between the tags the existing text
        $result = preg_replace(self::NOTES_PATTERN, '$1' . $taggedNote . '$3', $existingNote);

        // preg_replace returns null on error or no match - fallback to original
        return $result ?? $existingNote;
    }

    protected function endsWith(string $haystack, string $needle): bool
    {
        return $needle === '' || (str_ends_with($haystack, $needle));
    }
}
