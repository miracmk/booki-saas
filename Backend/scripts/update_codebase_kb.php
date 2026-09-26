#!/usr/bin/env php
<?php
/**
 * BooKi Codebase Feature Scanner & KB Generator.
 *
 * Runs automatically on git post-merge/post-commit, or manually via CLI.
 * Scans all controllers, models, migrations and blueprints to generate an up-to-date
 * feature catalog for the Platform AI Sales & Support Consultant.
 */

define('BASEPATH', __DIR__ . '/../system/');
define('APPPATH', __DIR__ . '/../application/');
define('FCPATH', __DIR__ . '/../');

require_once APPPATH . 'libraries/Platform_knowledge_base.php';

// Mock minimal CI environment for standalone CLI execution
if (!function_exists('get_instance')) {
    class DummyCI {
        public $db;
    }
    function &get_instance() {
        static $ci;
        if (!$ci) $ci = new DummyCI();
        return $ci;
    }
}

echo "=== BooKi Codebase Feature Scanner ===\n";
$kb = new Platform_knowledge_base();
$features = $kb->get_codebase_features(true);

echo "Git Commit: " . ($features['git_commit'] ?? 'unknown') . "\n";
echo "Scanned Controllers: " . ($features['total_controllers'] ?? 0) . "\n";
echo "Scanned Models: " . ($features['total_models'] ?? 0) . "\n";
echo "Scanned Migrations: " . ($features['total_migrations'] ?? 0) . "\n";
echo "Modules Discovered: " . count($features['modules'] ?? []) . "\n";
echo "Knowledge Base updated at: " . FCPATH . "storage/kb/codebase_features.json\n";
echo "=== Done ===\n";
