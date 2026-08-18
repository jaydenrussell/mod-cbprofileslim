<?php
/**
 * @package     mod_sccuserheader
 * @subpackage  SCC User Header
 * @version     1.3.0
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class ModSccUserHeaderHelper
{
    public static function getDisplayName($userId)
    {
        $name = '';

        if (class_exists('CBuser')) {
            try {
                self::initCbApi();
                $cbUser = CBuser::getInstance((int) $userId, false);
                if ($cbUser) {
                    $cbName = $cbUser->getField('typename', null, 'raw');
                    if ($cbName) {
                        $name = $cbName;
                    }
                }
            } catch (\Throwable $e) {
                // fall through to Joomla name
            }
        }

        return $name;
    }

    public static function getAvatar($userId, $size = 32)
    {
        $url = '';

        // PRIMARY: CB Field API
        if (class_exists('CBuser')) {
            try {
                self::initCbApi();
                $cbUser = CBuser::getInstance((int) $userId, false);
                if ($cbUser) {
                    $html = $cbUser->getField('avatar', null, 'html', 'none', 'profile', 0, false);
                    if ($html && preg_match('#src="([^"]+)"#i', $html, $m)) {
                        $url = $m[1];
                    } elseif ($html && preg_match("#src='([^']+)'#i", $html, $m)) {
                        $url = $m[1];
                    }
                }
            } catch (\Throwable $e) {
                // fall through to DB fallback
            }
        }

        // FALLBACK: Direct DB query using user_id foreign key
        if (!$url) {
            try {
                $db = Factory::getDbo();
                $db->setQuery(
                    $db->getQuery(true)
                        ->select($db->quoteName('avatar'))
                        ->from($db->quoteName('#__comprofiler'))
                        ->where($db->quoteName('user_id') . ' = ' . (int) $userId)
                );
                $dbAvatar = $db->loadResult();
                if ($dbAvatar && $dbAvatar !== '0' && $dbAvatar !== '') {
                    // Allowlist: only permit safe filename characters to prevent
                    // path traversal or injection from the stored avatar value.
                    if (preg_match('/^[a-zA-Z0-9_\.\-\/]+$/', $dbAvatar)) {
                        if (strpos($dbAvatar, '/') !== false) {
                            $url = '/' . ltrim($dbAvatar, '/');
                        } else {
                            $url = '/images/comprofiler/' . $dbAvatar;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // no fallback image
            }
        }

        // Normalise URL
        if ($url && strpos($url, Uri::root()) === 0) {
            $url = '/' . ltrim(str_replace(Uri::root(), '', $url), '/');
        } elseif ($url && strpos($url, 'http') === 0) {
            $currentHost = Uri::getInstance()->getHost();
            $avatarHost  = parse_url($url, PHP_URL_HOST);
            if ($avatarHost === $currentHost || 'www.' . $currentHost === $avatarHost) {
                $url = '/' . ltrim(parse_url($url, PHP_URL_PATH), '/');
            }
        }

        return $url;
    }

    protected static function initCbApi()
    {
        if (defined('SCC_CB_API_LOADED')) {
            return;
        }

        $cbFoundation = JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php';
        if (file_exists($cbFoundation)) {
            include_once $cbFoundation;
        }
        if (function_exists('cbimport')) {
            cbimport('cb.html');
        }
        try {
            if (isset($GLOBALS['_PLUGINS']) && method_exists($GLOBALS['_PLUGINS'], 'loadPluginGroup')) {
                $GLOBALS['_PLUGINS']->loadPluginGroup('user');
            }
        } catch (\Throwable $e) {
            // Ignore if loadPluginGroup fails
        }

        define('SCC_CB_API_LOADED', 1);
    }
}
