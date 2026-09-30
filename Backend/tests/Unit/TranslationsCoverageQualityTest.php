<?php declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Quality Gate: Ensures 100% translation coverage and prevents raw uppercase fallback leaks.
 */
class TranslationsCoverageQualityTest extends TestCase
{
    private array $trLang = [];
    private array $enLang = [];

    /**
     * Languages whose dictionary natively defines every English master key.
     * Membership here is a hard regression gate: once a language is complete,
     * it must stay complete.
     *
     * @var string[]
     */
    private const NATIVELY_COMPLETE_LANGUAGES = ['english', 'turkish', 'german', 'french', 'russian', 'arabic'];

    /**
     * Highest number of English master keys each dictionary is still allowed to
     * be missing. This is a ratchet: adding translations lowers the real number
     * (still passing) while deleting/renaming a key raises it and fails the
     * build, so translated work can never silently regress.
     *
     * @var array<string,int>
     */
    private const GAP_BASELINE = [
        'albanian' => 397, 'bosnian' => 397, 'bulgarian' => 397,
        'catalan' => 397, 'chinese' => 397, 'croatian' => 397, 'czech' => 397,
        'danish' => 397, 'dutch' => 397, 'estonian' => 397, 'finnish' => 397,
        'greek' => 397, 'hebrew' => 397, 'hindi' => 397, 'hungarian' => 397,
        'italian' => 397, 'japanese' => 397, 'latvian' => 397, 'lithuanian' => 397,
        'luxembourgish' => 397, 'marathi' => 397, 'norwegian' => 397, 'persian' => 397,
        'polish' => 397, 'portuguese' => 397, 'portuguese-br' => 397, 'romanian' => 397,
        'serbian' => 397, 'slovak' => 397, 'slovenian' => 397, 'spanish' => 397,
        'swedish' => 397, 'thai' => 397, 'traditional-chinese' => 397, 'ukrainian' => 397,
    ];

