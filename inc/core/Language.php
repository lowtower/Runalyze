<?php

/**
 * This file contains class::Language
 * @package Runalyze
 */

namespace Runalyze;

use Runalyze\Util\InterfaceChoosable;

/**
 * Language class
 * @author Michael Pohl
 * @package Runalyze
 */
class Language implements InterfaceChoosable
{
    public const string GET_KEY = 'lang';

    public const string COOKIE_KEY = 'lang';

    private static string $LOCALE_DIR = './vendor/runalyze/translations/gettext';

    private static string $CURRENT_LANG = 'en';

    private static ?array $AVAILABLE_LANGUAGES = null;

    public function __construct(string $language = '', string $domain = 'runalyze')
    {
        $this->setLocaleDir();

        self::readAvailableLanguages();

        if (empty($language)) {
            $language = $this->guessLanguage();
        }

        self::setLanguage($language);

        $this->setDomain($domain);
    }

    public static function getChoices(): array
    {
        $languages = [];

        foreach (self::availableLanguages() as $name => $lang) {
            $languages[$lang[0]] = $name;
        }

        return $languages;
    }

    protected function setLocaleDir()
    {
        self::$LOCALE_DIR = __DIR__ . '/../../vendor/runalyze/translations/gettext';
    }

    /**
     * Set Language for user
     */
    public static function setLanguage(string $language, bool $overwriteGetParameter = true): bool
    {
        $supportedLanguages = self::availableLanguages();

        if (! isset($supportedLanguages[$language])) {
            return false;
        }

        if (! $overwriteGetParameter && isset($_GET[self::GET_KEY]) && isset($supportedLanguages[$_GET[self::GET_KEY]])) {
            return false;
        }

        $locale = $supportedLanguages[$language][1];

        self::$CURRENT_LANG = $language;

        putenv("LANG={$locale}");
        setlocale(LC_ALL, $locale);
        setlocale(LC_NUMERIC, 'C');

        if (! headers_sent() && (! isset($_COOKIE[self::COOKIE_KEY]) || $_COOKIE[self::COOKIE_KEY] != $language)) {
            setcookie(self::COOKIE_KEY, $language);
        }

        return true;
    }

    protected function setDomain(string $domain)
    {
        bind_textdomain_codeset($domain, 'UTF-8');

        bindtextdomain($domain, self::$LOCALE_DIR);
        textdomain($domain);
    }

    private static function readAvailableLanguages(): void
    {
        $languages = [];
        $configFile = __DIR__ . '/../../config/locales.php';

        if (file_exists($configFile)) {
            $config = require $configFile;

            if (is_array($config)) {
                foreach ($config as $short => $lang) {
                    $languages[$short] = [$lang['name'], $lang['locale']];
                }
            }
        }

        self::$AVAILABLE_LANGUAGES = $languages;
    }

    public static function availableLanguages(): array
    {
        if (self::$AVAILABLE_LANGUAGES === null) {
            self::readAvailableLanguages();
        }

        return self::$AVAILABLE_LANGUAGES;
    }

    /**
     * Return currently selected language
     */
    public static function getCurrentLanguage(): string
    {
        return self::$CURRENT_LANG;
    }

    /**
     * Guess language
     * Based on get parameter or language accepted by the browser
     */
    protected function guessLanguage(): string
    {
        if (isset($_GET[self::GET_KEY])) {
            if (isset(self::$AVAILABLE_LANGUAGES[$_GET[self::GET_KEY]])) {
                return $_GET[self::GET_KEY];
            }
        }

        return $_COOKIE[self::COOKIE_KEY] ?? $this->getBrowserLanguage(self::$AVAILABLE_LANGUAGES);
    }

    protected function getBrowserLanguage(array $supported, string $default = 'en'): string
    {
        $supp = [];

        foreach ($supported as $lang => $isSupported) {
            if ($isSupported) {
                $supp[strtolower($lang)] = $lang;
            }
        }

        if (! count($supp)) {
            return $default;
        }

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $match = $this->matchAccept(
                $_SERVER['HTTP_ACCEPT_LANGUAGE'],
                $supp
            );

            if ($match !== null) {
                return $match;
            }
        }

        return $default;
    }

    /**
     * Parses a weighed "Accept" HTTP header and matches it against a list
     * of supported options
     *
     * @param string $header The HTTP "Accept" header to parse
     * @param array $supported A list of supported values
     *
     * @return string|null a matched option, or null if no match
     */
    protected function matchAccept(string $header, array $supported): ?string
    {
        $matches = $this->sortAccept($header);

        foreach ($matches as $key => $q) {
            if (isset($supported[$key])) {
                return $supported[$key];
            }
        }

        // If any (i.e. "*") is acceptable, return the first supported format
        if (isset($matches['*'])) {
            return array_shift($supported);
        }

        return null;
    }

    /**
     * Parses and sorts a weighed "Accept" HTTP header
     *
     * @param string $header The HTTP "Accept" header to parse
     *
     * @return array Sorted list of "accept" options
     */
    protected function sortAccept(string $header): array
    {
        $matches = [];

        foreach (explode(',', $header) as $option) {
            $option = array_map(trim(...), explode(';', $option));
            $l = strtolower($option[0]);

            if (isset($option[1])) {
                $q = (float) str_replace('q=', '', $option[1]);
            } else {
                $q = null;

                // Assign default low weight for generic values
                if ($l === '*/*') {
                    $q = 0.01;
                } elseif (str_ends_with($l, '*')) {
                    $q = 0.02;
                }
            }

            // Unweighted values, get high weight by their position in the
            // list
            $matches[$l] = $q ?? 1000 - count($matches);
        }

        foreach ($matches as $k => $v) {
            if (strlen($k) > 2) {
                $gen_lang = substr($k, 0, 2);

                $matches[$gen_lang] ??= $v - 0.001;
            }
        }

        arsort($matches, SORT_NUMERIC);

        return $matches;
    }
}
