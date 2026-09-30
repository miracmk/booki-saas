<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * BooKi lang.
 *
 * Language integrity fallback: whenever a language file is loaded for a
 * non-English idiom, any translation key that the active language dictionary
 * does not define is filled from the English master file of the SAME language
 * file. This guarantees that every user-facing dictionary is 100% complete
 * (no raw key tokens leaking into the UI, both server-side `lang()` calls and
 * the `window.lang` JS dictionary built from `$this->lang->language`), even
 * while the per-language dictionaries are still being translated.
 *
 * @property App_Benchmark $benchmark
 * @property App_Cache $cache
 * @property App_Calendar $calendar
 * @property App_Config $config
 * @property App_DB_forge $dbforge
 * @property App_DB_query_builder $db
 * @property App_DB_utility $dbutil
 * @property App_Email $email
 * @property App_Encrypt $encrypt
 * @property App_Encryption $encryption
 * @property App_Exceptions $exceptions
 * @property App_Hooks $hooks
 * @property App_Input $input
 * @property App_Lang $lang
 * @property App_Loader $load
 * @property App_Log $log
 * @property App_Migration $migration
 * @property App_Output $output
 * @property App_Profiler $profiler
 * @property App_Router $router
 * @property App_Security $security
 * @property App_Session $session
 * @property App_Upload $upload
 * @property App_URI $uri
 */
class App_Lang extends CI_Lang
{
    /**
     * Track which lang files already received the English fallback for which
     * idiom, so repeated load() calls stay cheap and idempotent.
     *
     * @var array
     */
    protected array $english_fallback_applied = [];

    /**
     * Load a language file and top it up with the English master keys.
     *
     * @param mixed $langfile Language file name (string or list of names)
     * @param string $idiom Language name (english, etc.)
     * @param bool $return Whether to return the loaded array of translations
     * @param bool $add_suffix Whether to add suffix to $langfile
     * @param string $alt_path Alternative path to look for the language file
     *
     * @return mixed Same contract as CI_Lang::load()
     */
    #[\ReturnTypeWillChange]
    public function load($langfile, $idiom = '', $return = FALSE, $add_suffix = TRUE, $alt_path = '')
    {
        $result = parent::load($langfile, $idiom, $return, $add_suffix, $alt_path);

        if ($return === FALSE) {
            foreach ((array) $langfile as $single_langfile) {
                $this->apply_english_fallback((string) $single_langfile, $idiom, $add_suffix, $alt_path);
            }
        }

        return $result;
    }

    /**
     * Fill the missing keys of the active dictionary from the English master
     * file of the same language file.
     *
     * @param string $langfile Language file name (with or without suffix/extension)
     * @param string $idiom Language name (empty string resolves to the active idiom)
     * @param bool $add_suffix Whether to add the "_lang" suffix
     * @param string $alt_path Alternative path to look for the language file
     *
     * @return void
     */
    protected function apply_english_fallback(string $langfile, string $idiom = '', bool $add_suffix = TRUE, string $alt_path = ''): void
    {
        $langfile = str_replace('.php', '', $langfile);

        if ($add_suffix === TRUE) {
            $langfile = preg_replace('/_lang$/', '', $langfile) . '_lang';
        }

        $langfile .= '.php';

        // Resolve the active idiom exactly like CI_Lang::load() does.
        if (empty($idiom) OR ! preg_match('/^[a-z_-]+$/i', $idiom)) {
            $config =& get_config();
            $idiom = empty($config['language']) ? 'english' : $config['language'];
        }

        // English is the fallback source itself - nothing to top up, and each
        // (file, idiom) pair must only ever be topped up once.
        if ($idiom === 'english'
            || isset($this->english_fallback_applied[$langfile . '|' . $idiom])) {
            return;
        }

        $english_lines = $this->load_english_master($langfile, $alt_path);

        if (empty($english_lines)) {
            return;
        }

        $this->english_fallback_applied[$langfile . '|' . $idiom] = TRUE;

        foreach ($english_lines as $key => $value) {
            // Only fill the gaps - an existing translation always wins. An empty
            // string counts as a gap too: a blank label is just as broken as a
            // raw key token, and an untranslated (empty) upstream entry would
            // otherwise render as nothing at all.
            if (!isset($this->language[$key]) || $this->language[$key] === '') {
                $this->language[$key] = $value;
            }
        }
    }

    /**
     * Load the English master dictionary for the given language file, using
     * the same resolution order as CI_Lang::load() (alt path > package paths
     * aka application > system) so the fallback matches what the English UI
     * actually renders.
     *
     * @param string $langfile Resolved language file name (e.g. "translations_lang.php")
     * @param string $alt_path Alternative path to look for the language file
     *
     * @return array
     */
    protected function load_english_master(string $langfile, string $alt_path = ''): array
    {
        $candidates = [];

        if ($alt_path !== '') {
            $candidates[] = $alt_path . 'language/english/' . $langfile;
        }

        foreach (get_instance()->load->get_package_paths(TRUE) as $package_path) {
            $candidates[] = $package_path . 'language/english/' . $langfile;
        }

        $candidates[] = BASEPATH . 'language/english/' . $langfile;

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                $lang = [];

                include $candidate;

                return (isset($lang) && is_array($lang)) ? $lang : [];
            }
        }

        return [];
    }
}

if (!class_exists('EA_Lang', false)) {
    class_alias(App_Lang::class, 'EA_Lang');
}
