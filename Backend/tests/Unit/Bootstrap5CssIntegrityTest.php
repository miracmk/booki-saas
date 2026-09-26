<?php declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Quality Gate: Prevents deprecated Bootstrap 3/4 classes from breaking visuals in Bootstrap 5.
 *
 * In Bootstrap 5, classes like `badge-success` or `btn-default` render transparent or unstyled.
 * This test statically scans all active view templates and page scripts to enforce modern styling.
 */
class Bootstrap5CssIntegrityTest extends TestCase
{
    private const FORBIDDEN_CLASSES = [
        'badge-success' => 'bg-success-subtle text-success border border-success-subtle or text-bg-success',
        'badge-danger' => 'bg-danger-subtle text-danger border border-danger-subtle or text-bg-danger',
        'badge-warning' => 'bg-warning-subtle text-warning border border-warning-subtle or text-bg-warning',
        'badge-info' => 'bg-info-subtle text-info border border-info-subtle or text-bg-info',
        'badge-primary' => 'bg-primary-subtle text-primary border border-primary-subtle or text-bg-primary',
        'badge-default' => 'bg-secondary-subtle text-secondary or text-bg-secondary',
        'btn-default' => 'btn-light or btn-outline-secondary',
    ];

    /**
     * Ensure no active view templates use deprecated Bootstrap 3/4 styling classes.
     */
    public function testNoDeprecatedBootstrapClassesInViews(): void
    {
        $baseDir = dirname(__DIR__, 2);
        $viewsDir = $baseDir . '/application/views/pages';

        $files = glob($viewsDir . '/*.php');
        $this->assertNotEmpty($files, 'Views directory must not be empty.');

        $violations = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);

            foreach ($lines as $lineNum => $line) {
                foreach (self::FORBIDDEN_CLASSES as $forbidden => $recommendation) {
                    // Match class attribute or class tokens
                    if (preg_match("/\b" . preg_quote($forbidden, '/') . "\b/", $line)) {
                        $violations[] = sprintf(
                            '%s:%d uses deprecated "%s" (Use: %s)',
                            basename($file),
                            $lineNum + 1,
                            $forbidden,
                            $recommendation
                        );
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Found deprecated Bootstrap 3/4 classes in views:\n" . implode("\n", $violations)
        );
    }

    /**
     * Ensure no page JavaScript files inject deprecated Bootstrap 3/4 styling classes into dynamic DOM.
     */
    public function testNoDeprecatedBootstrapClassesInPageScripts(): void
    {
        $baseDir = dirname(__DIR__, 2);
        $jsDir = $baseDir . '/assets/js/pages';

        if (!is_dir($jsDir)) {
            $this->markTestSkipped('No assets/js/pages directory found.');
            return;
        }

        $files = glob($jsDir . '/*.js');
        $violations = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);

            foreach ($lines as $lineNum => $line) {
                foreach (self::FORBIDDEN_CLASSES as $forbidden => $recommendation) {
                    if (preg_match("/\b" . preg_quote($forbidden, '/') . "\b/", $line)) {
                        $violations[] = sprintf(
                            '%s:%d uses deprecated "%s" (Use: %s)',
                            basename($file),
                            $lineNum + 1,
                            $forbidden,
                            $recommendation
                        );
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Found deprecated Bootstrap 3/4 classes in page scripts:\n" . implode("\n", $violations)
        );
    }
}

