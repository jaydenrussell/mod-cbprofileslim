<?php
/**
 * @package     mod_sccuserheader
 * @subpackage  SCC User Header
 * @version     1.4.1
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class ModSccUserHeaderHelper
{
    public static function getDisplayName($userId)
    {
        $name = '';

        // 1. MUST initialize CB API first so CBuser class gets loaded
        self::initCbApi();

        if (class_exists('CBuser')) {
            try {
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

    public static function getAvatar($userId, $size = 32, $allowDbFallback = false)
    {
        $url = '';

        // 1. MUST initialize CB API first so CBuser class gets loaded
        self::initCbApi();

        if (class_exists('CBuser')) {
            try {
                $cbUser = CBuser::getInstance((int) $userId, false);
                if ($cbUser) {
                    $rawPath = '';

                    // Method A: Get formatted field string directly (returns relative path)
                    $rawPath = $cbUser->getField('avatar', null, 'csv');

                    // Method B: Fallback to HTML string parsing if CSV returned empty
                    if (empty($rawPath)) {
                        $html = $cbUser->getField('avatar', null, 'html', 'none', 'profile', 0, false);
                        if ($html && preg_match('#src="([^"]+)"#i', $html, $m)) {
                            $rawPath = $m[1];
                        } elseif ($html && preg_match("#src='([^']+)'#i", $html, $m)) {
                            $rawPath = $m[1];
                        }
                    }

                    // Method C: Fallback to direct CB user property access
                    if (empty($rawPath) && !empty($cbUser->avatar)) {
                        $rawPath = $cbUser->avatar;
                    }

                    if (!empty($rawPath)) {
                        if (strpos($rawPath, 'http') === 0 || strpos($rawPath, '/') === 0) {
                            $url = $rawPath;
                        } else {
                            $url = '/images/comprofiler/' . ltrim($rawPath, '/');
                        }
                    }
                }
            } catch (\Throwable $e) {
                // fall through to DB fallback (if enabled)
            }
        }

        // OPTIONAL FALLBACK: Direct DB query using user_id foreign key
        if (!$url && $allowDbFallback) {
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
            cbimport('cb.database');
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
