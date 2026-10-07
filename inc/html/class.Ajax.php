<?php

/**
 * This file contains class::Ajax
 * @package Runalyze\HTML
 */
/**
 * JavaScript/jQuery class
 *
 * @author Hannes Christiansen
 * @package Runalyze\HTML
 * @deprecated since v3.1
 */
class Ajax
{
    /**
     * CSS-class for waiter-image
     */
    public static string $IMG_WAIT = 'wait-img';

    /**
     * ID for plugin to reload, can be empty to reload all plugins
     */
    public static ?int $RELOAD_PLUGIN_ID = null;

    /**
     * Enum: Reload flag - no reload
     */
    public static int $RELOAD_NONE = 0;

    /**
     * Enum: Reload flag - reload databrowser
     */
    public static int $RELOAD_DATABROWSER = 1;

    /**
     * Enum: Reload flag - reload training
     */
    public static int $RELOAD_TRAINING = 2;

    /**
     * Enum: Reload flag - reload databrowser and training
     */
    public static int $RELOAD_DATABROWSER_AND_TRAINING = 3;

    /**
     * Enum: Reload flag - reload all plugins
     */
    public static int $RELOAD_PLUGINS = 4;

    /**
     * Enum: Reload flag - reload all elements with jQuery
     */
    public static int $RELOAD_ALL = 5;

    /**
     * Enum: Reload flag - reload complete page
     */
    public static int $RELOAD_PAGE = 6;

    /**
     * Current reload flag
     */
    private static int $currentReloadFlag = 0;

    /**
     * Init own JS-library on frontend (direct output)
     */
    public static function initJSlibrary(): void
    {
        $Options = [];
        $Options['sharedView'] = Request::isOnSharedPage();

        echo self::wrapJS('Runalyze.init(' . json_encode($Options) . ');');

        if (defined('NOKIA_HERE_APPID') && NOKIA_HERE_APPID != '' && defined('NOKIA_HERE_TOKEN') && NOKIA_HERE_TOKEN != '') {
            echo self::wrapJS('Runalyze.Options.setNokiaLayerAuth("' . NOKIA_HERE_APPID . '", "' . NOKIA_HERE_TOKEN . '")');
        }

        if (defined('THUNDERFOREST_API_KEY') && THUNDERFOREST_API_KEY != '') {
            echo self::wrapJS('Runalyze.Options.setThunderforestLayerAuth("' . THUNDERFOREST_API_KEY . '")');
        }

        if (defined('MAPBOX_API_KEY') && MAPBOX_API_KEY != '') {
            echo self::wrapJS('Runalyze.Options.setMapboxLayerAuth("' . MAPBOX_API_KEY . '")');
        }
    }

    public static function setReloadFlag(int $Flag): void
    {
        self::$currentReloadFlag = self::combineReloadFlags(self::$currentReloadFlag, $Flag);
    }

    public static function combineReloadFlags(int $current, int $new): int
    {
        if ($current === self::$RELOAD_PAGE || $new === self::$RELOAD_PAGE) {
            return self::$RELOAD_PAGE;
        }

        if ($current === self::$RELOAD_ALL || $new === self::$RELOAD_ALL) {
            return self::$RELOAD_ALL;
        }

        $contentFlags = [self::$RELOAD_DATABROWSER, self::$RELOAD_TRAINING, self::$RELOAD_DATABROWSER_AND_TRAINING];

        if (
            ($current === self::$RELOAD_PLUGINS && in_array($new, $contentFlags, true))
            || ($new === self::$RELOAD_PLUGINS && in_array($current, $contentFlags, true))
        ) {
            return self::$RELOAD_ALL;
        }

        if (
            in_array($current, [self::$RELOAD_DATABROWSER, self::$RELOAD_TRAINING], true)
            && in_array($new, [self::$RELOAD_DATABROWSER, self::$RELOAD_TRAINING], true)
            && $current !== $new
        ) {
            return self::$RELOAD_DATABROWSER_AND_TRAINING;
        }

        return max($current, $new);
    }

    /**
     * Set ID of plugin to reload
     */
    public static function setPluginIDtoReload(int $ID): void
    {
        self::$RELOAD_PLUGIN_ID = $ID;
    }

    public static function getReloadCommand(): string
    {
        return match (self::$currentReloadFlag) {
            self::$RELOAD_PAGE => self::wrapJS('Runalyze.reloadPage();'),
            self::$RELOAD_ALL => self::wrapJS('Runalyze.reloadContent();'),
            self::$RELOAD_PLUGINS => self::wrapJS('Runalyze.reloadAllPlugins(' . self::$RELOAD_PLUGIN_ID . ');'),
            self::$RELOAD_DATABROWSER_AND_TRAINING => self::wrapJS('Runalyze.reloadDataBrowserAndTraining();'),
            self::$RELOAD_TRAINING => self::wrapJS('Runalyze.Training.reload();'),
            self::$RELOAD_DATABROWSER => self::wrapJS('Runalyze.DataBrowser.reload();'),
            default => '',
        };
    }

