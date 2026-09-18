<?php

declare(strict_types=1);

/**
 * This file contains class::HTML
 * @package Runalyze\HTML
 */

use Runalyze\Util\Time;

/**
 * Class: HTML
 *
 * @author Hannes Christiansen
 * @package Runalyze\HTML
 */
class HTML
{
    /**
     * Encode opening and ending tag-character
     */
    public static function encodeTags(string $string): string
    {
        return str_replace(['<', '>'], ['<', '>'], $string);
    }

    /**
     * Return an empty td-Tag
     */
    public static function emptyTD(int $colspan = 0, string $string = '&nbsp;', string $class = ''): string
    {
        $colspan = ($colspan > 0) ? ' colspan="' . $colspan . '"' : '';
        $class = ($class !== '') ? ' class="' . $class . '"' : '';

        return '<td' . $colspan . $class . '>' . $string . '</td>';
    }

    /**
     * Return an empty tr-Tag
     * @param int $colspan colspan for empty TDs
     */
    public static function emptyTR(int $colspan = 0): string
    {
        return '<tr>' . self::emptyTD($colspan) . '</tr>';
    }

    /**
     * Wrap string in td-tag
     * @param string $string string for td-tag
     * @param string $class optional css class
     * @param string $style optional css inline style
     */
    public static function td(string $string, string $class = '', string $style = ''): string
    {
        if ($class !== '') {
            $class = ' class="' . $class . '"';
        }

        if ($style !== '') {
            $style = ' style="' . $style . '"';
        }

        return '<td' . $class . $style . '>' . $string . '</td>';
    }

    /**
     * Get a tr-tag for a bold header-line containing all month-names
     * @param int $fixedWidth Fixed width for every month-td in percent [set '0' for no fixed width]
     * @param int $emptyTDs Number of empty td before the month-td
     */
    public static function monthTR(int $fixedWidth = 0, int $emptyTDs = 1, string $tag = 'td', bool $withTotal = false): string
    {
        $width = ($fixedWidth > 0) ? ' width="' . $fixedWidth . '%"' : '';
        $html = '<tr class="b">';

        for ($i = 1; $i <= $emptyTDs; $i++) {
            $html .= '<' . $tag . '></' . $tag . '>';
        }

        for ($m = 1; $m <= 12; $m++) {
            $html .= '<' . $tag . $width . '>' . Time::month("{$m}", true) . '</' . $tag . '>';
        }

        if ($withTotal) {
            $html .= '<' . $tag . $width . '>' . __('Total') . '</' . $tag . '>';
        }

        return $html . '</tr>';
    }

    /**
     * Get a tr-tag for a bold header-line containing all years
     * @param int $fixedWidth Fixed width for every year-td in percent [set '0' for no fixed width]
     * @param int $emptyTDs Number of empty td before the year-td
     * @param bool $withTotal add last column for 'total'
     */
    public static function yearTR(int $fixedWidth = 0, int $emptyTDs = 1, string $tag = 'td', bool $withTotal = false): string
    {
        $year = date('Y');
        $width = ($fixedWidth > 0) ? ' width="' . $fixedWidth . '%"' : '';
        $html = '<tr class="b">';

        for ($i = 1; $i <= $emptyTDs; $i++) {
            $html .= '<' . $tag . ' />';
        }

        for ($y = START_YEAR; $y <= $year; $y++) {
            $html .= '<' . $tag . $width . '>' . $y . '</' . $tag . '>';
        }

        if ($withTotal) {
            $html .= '<' . $tag . $width . '>' . __('Total') . '</' . $tag . '>';
        }

        return $html . '</tr>';
    }

    /**
     * Get a tr-tag for a space-line
     */
    public static function spaceTR(int $colspan): string
    {
        return '<tr class="space"><td colspan="' . $colspan . '"></td></tr>';
    }

    /**
     * Return a header
     */
    public static function h1(string $text): string
    {
        return '<h1>' . $text . '</h1>';
    }

    /**
     * Return a break with class="clear"
     */
    public static function clearBreak(): string
    {
        return '<br class="clear">';
    }

    /**
     * Wrap value in span for plus/minus
     */
    public static function plusMinus(float $value, float $ignorance = 0.0): string
    {
        if ($value > +$ignorance) {
            return '<span class="plus">' . $value . '</span>';
        }

        if ($value < -$ignorance) {
            return '<span class="minus">' . $value . '</span>';
        }

        return (string) $value;
    }

