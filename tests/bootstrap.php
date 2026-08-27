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