    /**
     * Gives a HTML-link for using jTraining
     * @param int $id ID of the training
     * @param string $name Name of the link to be displayed
     * @param bool $closeOverlay Boolean flag: Should the overlay be closed after clicking? (default: false)
     */
    public static function trainingLink(int $id, string $name, bool $closeOverlay = false, $classes = '', $htmlID = ''): string
    {
        return '<a ' . (! empty($htmlID) ? 'id="' . $htmlID . '" ' : '') . 'class="training ' . $classes . '" href="activity/' . $id . '" rel="' . $id . '"' . ($closeOverlay ? ' onclick="Runalyze.Overlay.close()"' : '') . '>' . $name . '</a>';
    }

    /**
     * Get onclick-string for loading training
     * @param ?int $id ID of the training
     */
    public static function trainingLinkAsOnclick(?int $id): string
    {
        if (FrontendShared::$IS_SHOWN) {
            return 'onclick="Pace.restart();Runalyze.Training.load(' . $id . ", '" . SharedLinker::getUrlFor($id) . '\', event)"';
        }

        return 'onclick="Pace.restart();Runalyze.Training.load(' . $id . ', false, event)"';
    }

    /**
     * Get html-code for jquery-tooltip
     * @param bool|string $atLeft can be 'atRight'
     */
    public static function tooltip(string $html, string $tooltip, bool|string $atLeft = false, bool $onlyAttributes = false): string
    {
        if ($tooltip === '') {
            return $html;
        }

        $class = $atLeft ? is_string($atLeft) ? 'class="' . $atLeft . '"' : 'class="atLeft" ' : '';

        if ($onlyAttributes) {
            return $class . 'rel="tooltip" title="' . htmlspecialchars($tooltip) . '"';
        }

        return '<span ' . $class . 'rel="tooltip" title="' . htmlspecialchars($tooltip) . '">' . $html . '</span>';
    }

    /**
     * Get code for toolbar navigation for links as array (tag => ..., subs => array(..., ...))
     */
    public static function toolbarNavigation(array $Links, string $AdditionalClasses = ''): string
    {
        if ($Links === []) {
            return '';
        }

        $code = '<ul class="' . $AdditionalClasses . '">';

        foreach ($Links as $Link) {
            if (is_array($Link) && isset($Link['tag'])) {
                $hasSubcontainer = isset($Link['subs']) && is_array($Link['subs']);

                $code .= '<li' . ($hasSubcontainer ? ' class="with-submenu"' : '') . '>';
                $code .= $Link['tag'];

                if ($hasSubcontainer) {
                    $code .= '<ul class="submenu">';

                    foreach ($Link['subs'] as $Sublink) {
                        $code .= '<li>' . $Sublink . '</li>';
                    }

                    $code .= '</ul>';
                }

                $code .= '</li>';
            }
        }

        return $code . '</ul>';
    }

    /**
     * Gives a HTML-link for using jToggle()
     * @param string $link        The normal HTML-link
     * @param string $toggle_id   The ID of the css-container to toggle
     */
    public static function toggle(string $link, string $toggle_id): string
    {
        $link = self::insertClass($link, 'toggle');

        return self::insertRel($link, $toggle_id);
    }

    /**
     * Gives a HTML-link for using jWindow()
     * @param string $link     The normal HTML-link
     * @param string $size     Enum: big|normal|small
     */
    public static function window(string $link, string $size = 'normal'): string
    {
        $link = self::insertClass($link, 'window');
        if ($size === 'big' || $size === 'small') {
            return self::insertDataSize($link, $size);
        }

        return $link;
    }

    /**
     * Gives a HTML-link for using jChange()
     * @param string $name   Displayed name for this link
     * @param string $target ID of surrounding div-container
     * @param string $href   ID of div-container to be displayed
     */
    public static function change(string $name, string $target, string $href, string $additional_class = ''): string
    {
        if (! str_starts_with($href, '#')) {
            $href = '#' . $href;
        }

        if ($additional_class !== '') {
            $additional_class .= ' ';
        }

        return '<a class="' . $additional_class . 'change" target="' . $target . '" href="' . $href . '">' . $name . '</a>';
    }

    /**
     * Gives a HTML-link for using jLinks()
     * @param string $name    Displayed name for this link
     * @param string $target  ID of target div-container
     * @param string $href    URL to be loaded
     * @param string $data    data to be passed
     */
    public static function link(string $name, string $target, string $href, string $data = '', string $title = '', ?string $onclick = null): string
    {
        return '<a class="ajax" href="' . $href . '" target="' . $target . '" rel="' . $data . '"' . (! empty($title) ? ' title="' . $title . '"' : '') . ' ' . (($onclick) ? ' onclick="' . $onclick . '"' : '') . '>' . $name . '</a>';
    }

    /**
     * Transform text to link for changing flot
     */
    public static function flotChange(string $text, string $divID, string $flotID, bool $active = true): string
    {
        return '<span class="link' . ($active ? '' : ' unimportant') . ' flotChanger-' . $divID . ' flotChanger-id-' . $flotID . '" onclick="Runalyze.flotChange(\'' . $divID . "','" . $flotID . '\')">' . $text . '</span>';
    }