    /**
     * Wrap a string into emphasize-tag
     */
    public static function em(string $string): string
    {
        return '<em>' . $string . '</em>';
    }

    /**
     * Wrap a string into paragraph-tag
     */
    public static function p(string $string): string
    {
        return '<p class="text">' . $string . '</p>';
    }

    /**
     * Wrap a string into p-tag with class="info"
     */
    public static function info(string $string): string
    {
        return '<p class="info">' . $string . '</p>';
    }

    /**
     * Wrap a string into p-tag with class="error"
     */
    public static function error(string $string): string
    {
        return '<p class="error">' . $string . '</p>';
    }

    /**
     * Wrap a string into p-tag with class="warning"
     */
    public static function warning(string $string): string
    {
        return '<p class="warning">' . $string . '</p>';
    }

    /**
     * Wrap a string into p-tag with class="okay"
     */
    public static function okay(string $string): string
    {
        return '<p class="okay">' . $string . '</p>';
    }

    /**
     * Wrap a string into p-tag with class="file"
     */
    public static function fileBlock(string $string): string
    {
        return '<p class="file">' . $string . '</p>';
    }

    /**
     * Replace ampersands for a textarea
     */
    public static function textareaTransform(string $text): string
    {
        return stripslashes(str_replace('&', '&amp;', $text));
    }

    /**
     * Get a hidden input field, filled with post-data for this name
     * @param string $name name for this field
     * @param string $value value, if empty uses post-data
     */
    public static function hiddenInput(string $name, string $value = ''): string
    {
        if ($value === '' && isset($_POST[$name])) {
            $value = $_POST[$name];
        }

        return '<input type="hidden" name="' . $name . '" value="' . $value . '">';
    }

    /**
     * Get a select box with given options
     * @param string $name Name for this select-box
     * @param mixed $checked [optional] bool for beeing checked or not, otherwise checks for post-data
     */
    public static function checkBox(string $name, $checked = -1, bool $noHiddenSent = false): string
    {
        if ($checked === -1) {
            $checked = isset($_POST[$name]) && $_POST[$name] != 0;
        }

        $hiddenSent = self::hiddenInput($name . '_sent', 'true');

        return (! $noHiddenSent ? $hiddenSent : '') . '<input type="checkbox" name="' . $name . '"' . self::Checked($checked) . '>';
    }

    /**
     * Get ' checked' if boolean value is true
     * @param mixed $value
     * @param mixed $value_to_be_checked [optional]
     */
    public static function Checked($value, $value_to_be_checked = null): string
    {
        if ($value_to_be_checked !== null) {
            $value = ($value == $value_to_be_checked);
        }

        $value ??= false;

        return ($value === true)
            ? ' checked'
            : '';
    }

    /**
     * Get a select box with given options
     * @param string $name Name for this select-box
     * @param array $options Array containing values as indices, displayed text as values (may be array: 'text' => ..., 'data-...' => ...)
     * @param mixed $selected Value to be selected
     * @param string $id [optional]
     */
    public static function selectBox(string $name, array $options, $selected = false, string $id = '', string $class = ''): string
    {
        if ($selected === false && isset($_POST[$name])) {
            $selected = $_POST[$name];
        }

        $html = '<select name="' . $name . '"' . (! empty($id) ? ' id="' . $id . '"' : '') . (! empty($class) ? ' class="' . $class . '"' : '') . '>';

        foreach ($options as $value => $text) {
            $additionalAttributes = [];
            $displayedText = $text;

            if (is_array($text)) {
                $displayedText = $text['text'] ?? '?';

                foreach ($text as $attr => $attrVal) {
                    if ($attr != 'text') {
                        $additionalAttributes[] = ' ' . $attr . '="' . $attrVal . '"';
                    }
                }
            }

            $html .= '<option value="' . $value . '"' . self::Selected($value, $selected) . implode('', $additionalAttributes) . '>' . $displayedText . '</option>';
        }

        return $html . '</select>';
    }

    /**
     * Get ' selected' if boolean value is true
     * @param mixed $value
     * @param mixed $value_to_be_checked
     */
    public static function Selected($value, $value_to_be_checked = null): string
    {
        if ($value_to_be_checked !== null) {
            if (is_array($value_to_be_checked)) {
                $value = in_array($value, $value_to_be_checked);
            } else {
                $value = ($value_to_be_checked === '') ? ($value === $value_to_be_checked) : ($value == $value_to_be_checked);
            }
        }

        return ($value === true)
            ? ' selected'
            : '';
    }
}
