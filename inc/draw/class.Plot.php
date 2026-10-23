<?php

/**
 * This file contains class::Plot
 * @package Runalyze\Draw
 */

use Runalyze\Configuration;

/**
 * General plotting class
 * @author Hannes Christiansen
 * @package Runalyze\Draw
 */
class Plot
{
    /**
     * Internal Data, is public for direct access
     */
    public array $Data = [];

    /**
     * Array for all options for FLOT
     */
    public array $Options = [];

    /**
     * Options only used by Runalyze's plot handler
     */
    public array $PlotOptions = [];

    /**
     * Array containing annotations for this plot
     * @var array<mixed, array<string, string|float>>
     */
    private array $Annotations = [];

    /**
     * Error string
     */
    private string $ErrorString = '';

    /**
     * @param mixed $width
     * @param mixed $height
     */
    public function __construct(
        private readonly string $cssID,
        private $width = 480,
        private $height = 190
    ) {}

    public function raiseError(string $string): void
    {
        $this->ErrorString = $string;
    }

    /**
     * Get standard div for a special plot
     * @param string $id
     */
    public static function getDivFor($id, int $width, int $height): string
    {
        return '<div style="position:relative;width:' . $width . 'px;height:' . $height . 'px;margin:0 auto;">' . self::getInnerDivFor($id, $width, $height) . '</div>';
    }

    /**
     * Get standard div for a special plot
     * @param string $id
     */
    public static function getInnerDivFor($id, int $width, int $height, bool $hidden = false, string $class = ''): string
    {
        return '<div class="flot ' . Ajax::$IMG_WAIT . ' ' . $class . ($hidden ? ' flot-hide' : '') . '" id="' . $id . '" style="width:' . $width . 'px;height:' . $height . 'px;position:absolute;"></div>';
    }

    /**
     * Get a div for this plot
     */
    public function getDiv(): string
    {
        return self::getDivFor($this->cssID, $this->width, $this->height);
    }

    public function outputDiv(): void
    {
        echo $this->getDiv();
    }

    public function outputJavaScript(bool $removeOldPlot = false): void
    {
        if ($removeOldPlot) {
            echo Ajax::wrapJS('RunalyzePlot.remove("' . $this->cssID . '");');
        }

        $this->convertData();

        $bindedCode = ($this->ErrorString !== '') ? $this->getJSForError() : $this->getMainJS();

        echo Ajax::wrapJS('RunalyzePlot.preparePlot("' . $this->cssID . '","' . $this->width . '","' . $this->height . '",function(){' . $bindedCode . '});');
    }

    /**
     * Get main functionalities for this plot
     */
    private function getMainJS(): string
    {
        $encodedData = json_encode($this->Data);

        if ($encodedData === false) {
            $this->ErrorString = sprintf(
                '%s (%s: %u)',
                __('There is an unknown problem with this plot.'),
                __('Error code'),
                json_last_error()
            );

            return $this->getJSForError();
        }

        return 'RunalyzePlot.addPlot("' . $this->cssID . '", '
                . $encodedData . ', '
                . Ajax::json_encode_jsfunc($this->Options) . ', '
                . json_encode($this->PlotOptions) . ', '
                . json_encode($this->Annotations) . ');';
    }

    /**
     * Get code for an error
     */
    private function getJSForError(): string
    {
        return '$("#' . $this->cssID . '").append(\'<div class="flot-error"><span>' . $this->ErrorString . "</span></div>');";
    }

    /**
     * Convert internal data to correct array for JSON
     */
    private function convertData(): void
    {
        foreach ($this->Data as $i => $Data) {
            $Points = [];
            foreach ($Data['data'] as $x => $y) {
                $Points[] = [$x, $y];
            }

            $this->Data[$i]['data'] = $Points;
        }

        if (Configuration::ActivityView()->smoothCurves() && ! isset($this->Options['series']['curvedLines']['apply'])) {
            $this->Options['series']['curvedLines']['apply'] = true;
        }

        if ($this->Data === [] && $this->ErrorString === '') {
            $this->raiseError('Es sind keine Daten vorhanden.');
        }
    }

    /**
     * Add an annotation to plot
     * @param float $toX relative repositioning in px
     * @param float $toY relative repositioning in px
     */
    public function addAnnotation(float $x, float $y, string $text, float $toX = 0.0, float $toY = 0.0): void
    {
        $this->Annotations[] = [
            'x' => $x,
            'y' => $y,
            'text' => $text,
            'toX' => $toX,
            'toY' => $toY,
        ];
    }

