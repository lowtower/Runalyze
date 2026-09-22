<?php

namespace Runalyze;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LanguageTest extends TestCase
{
    private const LOCALE_CATEGORIES = [
        'LC_COLLATE' => LC_COLLATE,
        'LC_CTYPE' => LC_CTYPE,
        'LC_MONETARY' => LC_MONETARY,
        'LC_NUMERIC' => LC_NUMERIC,
        'LC_TIME' => LC_TIME,
        'LC_MESSAGES' => LC_MESSAGES,
    ];

    private array $originalGet;

    private array $originalCookie;

    private array $originalServer;

    private ?array $originalAvailableLanguages;

    private string $originalCurrentLang;

    protected function setUp(): void
    {
        $this->originalGet = $_GET;
        $this->originalCookie = $_COOKIE;
        $this->originalServer = $_SERVER;

        $this->originalAvailableLanguages = $this->getStaticProperty('AVAILABLE_LANGUAGES');
        $this->originalCurrentLang = $this->getStaticProperty('CURRENT_LANG');
    }

    protected function tearDown(): void
    {
        $_GET = $this->originalGet;
        $_COOKIE = $this->originalCookie;
        $_SERVER = $this->originalServer;

        $this->setStaticProperty('AVAILABLE_LANGUAGES', $this->originalAvailableLanguages);
        $this->setStaticProperty('CURRENT_LANG', $this->originalCurrentLang);
    }

    /**
     * @return array<string, string>
     */
    private function saveLocale(): array
    {
        $saved = [];

        foreach (self::LOCALE_CATEGORIES as $name => $constant) {
            $saved[$name] = setlocale($constant, '0');
        }

        return $saved;
    }

    /**
     * @param array<string, string> $saved
     */
    private function restoreLocale(array $saved): void
    {
        foreach ($saved as $name => $value) {
            if ($value !== false) {
                setlocale(self::LOCALE_CATEGORIES[$name], $value);
            }
        }
    }

    private function createInstance(): Language
    {
        $reflection = new \ReflectionClass(Language::class);

        return $reflection->newInstanceWithoutConstructor();
    }

    private function invokeProtected(object $object, string $method, array $args = []): mixed
    {
        $reflection = new \ReflectionObject($object);
        $refMethod = $reflection->getMethod($method);

        return $refMethod->invokeArgs($object, $args);
    }

    private function getStaticProperty(string $name): mixed
    {
        $reflection = new \ReflectionClass(Language::class);
        $prop = $reflection->getProperty($name);

        return $prop->getValue();
    }

    private function setStaticProperty(string $name, mixed $value): void
    {
        $reflection = new \ReflectionClass(Language::class);
        $prop = $reflection->getProperty($name);
        $prop->setValue(null, $value);
    }

