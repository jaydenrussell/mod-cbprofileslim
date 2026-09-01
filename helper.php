<?php
/**
 * @package     mod_cbprofileslim
 * @subpackage  CB Profile Slim Display
 * @version     1.7.0
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

class ModCbProfileSlimHelper
{
    private const CB_LOADED_FLAG = 'MOD_CBPROFILESLIM_CB_LOADED';

    /**
     * @param int $userId
     * @return string
     */
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

    /**
     * @param int    $userId
     * @param int    $size
     * @param bool   $allowDbFallback
     * @param string $basePath
     * @return string
     */
    public static function getAvatar($userId, $size = 32, $allowDbFallback = false, $basePath = '/images/comprofiler/')
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

        return self::sanitizeAvatarUrl($raw, $basePath);
    }

    /**
     * Accepts a CB avatar value that is either a relative path (flat filename OR a
     * subfolder like 383_abc/xyz.jpg) OR an absolute URL on the SITE'S OWN host
     * (e.g. https://mysite.com/images/comprofiler/x.jpg) — the foreign host is
     * stripped, leaving a same-origin relative path. Rejects foreign/abs URLs,
     * javascript:/data: schemes, protocol-relative (//), backslashes, and "..".
     *
     * @param string $raw
     * @param string $basePath
     * @return string
     */
    private static function sanitizeAvatarUrl($raw, $basePath = '/images/comprofiler/')
    {
        if (!is_string($raw) || $raw === '') {
            return '';
        }

        // Same-site absolute URL? (http:// or https://)
        if (preg_match('#^https?://#i', $raw)) {
            $host = parse_url($raw, PHP_URL_HOST);
            $siteHost = self::siteHost();
            if ($host === null || $siteHost === '' || strcasecmp($host, $siteHost) !== 0) {
                self::log('Avatar rejected: foreign/abs host: ' . $raw);
                return '';
            }
            $path = parse_url($raw, PHP_URL_PATH);
            if (!is_string($path) || $path === '') {
                return '';
            }
            $raw = $path;
        } elseif (preg_match('#^[a-z][a-z0-9+.\\-]*:#i', $raw)) {   // other scheme (javascript:, data:)
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

        // Strip leading slash(es) so the value is always relative to $basePath.
        $rel = ltrim($raw, '/');

        // Allow only relative path segments: [seg]/[seg], safe chars per segment,
        // no ".." traversal, no empty segments. Rejects anything else (incl. flat
        // filenames, which match too).
        if ($rel === '' || !preg_match('#^(?:[a-zA-Z0-9_.-]+/)*[a-zA-Z0-9_.-]+$#', $rel)) {
            self::log('Avatar rejected: invalid path: ' . $raw);
            return '';
        }
        if (strpos($rel, '..') !== false) {
            self::log('Avatar rejected: traversal: ' . $raw);
            return '';
        }

        $base = rtrim($basePath, '/') . '/';
        // If CB already returned the full base-relative path (e.g. the configured
        // image dir prefix), use it as-is instead of double-prefixing.
        if (strpos($rel, ltrim($base, '/')) === 0) {
            return $base . substr($rel, strlen(ltrim($base, '/')));
        }
        return $base . $rel;
    }

    /**
     * Returns the site's own HTTP host (no port, lowercased) for same-origin checks.
     *
     * @return string
     */
    private static function siteHost()
    {
        if (class_exists('\\Joomla\\CMS\\Uri\\Uri')) {
            $h = \Joomla\CMS\Uri\Uri::root();
            $host = parse_url($h, PHP_URL_HOST);
            return is_string($host) ? strtolower($host) : '';
        }
        if (!empty($_SERVER['HTTP_HOST'])) {
            return strtolower(preg_replace('/:[0-9]+$/', '', $_SERVER['HTTP_HOST']));
        }
        return '';
    }

    /**
     * Validates the configured avatar base directory. Only allows a root-relative
     * path of safe characters with a leading slash. Scheme/protocol-relative/backslash
     * inputs are rejected and fall back to the standard CB location.
     *
     * @param string $raw
     * @return string
     */
    public static function validateBasePath($raw)
    {
        if (!is_string($raw) || $raw === '') {
            return '/images/comprofiler/';
        }
        if (preg_match('#^[a-z][a-z0-9+.\\-]*:#i', $raw)) {
            return '/images/comprofiler/';
        }
        if (strpos($raw, '//') === 0 || strpos($raw, '\\\\') !== false) {
            return '/images/comprofiler/';
        }
        if (!preg_match('#^/[a-zA-Z0-9_./-]+$#', $raw)) {
            return '/images/comprofiler/';
        }
        // Reject path traversal (..) and empty segments (//) in the base path.
        if (strpos($raw, '..') !== false || strpos($raw, '//') !== false) {
            return '/images/comprofiler/';
        }
        return rtrim($raw, '/') . '/';
    }

    /**
     * Builds a Community Builder profile URL for the given user via the CB API.
     * Returns '' if CB is unavailable (caller then renders an unlinked label).
     *
     * @param int $userId
     * @return string
     */
    public static function cbProfileUrl($userId)
    {
        self::initCbApi();
        if (!class_exists('CBuser')) {
            return '';
        }
        try {
            $cbUser = CBuser::getInstance((int) $userId, false);
            if ($cbUser && method_exists($cbUser, 'userProfileURL')) {
                $url = $cbUser->userProfileURL();
                if (is_string($url) && $url !== '') {
                    return self::validateUrl($url);
                }
            }
        } catch (\Throwable $e) {
            self::log('cbProfileUrl failed: ' . $e->getMessage());
        }
        return '';
    }

    /**
     * Strict URL validator for the profile link. Only http(s) schemes allowed;
     * rejects javascript:, data:, protocol-relative (//), and anything non-URL.
     * Returns the validated URL or an empty string (never an unsafe value).
     *
     * @param string $raw
     * @return string
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
     * tokens safe inside a CSS declaration (numbers, units, %, spacing, !important).
     * Rejects ; { } url( and any other punctuation that enables CSS injection.
     *
     * @param string $raw
     * @return string
     */
    public static function validateCss($raw)
    {
        if (!is_string($raw) || $raw === '') {
            return '';
        }
        if (!preg_match('#^[0-9a-z!%(). +-]+$#i', $raw)) {
            self::log('CSS value rejected (unsafe chars): ' . $raw);
            return '';
        }
        // Block CSS function-call tokens (expression(...), url(...), calc(...),
        // var(...), etc.). Padding/margin values never need a function call; a
        // '(' immediately after a letter is the signature of one. This closes
        // the legacy expression()/javascript: CSS-injection vector.
        if (preg_match('#[a-z]\s*\(#i', $raw)) {
            self::log('CSS value rejected (function call): ' . $raw);
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

        // Only mark initialized if CB actually became available. Otherwise a
        // transient failure would be cached for the whole request: every later
        // call would skip re-init and silently return empty.
        if (class_exists('CBuser')) {
            define(self::CB_LOADED_FLAG, 1);
        }
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