    /**
     * Clear all annotations
     */
    public function clearAnnotations(): void
    {
        $this->Annotations = [];
    }

    /**
     * Use chart for a line with steps
     */
    public function lineWithSteps(): void
    {
        $this->Options['series']['lines']['steps'] = true;
    }

    /**
     * Use chart for a line with steps
     */
    public function lineWithPoints(): void
    {
        $this->Options['series']['lines']['show'] = true;
        $this->Options['series']['points']['show'] = true;
    }

    /**
     * Use chart for points
     */
    public function showPoints(int $size = -1): void
    {
        $this->Options['series']['points']['show'] = true;

        if ($size !== -1) {
            $this->Options['series']['points']['lineWidth'] = $size;
        }
    }

    /**
     * Show series as points
     */
    public function showAsPoints(int $series): void
    {
        $this->Data[$series]['points']['show'] = true;
    }

    /**
     * Set line width for series
     */
    public function setLineWidth(int $series, int $width): void
    {
        $this->Data[$series]['lines']['lineWidth'] = $width;
    }

    /**
     * Set shadow width for series
     */
    public function setShadowSize(int $series, int $size): void
    {
        $this->Data[$series]['shadowSize'] = $size;
    }

    /**
     * Show specific series as bars
     */
    public function showAsBars(int $series, $barWidth = 0, $lineWidth = 0): void
    {
        $this->Data[$series]['bars']['show'] = true;

        if ($barWidth > 0) {
            $this->Data[$series]['bars']['barWidth'] = $barWidth;
        }

        if ($lineWidth > 0) {
            $this->Data[$series]['bars']['lineWidth'] = $lineWidth;
        }
    }

    /**
     * Use chart for bar-plot
     * @param bool $withPadding Only useful if ticks are set
     */
    public function showBars(bool $withPadding = false): void
    {
        $this->Options['series']['bars']['show'] = true;

        if ($withPadding) {
            $maxLength = 0;
            foreach ($this->Data as $data) {
                if (count($data['data']) > $maxLength) {
                    $maxLength = count($data['data']);
                }
            }

            $this->Options['xaxis']['min'] = -1;
            $this->Options['xaxis']['max'] = $maxLength;
        }
    }

    /**
     * Use stacked lines/bars
     */
    public function stacked(): void
    {
        $this->Options['series']['stack'] = 'stack';
        $this->Options['series']['lines']['fill'] = true;
    }

    public function addThreshold(string $axis, float $from, string $color = '#000', int $lineWidth = 1): void
    {
        $this->Options['grid']['markings'][] = [
            'color' => $color,
            'lineWidth' => $lineWidth,
            $axis . 'axis' => [
                'from' => $from,
                'to' => $from,
            ],
        ];
    }

    public function addMarkingArea(string $axis, float $from, float $to, string $color = 'rgba(255,255,255,0.2)'): void
    {
        $this->Options['grid']['markings'][] = [
            'color' => $color,
            $axis . 'axis' => [
                'from' => $from,
                'to' => $to,
            ],
        ];
    }

    /**
     * Set the margin for the whole grid
     */
    public function setMarginForGrid(int $margin): void
    {
        $this->Options['grid']['minBorderMargin'] = $margin;
    }

    /**
     * Put grid above data
     */
    public function setGridAboveData(): void
    {
        $this->Options['grid']['aboveData'] = true;
    }

    /**
     * Set legend as table (not in one line as default)
     */
    public function setLegendAsTable(string $position = 'nw'): void
    {
        $this->Options['legend']['noColumns'] = 1;
        $this->Options['legend']['position'] = $position;
    }

    /**
     * Set specific lines to be filled
     */
    public function setLinesFilled(array $keys = [], float $opacity = 0.7): void
    {
        if ($keys === []) {
            $keys = array_keys($this->Data);
        }

        foreach ($keys as $key) {
            $this->Data[$key]['lines']['fill'] = $opacity;
        }
    }

    /**
     * Set smoothing
     */
    public function smoothing(bool $flag = true, ?bool $fit = null): void
    {
        $this->Options['series']['curvedLines']['apply'] = $flag;
        $this->Options['series']['curvedLines']['monotonicFit'] = true;

        if ($fit !== null) {
            $this->Options['series']['curvedLines']['monotonicFit'] = $fit;
        }
    }

