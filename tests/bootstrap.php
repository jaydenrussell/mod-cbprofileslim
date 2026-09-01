<?php
/**
 * Bootstrap for PHPUnit sanitizer tests.
 * Stubs the Joomla classes helper.php imports so it can be unit-tested
 * outside a full Joomla install. The sanitizers under test do NOT call
 * Joomla at runtime, but the file-level `use` imports must resolve.
 *
 * NOTE: all code here lives inside namespace blocks. The actual
 * require_once of helper.php happens in SanitizerTest.php at global scope.
 */

namespace Joomla\CMS\Log {
    class Log
    {
        public static function add($msg, $level = null, $category = null) { /* no-op in tests */ }
    }
}

namespace Joomla\CMS\Uri {
    class Uri
    {
        public static function root()
        {
            return 'https://simcoecurlingclub.ca/';
        }
    }
}

namespace {
    // helper.php guards itself with `defined('_JEXEC') or die;`, exactly as in
    // a live Joomla request. Tests must define it so the helper actually loads
    // instead of silently terminating the process — which previously made the
    // suite "pass" vacuously without running any assertion.
    define('_JEXEC', 1);
}
