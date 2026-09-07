<?php
/**
 * Joomla Profile Slim Display — install / migration script.
 *
 * v1.10.0 renamed the module element from `mod_cbprofileslim` to
 * `mod_profileslim` so the Community Builder prefix no longer implies CB is the
 * primary function. Installing this zip on a site that still has the older
 * element is NON-BREAKING: the legacy `mod_cbprofileslim` module instance has
 * its parameters, title, position, ordering, published/access state, language
 * and menu assignments copied onto the new `mod_profileslim` instance, and then
 * the legacy instance, its extension record and files are removed.
 *
 * Because the element name changed, the Joomla update channel cannot match the
 * old element, so upgrading is a manual "Extensions → Manage → Install" of the
 * zip. No configuration is lost during that install.
 *
 * @package     mod_profileslim
 * @since       1.10.0
 */
defined('_JEXEC') or die;

class ModProfileslimInstallerScript
{
	private $legacy = null;

	private $fields = array('title', 'note', 'position', 'ordering', 'published', 'access', 'showtitle', 'language', 'params');

	public function preflight($type, $parent)
	{
		if ($type === 'uninstall')
		{
			return;
		}

		$this->legacy = $this->loadModuleRow('mod_cbprofileslim');
	}

	public function install($parent)
	{
		return true;
	}

	public function update($parent)
	{
		return true;
	}

	public function postflight($type, $parent)
	{
		if ($type === 'uninstall' || !$this->legacy)
		{
			return;
		}

		$new = $this->loadModuleRow('mod_profileslim');
		if (!$new)
		{
			return;
		}

		$this->copyToNewModule($new);
		$this->removeLegacy();
	}

	private function loadModuleRow($element)
	{
		$db = JFactory::getDBO();
		$q  = $db->getQuery(true);

		$q->select('*')
			->from($db->quoteName('#__modules'))
			->where($db->quoteName('module') . ' = ' . $db->quote($element))
			->setLimit(1);

		$db->setQuery($q);
		$row = $db->loadAssoc();

		return (is_array($row) && isset($row['id'])) ? $row : null;
	}

	private function copyToNewModule(array $new)
	{
		$db  = JFactory::getDBO();
		$set = array();

		foreach ($this->fields as $f)
		{
			$value = array_key_exists($f, $this->legacy) ? $this->legacy[$f]
				: (array_key_exists($f, $new) ? $new[$f] : '');
			$set[] = $db->quoteName($f) . ' = ' . $db->quote((string) $value);
		}

		$db->setQuery(
			$db->getQuery(true)
				->update($db->quoteName('#__modules'))
				->set($set)
				->where($db->quoteName('id') . ' = ' . (int) $new['id'])
		);
		$db->execute();

		// Carry over the page (menu) assignments from the legacy instance id.
		$db->setQuery(
			$db->getQuery(true)
				->update($db->quoteName('#__modules_menu'))
				->set($db->quoteName('moduleid') . ' = ' . (int) $new['id'])
				->where($db->quoteName('moduleid') . ' = ' . (int) $this->legacy['id'])
		);
		$db->execute();
	}

	private function removeLegacy()
	{
		$db = JFactory::getDBO();

		$db->setQuery(
			$db->getQuery(true)
				->delete($db->quoteName('#__modules'))
				->where($db->quoteName('module') . ' = ' . $db->quote('mod_cbprofileslim'))
		);
		$db->execute();

		$db->setQuery(
			$db->getQuery(true)
				->delete($db->quoteName('#__extensions'))
				->where($db->quoteName('element') . ' = ' . $db->quote('mod_cbprofileslim'))
				->where($db->quoteName('type') . ' = ' . $db->quote('module'))
		);
		$db->execute();

		$oldDir = JPATH_SITE . '/modules/mod_cbprofileslim';
		if (is_dir($oldDir))
		{
			$this->removeDirectory($oldDir);
		}
	}

	private function removeDirectory($path)
	{
		$items = glob($path . '/*');

		if ($items)
		{
			foreach ($items as $item)
			{
				if (is_dir($item))
				{
					$this->removeDirectory($item);
					@rmdir($item);
				}
				else
				{
					@unlink($item);
				}
			}
		}

		@rmdir($path);
	}
}