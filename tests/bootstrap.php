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

        public static function base()
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

    // Some helper methods reference JPATH_* constants; define them so the
    // code paths behave as they would inside a live Joomla request.
    if (!defined('JPATH_ADMINISTRATOR')) {
        define('JPATH_ADMINISTRATOR', __DIR__ . '/admin');
    }
    if (!defined('JPATH_SITE')) {
        define('JPATH_SITE', __DIR__);
    }

    // Minimal, test-only stand-ins for the global Joomla classes used by
    // script.php's migration (ModProfileslimInstallerScript). They emulate just
    // enough of #__modules / #__modules_menu / #__extensions to verify the
    // non-breaking rename migration in ScriptTest.
    class FakeJoomlaDbDriver
    {
        public $modules = array();      // id => assoc row (module element keyed lookup below)
        public $modulesById = array();  // id => assoc row
        public $menuAssign = array();   // list of array('moduleid'=>, 'menuid'=>)
        public $extensions = array();   // list of array('element'=>, 'type'=>)
        public $updateSites = array();  // list of array('update_site_id'=>, 'location'=>, 'enabled'=>)
        public $updateSiteExt = array(); // list of array('update_site_id'=>, 'extension_id'=>)
        public $lastQuery = null;
        public $executed = array();

        public function getQuery($new = false)
        {
            return new FakeJoomlaQuery($this);
        }

        public function quote($value)
        {
            return "'" . (string) $value . "'";
        }

        public function q($value)
        {
            return $this->quote($value);
        }

        public function quoteName($name)
        {
            return preg_replace('/^([^`]+)$/', '`$1`', (string) $name);
        }

        public function setQuery($query)
        {
            $this->lastQuery = $query;
            return $this;
        }

        public function loadAssoc()
        {
            if (!$this->lastQuery) {
                return false;
            }
            $rows = $this->lastQuery->matches();
            return (count($rows) > 0) ? $rows[0] : false;
        }

        public function loadAssocList($key = null)
        {
            if (!$this->lastQuery) {
                return array();
            }
            return $this->lastQuery->matches($key);
        }

        public function execute()
        {
            if (!$this->lastQuery) {
                return false;
            }
            $this->lastQuery->run();
            return true;
        }
    }

    class FakeJoomlaQuery
    {
        public $type = '';
        public $table = '';
        public $wheres = array();
        public $sets = array();
        public $limit = null;

        /** @var FakeJoomlaDbDriver */
        private $db;

        public function __construct($db)
        {
            $this->db = $db;
        }

        public function select($columns = null)
        {
            $this->type = 'select';
            return $this;
        }

        public function update($table)
        {
            $this->type = 'update';
            $this->table = $this->db->quoteName($table);
            return $this;
        }

        public function delete($table)
        {
            $this->type = 'delete';
            $this->table = $this->db->quoteName($table);
            return $this;
        }

        public function from($table)
        {
            $this->table = $this->db->quoteName($table);
            return $this;
        }

        public function where($condition, $andOr = null)
        {
            $this->wheres[] = (string) $condition;
            return $this;
        }

        public function set($set)
        {
            foreach ((array) $set as $s) {
                $this->sets[] = (string) $s;
            }
            return $this;
        }

        public function setLimit($limit)
        {
            $this->limit = (int) $limit;
            return $this;
        }

        public function order($order)
        {
            return $this;
        }

        private static function stripQuotes($value)
        {
            $value = trim((string) $value);
            $value = preg_replace('/^`(.*)`$/', '$1', $value);
            if (strlen($value) >= 2 && $value[0] === "'" && substr($value, -1) === "'") {
                $value = substr($value, 1, -1);
            }
            return $value;
        }

        private function conditionMatches($row, $condition)
        {
            if (!preg_match('/^(`?[A-Za-z0-9_]+`?)\s*(=|LIKE)\s*(.+)$/i', $condition, $m)) {
                return true;
            }
            $col   = self::stripQuotes($m[1]);
            $op    = strtoupper($m[2]);
            $value = self::stripQuotes($m[3]);
            if (!array_key_exists($col, $row)) {
                return true;
            }
            if ($op === '=') {
                return (string) $row[$col] === (string) $value;
            }
            // Emulate SQL LIKE (case-insensitive substring match): strip the
            // '%'/_ wildcards the real driver expands natively.
            $value = str_replace(array('%', '_'), '', $value);
            return $value !== '' && stripos((string) $row[$col], $value) !== false;
        }

        public function matches($key = null)
        {
            $table = self::stripQuotes($this->table);
            $rows  = array();

            if ($table === '#__modules') {
                foreach ($this->db->modulesById as $row) {
                    if ($this->rowMatches($row)) {
                        $rows[] = $row;
                    }
                }
            }

            if ($table === '#__modules_menu') {
                foreach ($this->db->menuAssign as $row) {
                    if ($this->rowMatches($row)) {
                        $rows[] = $row;
                    }
                }
            }

            if ($table === '#__extensions') {
                foreach ($this->db->extensions as $row) {
                    if ($this->rowMatches($row)) {
                        $rows[] = $row;
                    }
                }
            }

            if ($table === '#__update_sites') {
                foreach ($this->db->updateSites as $row) {
                    if ($this->rowMatches($row)) {
                        $rows[] = $row;
                    }
                }
            }

            if ($table === '#__update_sites_extensions') {
                foreach ($this->db->updateSiteExt as $row) {
                    if ($this->rowMatches($row)) {
                        $rows[] = $row;
                    }
                }
            }

            if ($this->limit !== null) {
                $rows = array_slice($rows, 0, $this->limit);
            }

            if ($key !== null) {
                $out = array();
                foreach ($rows as $row) {
                    if (isset($row[$key])) {
                        $out[$row[$key]] = $row;
                    }
                }
                return $out;
            }

            return $rows;
        }

        private function rowMatches($row)
        {
            foreach ($this->wheres as $cond) {
                if (!$this->conditionMatches($row, $cond)) {
                    return false;
                }
            }
            return true;
        }

        public function run()
        {
            $table = self::stripQuotes($this->table);
            $this->db->executed[] = $this->type . ' ' . $table;

            if ($table === '#__modules') {
                if ($this->type === 'update') {
                    foreach ($this->db->modulesById as $id => $row) {
                        if ($this->rowMatches($row)) {
                            $this->db->modulesById[$id] = $this->applySets($row);
                            if (isset($this->db->modules[$row['module']]) && $this->db->modules[$row['module']]['id'] == $id) {
                                $this->db->modules[$row['module']] = $this->db->modulesById[$id];
                            }
                        }
                    }
                }
                if ($this->type === 'delete') {
                    foreach ($this->db->modulesById as $id => $row) {
                        if ($this->rowMatches($row)) {
                            unset($this->db->modulesById[$id]);
                            unset($this->db->modules[$row['module']]);
                        }
                    }
                }
                return;
            }

            if ($table === '#__modules_menu') {
                if ($this->type === 'update') {
                    foreach ($this->db->menuAssign as $i => $row) {
                        if ($this->rowMatches($row)) {
                            $this->db->menuAssign[$i] = $this->applySets($row);
                        }
                    }
                }
                if ($this->type === 'delete') {
                    $this->db->menuAssign = array_values(array_filter($this->db->menuAssign, function ($row) {
                        return !$this->rowMatches($row);
                    }));
                }
                return;
            }

            if ($table === '#__extensions') {
                if ($this->type === 'delete') {
                    $this->db->extensions = array_values(array_filter($this->db->extensions, function ($row) {
                        return !$this->rowMatches($row);
                    }));
                }
            }

            if ($table === '#__update_sites') {
                if ($this->type === 'delete') {
                    $this->db->updateSites = array_values(array_filter($this->db->updateSites, function ($row) {
                        return !$this->rowMatches($row);
                    }));
                }
            }

            if ($table === '#__update_sites_extensions') {
                if ($this->type === 'delete') {
                    $this->db->updateSiteExt = array_values(array_filter($this->db->updateSiteExt, function ($row) {
                        return !$this->rowMatches($row);
                    }));
                }
            }
        }

        private function applySets($row)
        {
            foreach ($this->sets as $setStr) {
                if (!preg_match('/^(.*)\s*=\s*(.+)$/s', $setStr, $m)) {
                    continue;
                }
                $column = self::stripQuotes($m[1]);
                $value  = self::stripQuotes($m[2]);
                $row[$column] = $value;
            }
            return $row;
        }
    }

    if (!class_exists('JFactory')) {
        class JFactory
        {
            public static $driver = null;

            public static function getDBO()
            {
                if (self::$driver === null) {
                    self::$driver = new FakeJoomlaDbDriver();
                }
                return self::$driver;
            }
        }
    }
}
