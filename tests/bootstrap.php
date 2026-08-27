<?php
/**
 * Bootstrap for PHPUnit sanitizer tests.
 * Stubs the Joomla classes helper.php imports so it can be unit-tested
 * outside a full Joomla install. The sanitizers under test do NOT call
 * Joomla at runtime, but the file-level `use` imports must resolve.
 */

namespace Joomla\CMS\Log {
    class Log
    {
        public static function add($msg, $level = null, $category = null) { /* no-op in tests */ }
    }
}

namespace Joomla\CMS\Factory {
    // not used by sanitizers; declared only so autoload doesn't fail
}

namespace Joomla\CMS\Uri {
    // intentionally empty; Uri import was removed from helper.php
}

// Load the helper (defines ModCbProfileSlimHelper in global namespace).
require_once __DIR__ . '/../helper.php';