    #[DataProvider('provideSortAcceptScenarios')]
    public function testSortAccept(string $header, array $expected): void
    {
        $instance = $this->createInstance();

        $result = $this->invokeProtected($instance, 'sortAccept', [$header]);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function provideSortAcceptScenarios(): array
    {
        return [
            'single unweighted value' => [
                'de',
                [
                    'de' => 1000,
                ],
            ],
            'regional variant gets an implicit generic fallback entry' => [
                'de-DE',
                [
                    'de-de' => 1000,
                    'de' => 999.999,
                ],
            ],
            'explicit generic entry takes precedence over implicit fallback' => [
                'de-DE,de;q=0.9,en;q=0.8',
                [
                    'de-de' => 1000,
                    'de' => 0.9,
                    'en' => 0.8,
                ],
            ],
            'explicitly weighted values are sorted purely by their q value' => [
                'en;q=0.5,de;q=0.8',
                [
                    'de' => 0.8,
                    'en' => 0.5,
                ],
            ],
        ];
    }

    public function testMatchAcceptReturnsHighestWeightedSupportedMatch(): void
    {
        $instance = $this->createInstance();

        $result = $this->invokeProtected($instance, 'matchAccept', [
            'de-DE,de;q=0.9,en;q=0.8',
            [
                'de' => 'de',
                'en' => 'en',
            ],
        ]);

        $this->assertSame('de', $result);
    }

    public function testMatchAcceptSkipsUnsupportedHigherWeightedValues(): void
    {
        $instance = $this->createInstance();

        $result = $this->invokeProtected($instance, 'matchAccept', [
            'fr,de;q=0.5',
            [
                'de' => 'de',
            ],
        ]);

        $this->assertSame('de', $result);
    }

    public function testMatchAcceptReturnsNullWhenNothingMatches(): void
    {
        $instance = $this->createInstance();

        $result = $this->invokeProtected($instance, 'matchAccept', [
            'fr,es',
            [
                'de' => 'de',
            ],
        ]);

        $this->assertNull($result);
    }

    public function testMatchAcceptFallsBackToFirstSupportedValueForWildcard(): void
    {
        $instance = $this->createInstance();

        $result = $this->invokeProtected($instance, 'matchAccept', [
            'fr,*;q=0.1',
            [
                'de' => 'de',
                'en' => 'en',
            ],
        ]);

        $this->assertSame('de', $result);
    }

    public function testGetBrowserLanguageReturnsDefaultWhenNoLanguagesAreSupported(): void
    {
        $instance = $this->createInstance();
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de,en';

        $result = $this->invokeProtected($instance, 'getBrowserLanguage', [[
            'de' => false,
            'en' => false,
        ], 'en']);

        $this->assertSame('en', $result);
    }

    public function testGetBrowserLanguageReturnsDefaultWhenHeaderIsMissing(): void
    {
        $instance = $this->createInstance();
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        $result = $this->invokeProtected($instance, 'getBrowserLanguage', [[
            'de' => true,
        ], 'en']);

        $this->assertSame('en', $result);
    }

    public function testGetBrowserLanguageMatchesSupportedHeaderValue(): void
    {
        $instance = $this->createInstance();
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE,de;q=0.9,en;q=0.8';

        $result = $this->invokeProtected($instance, 'getBrowserLanguage', [[
            'de' => true,
            'en' => true,
        ], 'en']);

        $this->assertSame('de', $result);
    }

    public function testGetBrowserLanguageMatchingIsCaseInsensitive(): void
    {
        $instance = $this->createInstance();
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de';

        $result = $this->invokeProtected($instance, 'getBrowserLanguage', [[
            'De' => true,
        ], 'en']);

        $this->assertSame('De', $result);
    }

    public function testGuessLanguageReturnsGetParameterWhenSupported(): void
    {
        $this->setStaticProperty('AVAILABLE_LANGUAGES', [
            'de' => ['Deutsch', 'de_DE'],
            'en' => ['English', 'en_US'],
        ]);
        $_GET[Language::GET_KEY] = 'de';

        $instance = $this->createInstance();
        $result = $this->invokeProtected($instance, 'guessLanguage');

        $this->assertSame('de', $result);
    }

    public function testGuessLanguageIgnoresUnsupportedGetParameterAndFallsBackToCookie(): void
    {
        $this->setStaticProperty('AVAILABLE_LANGUAGES', [
            'de' => ['Deutsch', 'de_DE'],
        ]);
        $_GET[Language::GET_KEY] = 'xx-not-supported';
        $_COOKIE[Language::COOKIE_KEY] = 'de';

        $instance = $this->createInstance();
        $result = $this->invokeProtected($instance, 'guessLanguage');

        $this->assertSame('de', $result);
    }

    public function testGuessLanguageFallsBackToCookieWhenNoGetParameterPresent(): void
    {
        $this->setStaticProperty('AVAILABLE_LANGUAGES', [
            'de' => ['Deutsch', 'de_DE'],
        ]);
        unset($_GET[Language::GET_KEY]);
        $_COOKIE[Language::COOKIE_KEY] = 'de';

        $instance = $this->createInstance();
        $result = $this->invokeProtected($instance, 'guessLanguage');

        $this->assertSame('de', $result);
    }

    public function testGuessLanguageFallsBackToBrowserHeaderWhenNoGetOrCookiePresent(): void
    {
        $this->setStaticProperty('AVAILABLE_LANGUAGES', [
            'de' => ['Deutsch', 'de_DE'],
        ]);
        unset($_GET[Language::GET_KEY]);
        unset($_COOKIE[Language::COOKIE_KEY]);
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de';

        $instance = $this->createInstance();
        $result = $this->invokeProtected($instance, 'guessLanguage');

        $this->assertSame('de', $result);
    }

    public function testGetChoicesMapsDisplayNameToShortCode(): void
    {
        $this->setStaticProperty('AVAILABLE_LANGUAGES', [
            'de' => ['Deutsch', 'de_DE'],
            'en' => ['English', 'en_US'],
        ]);

        $result = Language::getChoices();

        $this->assertSame([
            'Deutsch' => 'de',
            'English' => 'en',
        ], $result);
    }

    public function testAvailableLanguagesReturnsControlledStaticStateWithoutTouchingRealFile(): void
    {
        $expected = [
            'de' => ['Deutsch', 'de_DE'],
        ];
        $this->setStaticProperty('AVAILABLE_LANGUAGES', $expected);

        $this->assertSame($expected, Language::availableLanguages());
    }

    public function testAvailableLanguagesParsesRealConfigFileIntoExpectedShape(): void
    {
        $this->setStaticProperty('AVAILABLE_LANGUAGES', null);

        $result = Language::availableLanguages();

        $this->assertNotEmpty($result, 'Expected config/packages/languages.yaml to define at least one language.');

        foreach ($result as $shortCode => $definition) {
            $this->assertIsString($shortCode);
            $this->assertCount(2, $definition, 'Each language entry must be a [name, locale] pair.');
            $this->assertIsString($definition[0]);
            $this->assertIsString($definition[1]);
        }
    }

    public function testSetLanguageReturnsFalseForUnsupportedLanguage(): void
    {
        $this->setStaticProperty('AVAILABLE_LANGUAGES', [
            'de' => ['Deutsch', 'de_DE'],
        ]);

        $result = Language::setLanguage('xx-not-supported');

        $this->assertFalse($result);
    }

    public function testSetLanguageReturnsTrueAndUpdatesCurrentLanguageForSupportedLanguage(): void
    {
        $this->setStaticProperty('AVAILABLE_LANGUAGES', [
            'de' => ['Deutsch', 'de_DE.UTF-8'],
        ]);

        $originalLang = getenv('LANG');
        $savedLocale = $this->saveLocale();

        try {
            $result = Language::setLanguage('de');

            $this->assertTrue($result);
            $this->assertSame('de', Language::getCurrentLanguage());
        } finally {
            if ($originalLang !== false) {
                putenv('LANG=' . $originalLang);
            }

            $this->restoreLocale($savedLocale);
        }
    }

    public function testSetLanguageWithoutOverwriteRejectsChangeWhenGetParameterIsAlreadySupported(): void
    {
        $this->setStaticProperty('AVAILABLE_LANGUAGES', [
            'de' => ['Deutsch', 'de_DE'],
            'en' => ['English', 'en_US'],
        ]);
        $_GET[Language::GET_KEY] = 'en';

        $result = Language::setLanguage('de', false);

        $this->assertFalse($result);
    }
}
