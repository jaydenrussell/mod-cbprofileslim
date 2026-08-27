<?php
/**
 * @package     mod_cbprofileslim
 * @subpackage  CB Profile Slim Display
 * @version     1.5.2
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

class ModCbProfileSlimHelper
{
    private const CB_LOADED_FLAG = 'MOD_CBPROFILESLIM_CB_LOADED';
    private const IMAGE_DIR = '/images/comprofiler/';

    public static function getDisplayName($userId)
    {
        $name = '';
        self::initCbApi();

        if (class_exists('CBuser')) {
            try {
                $cbUser = CBuser::getInstance((int) $userId, false);
                if ($cbUser) {
                    $cbName = $cbUser->getField('typename', null, 'raw');
                    if (is_string($cbName) && $cbName !== '') {
                        $name = $cbName;
                    }
                }
            } catch (\Throwable $e) {
                self::log('getDisplayName failed: ' . $e->getMessage());
            }
        }

        return $name;
    }

    public static function getAvatar($userId, $size = 32, $allowDbFallback = false)
    {
        $raw = '';

        self::initCbApi();
        if (class_exists('CBuser')) {
            try {
                $cbUser = CBuser::getInstance((int) $userId, false);
                if ($cbUser) {
                    // Method A: raw relative path
                    $raw = $cbUser->getField('avatar', null, 'csv');
                    // Method B: parse src from rendered HTML
                    if (empty($raw)) {
                        $html = $cbUser->getField('avatar', null, 'html', 'none', 'profile', 0, false);
                        if (is_string($html)) {
                            if (preg_match('#src="([^"]+)"#i', $html, $m)) {
                                $raw = $m[1];
                            } elseif (preg_match("#src='([^']+)'#i", $html, $m)) {
                                $raw = $m[1];
                            }
                        }
                    }
                    // Method C: direct property
                    if (empty($raw) && !empty($cbUser->avatar)) {
                        $raw = $cbUser->avatar;
                    }
                }
            } catch (\Throwable $e) {
                self::log('getAvatar CB path failed: ' . $e->getMessage());
            }
        }

        // DB fallback (opt-in)
        if ($raw === '' && $allowDbFallback) {
            try {
                $db = Factory::getDbo();
                $db->setQuery(
                    $db->getQuery(true)
                        ->select($db->quoteName('avatar'))
                        ->from($db->quoteName('#__comprofiler'))
                        ->where($db->quoteName('user_id') . ' = ' . (int) $userId)
                );
                $dbAvatar = $db->loadResult();
                if (is_string($dbAvatar) && $dbAvatar !== '' && $dbAvatar !== '0') {
                    $raw = $dbAvatar;
                }
            } catch (\Throwable $e) {
                self::log('getAvatar DB fallback failed: ' . $e->getMessage());
            }
        }

        return self::sanitizeAvatarUrl($raw);
    }

    /**
     * Strict: only accept a root-relative path that resolves inside the
     * comprofiler images directory. Anything else (absolute URL, protocol-
     * relative, foreign host, non-path) is rejected -> empty string.
     */
    private static function sanitizeAvatarUrl($raw)
    {
        if (!is_string($raw) || $raw === '') {
            return '';
        }

        // Reject anything that is or could become an external/abs URL.
        if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $raw)) {      // scheme: (http:, javascript:, etc.)
            self::log('Avatar rejected: scheme present: ' . $raw);
            return '';
        }
        if (strpos($raw, '//') === 0) {                          // protocol-relative
            self::log('Avatar rejected: protocol-relative: ' . $raw);
            return '';
        }
        if (strpos($raw, '\\') !== false) {                      // Windows/abs path
            return '';
        }

        // Strip any leading slash(es); we re-prefix to the known image dir.
        $clean = ltrim($raw, '/');

        // Allow only safe filename characters (no slashes, no dots-in-path traversal).
        if (!preg_match('#^[a-zA-Z0-9_.-]+$#', $clean)) {
            self::log('Avatar rejected: invalid chars: ' . $raw);
            return '';
        }

        return self::IMAGE_DIR . $clean;
    }

    /**
     * Strict URL validator for the profile link. Only http(s) schemes allowed;
     * rejects javascript:, data:, protocol-relative (//), and anything non-URL.
     * Returns the validated URL or an empty string (never an unsafe value).
     */
    public static function validateUrl($raw)
    {
        if (!is_string($raw) || $raw === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $raw)) {
            self::log('Profile URL rejected (not http(s)): ' . $raw);
            return '';
        }
        // Reject embedded control chars / whitespace that enable scheme confusion.
        if (preg_match('#[\x00-\x20<>"]#', $raw)) {
            self::log('Profile URL rejected (unsafe chars): ' . $raw);
            return '';
        }
        return $raw;
    }

    /**
     * Strict CSS-value validator for padding/margin params. Allows only
     * tokens safe inside a CSS declaration (numbers, units, %, spacing).
     * Rejects ; { } url( and any other punctuation that enables CSS injection.
     */
    public static function validateCss($raw)
    {
        if (!is_string($raw) || $raw === '') {
            return '';
        }
        if (!preg_match('#^[0-9a-z%. ()+-]+$#i', $raw)) {
            self::log('CSS value rejected (unsafe chars): ' . $raw);
            return '';
        }
        return $raw;
    }

    protected static function initCbApi()
    {
        if (defined(self::CB_LOADED_FLAG)) {
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
            self::log('loadPluginGroup failed: ' . $e->getMessage());
        }

        define(self::CB_LOADED_FLAG, 1);
    }

    private static function log($msg)
    {
        try {
            Log::add('mod_cbprofileslim: ' . $msg, Log::WARNING, 'mod_cbprofileslim');
        } catch (\Throwable $e) {
            // logging must never throw
        }
    }
}
