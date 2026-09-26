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
}