    /**
     * Every language directory shipped with the application.
     *
     * @return string[]
     */
    private function languageDirectories(): array
    {
        $dir = dirname(__DIR__, 2) . '/application/language';
        $languages = [];

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..' && is_dir($dir . '/' . $entry)) {
                $languages[] = $entry;
            }
        }

        sort($languages);

        return $languages;
    }

    /**
     * Load a single language dictionary.
     *
     * @return array<string,string>
     */
    private function dictionary(string $language): array
    {
        if (!defined('BASEPATH')) {
            define('BASEPATH', '1');
        }

        $lang = [];
        $path = dirname(__DIR__, 2) . '/application/language/' . $language . '/translations_lang.php';

        if (is_file($path)) {
            include $path;
        }

        return $lang;
    }

    /**
     * English master keys a dictionary does not define natively.
     *
     * @return string[]
     */
    private function missingKeys(string $language): array
    {
        return array_values(array_diff(array_keys($this->enLang), array_keys($this->dictionary($language))));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $loadLang = function (string $filePath): array {
            $lang = [];
            if (file_exists($filePath)) {
                // Mock BASEPATH if not defined so translation file doesn't exit
                if (!defined('BASEPATH')) {
                    define('BASEPATH', '1');
                }
                include $filePath;
            }
            return $lang;
        };

        $baseDir = dirname(__DIR__, 2);
        $this->trLang = $loadLang($baseDir . '/application/language/turkish/translations_lang.php');
        $this->enLang = $loadLang($baseDir . '/application/language/english/translations_lang.php');
    }

    /**
     * Test that translation dictionaries are populated and valid arrays.
     */
    public function testTranslationDictionariesArePopulated(): void
    {
        $this->assertGreaterThan(300, count($this->trLang), 'Turkish translation dictionary must contain at least 300 keys.');
        $this->assertGreaterThan(300, count($this->enLang), 'English translation dictionary must contain at least 300 keys.');
    }

    /**
     * Test that all lang('...') keys used in application/views/pages/ exist in Turkish translations.
     */
    public function testAllViewLangKeysExistInTurkish(): void
    {
        $baseDir = dirname(__DIR__, 2);
        $viewsDir = $baseDir . '/application/views/pages';

        $phpFiles = glob($viewsDir . '/*.php');
        $this->assertNotEmpty($phpFiles, 'Views directory must contain PHP templates.');

        $missingKeys = [];

        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            // Match lang('key') or lang("key")
            if (preg_match_all("/\blang\(\s*['\"]([a-zA-Z0-9_\-]+)['\"]\s*\)/", $content, $matches)) {
                $keys = array_unique($matches[1]);
                foreach ($keys as $key) {
                    if (!array_key_exists($key, $this->trLang)) {
                        $missingKeys[] = basename($file) . " -> " . $key;
                    }
                }
            }
        }

        $this->assertEmpty(
            $missingKeys,
            "Found missing Turkish translation keys used in views:\n" . implode("\n", $missingKeys)
        );
    }

    /**
     * Test that all lang('...') keys used in application/views/pages/ exist in English translations.
     */
    public function testAllViewLangKeysExistInEnglish(): void
    {
        $baseDir = dirname(__DIR__, 2);
        $viewsDir = $baseDir . '/application/views/pages';

        $phpFiles = glob($viewsDir . '/*.php');
        $missingKeys = [];

        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            if (preg_match_all("/\blang\(\s*['\"]([a-zA-Z0-9_\-]+)['\"]\s*\)/", $content, $matches)) {
                $keys = array_unique($matches[1]);
                foreach ($keys as $key) {
                    if (!array_key_exists($key, $this->enLang)) {
                        $missingKeys[] = basename($file) . " -> " . $key;
                    }
                }
            }
        }

        $this->assertEmpty(
            $missingKeys,
            "Found missing English translation keys used in views:\n" . implode("\n", $missingKeys)
        );
    }

    /**
     * Quality Gate: Prevent raw unlocalized uppercase tokens from leaking into table headers.
     * Tokens like <th>TOTAL_SESSIONS</th> or <th>EXPİRES</th> indicate an untranslated raw string.
     */
    public function testNoRawUppercaseKeysInTableHeaders(): void
    {
        $baseDir = dirname(__DIR__, 2);
        $viewsDir = $baseDir . '/application/views/pages';

        $phpFiles = glob($viewsDir . '/*.php');
        $violations = [];

        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            // Match <th>TOTAL_SESSIONS</th> or similar without PHP tags
            if (preg_match_all("/<th[^>]*>\s*([A-Z_]{5,})\s*<\/th>/", $content, $matches)) {
                foreach ($matches[1] as $token) {
                    $violations[] = basename($file) . " has raw header: " . $token;
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Found unlocalized raw uppercase headers in views:\n" . implode("\n", $violations)
        );
    }

    /**
     * Every shipped language must expose a readable translations dictionary.
     */
    public function testEveryLanguageShipsATranslationsDictionary(): void
    {
        $languages = $this->languageDirectories();

        $this->assertCount(41, $languages, 'All 41 supported languages must ship a language directory.');

        foreach ($languages as $language) {
            $this->assertFileExists(
                dirname(__DIR__, 2) . '/application/language/' . $language . '/translations_lang.php',
                "Language '{$language}' is missing translations_lang.php."
            );
        }
    }

    /**
     * The English master is the single source of truth: no dictionary may
     * define a key that English does not have. Such keys are dead weight and
     * usually a typo that silently never renders.
     */
    public function testNoDictionaryDefinesKeysOutsideTheEnglishMaster(): void
    {
        $violations = [];

        foreach ($this->languageDirectories() as $language) {
            $extra = array_diff(array_keys($this->dictionary($language)), array_keys($this->enLang));

            foreach ($extra as $key) {
                $violations[] = $language . ' -> ' . $key;
            }
        }

        $this->assertEmpty(
            $violations,
            "Dictionaries define keys absent from the English master:\n" . implode("\n", $violations)
        );
    }

    /**
     * Regression guard for the reported Salon Flora leak.
     *
     * `session`, `station`, `real_start` and `real_end` are consumed
     * DYNAMICALLY from JS: assets/js/utils/calendar_event_popover.js calls
     * createPopoverRow('station'|'real_start'|'real_end'), which resolves the
     * label through lang(labelKey). They used to exist only in the Turkish
     * dictionary, so every other idiom (including English) rendered the raw key
     * token instead of a label.
     */
    public function testSalonFloraSessionStationKeysExistInEnglishMaster(): void
    {
        foreach (['session', 'station', 'real_start', 'real_end'] as $key) {
            $this->assertArrayHasKey(
                $key,
                $this->enLang,
                "The English master must define '{$key}' because the calendar popover JS requires it."
            );
            $this->assertNotSame($key, $this->enLang[$key], "The English master must really translate '{$key}'.");
        }
    }

    /**
     * An empty translation renders as a blank label, which is as broken as a
     * raw key token.
     */
    public function testDictionaryValuesAreNonEmptyStrings(): void
    {
        $violations = [];

        foreach ($this->languageDirectories() as $language) {
            foreach ($this->dictionary($language) as $key => $value) {
                if (!is_string($value) || trim($value) === '') {
                    $violations[] = $language . ' -> ' . $key;
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Dictionaries contain empty translation values:\n" . implode("\n", $violations)
        );
    }

    /**
     * Ratchet gate: a language declared natively complete must stay complete,
     * and no dictionary may regress past its recorded gap baseline. Adding
     * translations keeps lowering the real number (test stays green) while
     * deleting or renaming keys fails the build.
     */
    public function testNativeTranslationCoverageOnlyImproves(): void
    {
        foreach (self::NATIVELY_COMPLETE_LANGUAGES as $language) {
            $this->assertSame(
                [],
                $this->missingKeys($language),
                "Language '{$language}' is declared natively complete but its dictionary lost keys."
            );
        }

        $this->assertSame(
            [],
            array_values(array_diff(
                $this->languageDirectories(),
                array_merge(self::NATIVELY_COMPLETE_LANGUAGES, array_keys(self::GAP_BASELINE))
            )),
            'Every shipped language must be tracked either as natively complete or in GAP_BASELINE.'
        );

        $regressions = [];

        foreach (self::GAP_BASELINE as $language => $allowedGap) {
            $actualGap = count($this->missingKeys($language));

            if ($actualGap > $allowedGap) {
                $regressions[] = "{$language}: {$actualGap} keys missing (baseline {$allowedGap})";
            }
        }

        $this->assertEmpty(
            $regressions,
            "Translation coverage regressed:\n" . implode("\n", $regressions)
        );
    }

    /**
     * The App_Lang English fallback layer is what keeps an un-translated key
     * from ever reaching the UI. It reaches the browser through two paths that
     * both have to stay wired:
     *
     *   1. server side - lang() reads the topped-up $this->lang->language
     *   2. client side - App_Controller::load_common_html_vars() publishes
     *      `'language' => $this->lang->language`, and
     *      views/components/js_lang_script.php turns `html_vars('language')`
     *      into the window.lang() dictionary.
     *
     * Removing either link brings back the raw-key-token bug.
     */
    public function testEnglishFallbackLayerIsWiredForServerAndJsDictionaries(): void
    {
        $baseDir = dirname(__DIR__, 2);
        $appLangPath = $baseDir . '/application/core/App_Lang.php';
        $controllerPath = $baseDir . '/application/core/App_Controller.php';
        $jsDictionaryPath = $baseDir . '/application/views/components/js_lang_script.php';

        $this->assertFileExists($appLangPath);
        $this->assertFileExists($controllerPath);
        $this->assertFileExists($jsDictionaryPath, 'The window.lang JS dictionary script must exist.');

        $appLang = (string) file_get_contents($appLangPath);

        $this->assertMatchesRegularExpression(
            '/function\s+load\s*\(/',
            $appLang,
            'App_Lang must override CI_Lang::load() to apply the English fallback.'
        );
        $this->assertStringContainsString('apply_english_fallback', $appLang);

        $controller = (string) file_get_contents($controllerPath);
        $this->assertMatchesRegularExpression(
            "/'language'\s*=>\s*\\\$this->lang->language/",
            $controller,
            'App_Controller must publish the fully topped-up runtime dictionary as the "language" html var.'
        );

        $jsDictionary = (string) file_get_contents($jsDictionaryPath);
        $this->assertStringContainsString(
            "html_vars('language')",
            $jsDictionary,
            'window.lang must be generated from the "language" html var so the fallback reaches the JS layer.'
        );
    }

    /**
     * The sync tool is what turns "safe but English" fallbacks into real
     * translations, so it must always ship with the codebase.
     */
    public function testTranslationSyncToolIsAvailable(): void
    {
        $this->assertFileExists(
            dirname(__DIR__, 2) . '/scripts/sync_translations.php',
            'scripts/sync_translations.php is required to keep every dictionary aligned with the English master.'
        );
    }
}