    /**
     * Hide labels on xaxis
     */
    public function hideXLabels(): void
    {
        $this->Options['xaxis']['show'] = false;
    }

    /**
     * Set labels on xaxis
     */
    public function setXLabels(array $array): void
    {
        $this->Options['xaxis']['ticks'] = $array;
    }

    /**
     * Set axis-mode to time
     */
    public function setXAxisAsTime(): void
    {
        $this->Options['xaxis']['mode'] = 'time';
        $this->Options['xaxis']['monthNames'] = [
            __('Jan'),
            __('Feb'),
            __('Mar'),
            __('Apr'),
            __('May'),
            __('Jun'),
            __('Jul'),
            __('Aug'),
            __('Sep'),
            __('Oct'),
            __('Nov'),
            __('Dec'),
        ];
    }

    /**
     * Set axis-mode to time with specific format
     */
    public function setXAxisTimeFormat(string $format): void
    {
        $this->setXAxisAsTime();
        $this->Options['xaxis']['timeformat'] = $format;
    }

    /**
     * Set x-axis limits to a specific year
     */
    public function setXAxisLimitedTo(int $Year): void
    {
        $this->Options['xaxis']['min'] = mktime(1, 0, 0, 1, 1, $Year) . '000';
        $this->Options['xaxis']['max'] = mktime(1, 0, 0, 1, 0, $Year + 1) . '000';
    }

    /**
     * Set maximum of x-axis as today
     */
    public function setXAxisMaxToToday(): void
    {
        $this->Options['xaxis']['max'] = time() . '000';
    }

    /**
     * Set axis-mode to time
     * @param int|bool $axis
     */
    public function setYAxisAsTime($axis = false): void
    {
        if ($axis === false) {
            $this->Options['yaxis']['mode'] = 'time';
        } else {
            $this->Options['yaxes'][$axis - 1]['mode'] = 'time';
        }
    }

    /**
     * Set axis-mode to time with specific format
     * @param int|bool $axis
     */
    public function setYAxisTimeFormat(string $format, $axis = false): void
    {
        $this->setYAxisAsTime($axis);

        if ($axis === false) {
            $this->Options['yaxis']['timeformat'] = $format;
        } else {
            $this->Options['yaxes'][$axis - 1]['timeformat'] = $format;
        }
    }

    public function addYAxis(int $i, string $position, bool $align = true, int $alignTo = 1): void
    {
        $this->Options['yaxes'][$i - 1]['position'] = $position;

        if ($position === 'right' && $align) {
            $this->Options['yaxes'][$i - 1]['alignTicksWithAxis'] = $alignTo;
        }
    }

    public function hideYAxis(int $i): void
    {
        $this->Options['yaxes'][$i - 1]['show'] = false;
    }

    /**
     * Add unit to y axis
     */
    public function addYUnit(int $i, string $unit, int $roundTo = 2, float $factor = 1.0): void
    {
        if ($factor != 1) {
            $this->Options['yaxes'][$i - 1]['tickFormatter'] = 'function (v) { v = v * ' . $factor . '; return ' . $this->jsRoundUnit($roundTo) . " + ' " . $unit . "'; }";
        } else {
            $this->Options['yaxes'][$i - 1]['tickFormatter'] = 'function (v) { return ' . $this->jsRoundUnit($roundTo) . " + ' " . $unit . "'; }";
        }
    }

    /**
     * Add unit to x axis
     */
    public function setXUnit(string $unit, int $roundTo = 2): void
    {
        $this->Options['xaxis']['tickFormatter'] = 'function (v) { return ' . $this->jsRoundUnit($roundTo) . " + ' " . $unit . "'; }";
    }

    public function setXLabel(string $string): void
    {
        $this->Options['xaxis']['label'] = $string;
    }

    /**
     * Add unit to x axis
     */
    public function setXUnitFactor(float $factor, string $unit, int $roundTo = 2): void
    {
        $this->Options['xaxis']['tickFormatter'] = 'function (v) { v = v * ' . $factor . '; return ' . $this->jsRoundUnit($roundTo) . " + ' " . $unit . "'; }";
    }

    /**
     * JS function to round
     */
    private function jsRoundUnit(int $roundTo): string
    {
        if ($roundTo === 0) {
            return 'Math.round(v)';
        }

        return 'Math.round(v * Math.pow(10,' . $roundTo . ')) / Math.pow(10,' . $roundTo . ')';
    }