    /**
     * Adds a new class-value or creates a class-attribute
     * @param string $link    The full HTML-link
     * @param string $class   The new css-class
     */
    public static function insertClass(string $link, string $class): string
    {
        $text = preg_replace('#<a ([^>]*?)class="(.+?)"#i', '<a \\1class="' . $class . ' \\2"', $link);
        if ($text == $link) {
            return str_replace('<a ', '<a class="' . $class . '" ', $text);
        }

        return $text;
    }

    /**
     * Overwrites an existing rel-attribute or creates a new one
     * @param string $link   The full HTML-link
     * @param string $rel    The new rel-value
     */
    public static function insertRel(string $link, string $rel): string
    {
        $text = preg_replace('#rel="(.+?)"#i', 'rel="' . $rel . '"', $link);
        if ($text == $link) {
            return str_replace('<a ', '<a rel="' . $rel . '" ', $text);
        }

        return $text;
    }

    /**
     * Overwrites an existing data-size-attribute or creates a new one
     * @param string $link   The full HTML-link
     * @param string $rel    The new data-size-value
     */
    public static function insertDataSize(string $link, string $rel): string
    {
        $text = preg_replace('#data-size="(.+?)"#i', 'data-size="' . $rel . '"', $link);
        if ($text == $link) {
            return str_replace('<a ', '<a data-size="' . $rel . '" ', $text);
        }

        return $text;
    }

    /**
     * Wrap JavaScript into code block
     */
    public static function wrapJS(string $code): string
    {
        return '<script>' . $code . '</script>';
    }

    /**
     * Wrap JavaScript into code block for beeing executed on document ready
     */
    public static function wrapJSforDocumentReady(string $code): string
    {
        return self::wrapJS('(function($){$(document).ready(function(){ ' . $code . ' });})(jQuery);');
    }

    /**
     * Wrap JavaScript into code block for beeing an unnamed function
     */
    public static function wrapJSasFunction(string $code): string
    {
        return self::wrapJS('$(function(){ ' . $code . ' });');
    }

    /**
     * JSON encode with function
     * @param array<string, mixed> $funcs
     */
    public static function json_encode_jsfunc(array $input, array $funcs = [], int $level = 0): string|array
    {
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $ret = self::json_encode_jsfunc($value, $funcs, 1);
                $input[$key] = $ret[0];
                $funcs = $ret[1];
            } elseif (str_starts_with($value, 'function') || str_starts_with($value, 'RunalyzePlot.')) {
                $func_key = '#' . uniqid((string) random_int(0, mt_getrandmax()), true) . '#';
                $funcs[$func_key] = $value;
                $input[$key] = $func_key;
            }
        }

        if ($level === 1) {
            return [$input, $funcs];
        }

        $input_json = json_encode($input);
        foreach ($funcs as $key => $value) {
            $input_json = str_replace('"' . $key . '"', $value, $input_json);
        }

        return $input_json;
    }

    /**
     * Get code for closing overlay
     */
    public static function closeOverlay(): string
    {
        return self::wrapJS('Runalyze.Overlay.close();');
    }

    /**
     * Create code for binding tablesorter
     */
    public static function createTablesorterFor(string $selector, bool $reinit = false): void
    {
        echo self::wrapJSforDocumentReady('$("' . $selector . '").tablesorterAutosort(' . ($reinit ? 'true' : '') . ');');
    }

    /**
     * Create code for binding tablesorter with pager
     */
    public static function createTablesorterWithPagerFor(string $selector, bool $reinit = false): void
    {
        echo self::getTablesorterWithPagerFor($selector, $reinit);
    }

    /**
     * Create code for binding tablesorter with pager
     */
    public static function getTablesorterWithPagerFor(string $selector, bool $reinit = false): string
    {
        $Code = self::getPagerDiv();

        return $Code . self::wrapJSforDocumentReady('$("' . $selector . '").tablesorterWithPager(' . ($reinit ? 'true' : '') . ');');
    }

    /**
     * Get div for pager for tables
     */
    private static function getPagerDiv(): string
    {
        return '
<div id="pager" class="pager c">
    <form>
        <span class="link first">|&laquo; ' . __('Start') . '</span>
        <span class="link prev">&laquo; ' . __('previous') . '</span>
        <input type="text" class="pagedisplay">
        <span class="link next">' . __('next') . ' &raquo;</span>
        <span class="link last">' . __('End') . ' &raquo;|</span>

        <select class="pagesize">
            <option value="10">10 ' . __('per page') . '&nbsp;</option>
            <option value="20" selected>20 ' . __('per page') . '&nbsp;</option>
            <option value="30">30 ' . __('per page') . '&nbsp;</option>
            <option value="40">40 ' . __('per page') . '&nbsp;</option>
            <option value="50">50 ' . __('per page') . '&nbsp;</option>
        </select>
    </form>
</div>';
    }
}
