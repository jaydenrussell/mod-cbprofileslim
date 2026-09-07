<?php
/**
 * Unit tests for the v1.10.0 non-breaking rename migration
 * (ModProfileslimInstallerScript in script.php).
 *
 * Verifies that installing mod_profileslim on a site that still has the older
 * mod_cbprofileslim element carries the existing module's configuration over —
 * parameters, title, position, ordering, published/access, menu assignments —
 * and removes the legacy instance, extension record and files. Fresh installs
 * and same-element updates must be no-ops.
 *
 * @package     mod_profileslim
 * @since       1.10.0
 */

namespace {
    if (!defined('_JEXEC')) {
        define('_JEXEC', 1);
    }

    require_once __DIR__ . '/bootstrap.php';
    require_once __DIR__ . '/../script.php';

    use PHPUnit\Framework\TestCase;

    class ScriptTest extends TestCase
    {
        private $legacyParams = '{"avatar_size":"36","profile_url":"","container_padding":"0 0 0 0","container_margin":"0","avatar_align":"top","avatar_base_path":"/images/","avatar_db_fallback":"0"}';

        protected function setUp(): void
        {
            $db = new \FakeJoomlaDbDriver();
            \JFactory::$driver = $db;
        }

        private function seedLegacy($db, $withMenuAssign = true, $withExt = true)
        {
            $db->modulesById[12] = array(
                'id'         => '12',
                'module'     => 'mod_cbprofileslim',
                'title'      => 'My Profile',
                'note'       => '',
                'position'   => 'topbar-2',
                'ordering'   => '3',
                'published'  => '1',
                'access'     => '1',
                'showtitle'  => '1',
                'language'   => '*',
                'params'     => $this->legacyParams,
            );
            if ($withMenuAssign) {
                $db->menuAssign[] = array('moduleid' => '12', 'menuid' => '0');
            }
            if ($withExt) {
                $db->extensions[] = array('element' => 'mod_cbprofileslim', 'type' => 'module', 'name' => 'Joomla Profile Slim Display');
            }
        }

        private function seedNew($db, $title = 'Joomla Profile Slim Display')
        {
            $db->modulesById[13] = array(
                'id'         => '13',
                'module'     => 'mod_profileslim',
                'title'      => $title,
                'note'       => '',
                'position'   => '',
                'ordering'   => '0',
                'published'  => '1',
                'access'     => '1',
                'showtitle'  => '1',
                'language'   => '*',
                'params'     => '',
            );
        }

        public function testFreshInstallIsNoOpWithoutLegacy()
        {
            $db = \JFactory::getDBO();
            $this->seedNew($db, 'Joomla Profile Slim Display');

            $script = new \ModProfileslimInstallerScript();
            $script->preflight('install', null);
            $script->postflight('install', null);

            $row = $db->modulesById[13];
            $this->assertSame('Joomla Profile Slim Display', $row['title']);
            $this->assertSame('', $row['params']);

            $this->assertArrayNotHasKey(12, $db->modulesById);
            $this->assertEmpty($db->menuAssign);
            $this->assertEmpty($db->extensions);
        }

        public function testUpdateSameElementDoesNotTouchExistingParams()
        {
            $db = \JFactory::getDBO();
            $this->seedNew($db, 'Existing Title');
            $db->modulesById[13]['position'] = 'topbar-2';
            $db->modulesById[13]['params']   = '{"avatar_size":"40"}';

            $script = new \ModProfileslimInstallerScript();
            $script->preflight('update', null);
            $script->postflight('update', null);

            $this->assertSame('Existing Title', $db->modulesById[13]['title']);
            $this->assertSame('{"avatar_size":"40"}', $db->modulesById[13]['params']);
            $this->assertSame('topbar-2', $db->modulesById[13]['position']);
        }

        public function testLegacyConfigIsMigratedAndLegacyRemoved()
        {
            $db = \JFactory::getDBO();
            $this->seedLegacy($db);
            $this->seedNew($db);
            $db->menuAssign[] = array('moduleid' => '11', 'menuid' => '0'); // unrelated module — must survive

            $script = new \ModProfileslimInstallerScript();
            $script->preflight('install', null);
            $script->postflight('install', null);

            // New element carries the legacy configuration.
            $new = $db->modulesById[13];
            $this->assertSame('My Profile', $new['title']);
            $this->assertSame('topbar-2', $new['position']);
            $this->assertSame('3', $new['ordering']);
            $this->assertSame('1', $new['published']);
            $this->assertSame('1', $new['access']);
            $this->assertSame('*', $new['language']);
            $this->assertSame($this->legacyParams, $new['params']);

            // Legacy instance and extension record are gone.
            $this->assertArrayNotHasKey(12, $db->modulesById);
            $this->assertEmpty($db->extensions);

            // Menu assignments transferred from the old id; unrelated row survives.
            $this->assertSame(array('moduleid' => '13', 'menuid' => '0'), $db->menuAssign[0]);
            $this->assertSame(array('moduleid' => '11', 'menuid' => '0'), $db->menuAssign[1]);

            // The deletes actually ran against #__modules and #__extensions.
            $this->assertContains('delete #__modules', $db->executed);
            $this->assertContains('delete #__extensions', $db->executed);
        }

        public function testLegacyFolderIsRemoved()
        {
            $db = \JFactory::getDBO();
            $this->seedLegacy($db);
            $this->seedNew($db);

            $oldDir = JPATH_SITE . '/modules/mod_cbprofileslim';
            if (!is_dir($oldDir)) {
                mkdir($oldDir, 0777, true);
            }
            file_put_contents($oldDir . '/mod_cbprofileslim.php', '<?php exit;');

            $script = new \ModProfileslimInstallerScript();
            $script->preflight('install', null);
            $script->postflight('install', null);

            $this->assertFileDoesNotExist($oldDir . '/mod_cbprofileslim.php');
            $this->assertDirectoryDoesNotExist($oldDir);
        }
    }
}