    /**
     * Set size for ticks on y-axis
     */
    public function setYTicks(int $axis, ?int $tickSize, bool $decimals = false): void
    {
        $this->Options['yaxes'][$axis - 1]['minTickSize'] = $tickSize;

        if ($tickSize === null) {
            unset($this->Options['yaxes'][$axis - 1]['minTickSize']);
        }

        if ($decimals) {
            $this->Options['yaxes'][$axis - 1]['tickDecimals'] = $decimals;
        }
    }

    /**
     * Set limits to y-axis
     * @param mixed $min
     * @param mixed $max
     * @param mixed $factor
     */
    public function setYLimits(int $axis, $min, $max, bool $autoscale = false, $factor = 'auto'): void
    {
        if ($autoscale) {
            $diff = $max - $min;
            if ($factor == 'auto') {
                $factor = 10 ** (round(log10($diff)) - 1);

                if ($factor > 10) {
                    $factor = 10;
                }
            }

            $minScaled = $min > 0 ? max(0, $min / $factor - 0.02 * $diff) : $min / $factor - 0.02 * $diff;
            $min = floor($minScaled) * $factor;
            $max = ceil($max / $factor + 0.02 * $diff) * $factor;

            $this->setYTicks($axis, $factor);
        }

        $this->Options['yaxes'][$axis - 1]['min'] = $min;
        $this->Options['yaxes'][$axis - 1]['max'] = $max;
    }

    public function setYAxisLabels(int $axis, array $ticks): void
    {
        $this->Options['yaxes'][$axis - 1]['ticks'] = $ticks;
    }

    /**
     * Set y axis to reverse
     */
    public function setYAxisReverse(int $axis): void
    {
        $this->Options['yaxes'][$axis - 1]['transform'] = 'function(v){return -v;}';
        $this->Options['yaxes'][$axis - 1]['inverseTransform'] = 'function(v){return -v;}';
    }

    /**
     * Set y axis to reverse pace scale
     */
    public function setYAxisPaceReverse(int $axis): void
    {
        $this->Options['yaxes'][$axis - 1]['transform'] = 'function(v){return 1/(v);}';
        $this->Options['yaxes'][$axis - 1]['inverseTransform'] = 'function(v){return 1/v;}';
    }

    /**
     * Set all zero points to null
     */
    public function setZeroPointsToNull(): void
    {
        foreach ($this->Data as $series => $data) {
            $this->Data[$series]['data'] = array_map(PLOT__setZeroToNullMapper(...), $data['data']);
        }
    }

    /**
     * Correct special characters like umlaute to unicode-HTML
     */
    public static function correctSpecialChars(string $string): string
    {
        $string = mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
        $encrypted = ['ß',      'Ä',      'Ö',      'Ü',      'ä',      'ö',      'ü'];
        $correct = ['&#223;', '&#196;', '&#214;', '&#220;', '&#228;', '&#246;', '&#252;'];

        return str_replace($encrypted, $correct, $string);
    }

    /**
     * Get JavaScript-timestamp for a day of a year
     */
    public static function dayOfYearToJStime(int $year, int $day, int $hour = 12): string
    {
        return mktime($hour, 0, 0, 1, $day, $year) . '000';
    }

    /**
     * Correct all values as JS-timestamps
     */
    public static function correctValuesForTime(array $array): array
    {
        return array_map(PLOT__correctValuesMapperForTime(...), $array);
    }

    /**
     * Correct all pace-values to km/h
     */
    public static function correctValuesFromPaceToKmh(array $array): array
    {
        return array_map(PLOT__correctValuesMapperFromPaceToKmh(...), $array);
    }
}

/**
 * Mapper for Plot::correctValuesForTime
 *
 * Correct php-timestamps to JS-timestamps
 * @param mixed $v
 */
function PLOT__correctValuesMapperForTime($v): float
{
    return round($v * 1000);
}

/**
 * Mapper for Plot::correctValuesFromPaceToKmh
 * @param mixed $v
 */
function PLOT__correctValuesMapperFromPaceToKmh($v): int|float
{
    if ($v == 0) {
        return 0;
    }

    return round(3600 / $v, 2);
}

/**
 * Mapper for Plot::setZeroPointsToNull
 * @param mixed $v
 * @return mixed
 */
function PLOT__setZeroToNullMapper($v)
{
    if ($v == 0) {
        return null;
    }

    return $v;
}
