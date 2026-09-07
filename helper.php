<?php
/**
 * @package     mod_cbprofileslim
 * @subpackage  Joomla Profile Slim Display
 * @version     1.8.6
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;

class ModProfileSlimHelper
{
    /**
     * @param int $userId
     * @return string
     * @since 1.2.0
     */
    public static function getDisplayName($userId)
    {
        try {
            $user = Factory::getUser((int) $userId);
            $name = $user->get('name');
            if (is_string($name) && $name !== '') {
                return $name;
            }
            $username = $user->get('username');
            if (is_string($username) && $username !== '') {
                return $username;
            }
        } catch (\Throwable $e) {
            self::log('getDisplayName failed: ' . $e->getMessage());
        }
        return '';
    }

    /**
     * @param int    $userId
     * @param int    $size
     * @param bool   $allowDbFallback
     * @param string $basePath
     * @return string
     * @since 1.4.1
     */
    public static function getAvatar($userId, $size = 32, $allowDbFallback = false, $basePath = '/images/')
    {
        $raw = '';

        try {
            $user = Factory::getUser((int) $userId);
            $avatar = $user->get('avatar');
            if (is_string($avatar) && $avatar !== '') {
                $raw = $avatar;
            }
        } catch (\Throwable $e) {
            self::log('getAvatar user profile failed: ' . $e->getMessage());
        }

        if ($raw === '' && $allowDbFallback) {
            try {
                $db = Factory::getDbo();
                $db->setQuery(
                    $db->getQuery(true)
                        ->select($db->quoteName('profile_value'))
                        ->from($db->quoteName('#__user_profiles'))
                        ->where($db->quoteName('user_id') . ' = ' . (int) $userId)
                        ->where($db->quoteName('profile_key') . ' = ' . $db->quote('avatar'))
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
     * Accepts an avatar value that is either a relative path, a URL on
     * the site's own host, or a raw avatar filename. Strips foreign
     * hosts, javascript:/data: schemes, protocol-relative (//),
     * backslashes, and "..".
     *
     * @param string $raw
     * @param string $basePath
     * @return string
     * @since 1.5.1
     */
    private static function sanitizeAvatarUrl($raw, $basePath = '/images/')
    {
        if (!is_string($raw) || $raw === '') {
            return '';
        }

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
        } elseif (preg_match('#^[a-z][a-z0-9+.\\-]*:#i', $raw)) {
            self::log('Avatar rejected: scheme present: ' . $raw);
            return '';
        }
        if (strpos($raw, '//') === 0) {
            self::log('Avatar rejected: protocol-relative: ' . $raw);
            return '';
        }
        if (strpos($raw, '\\') !== false) {
            return '';
        }

        $rel = ltrim($raw, '/');

        if ($rel === '' || !preg_match('#^(?:[a-zA-Z0-9_.-]+/)*[a-zA-Z0-9_.-]+$#', $rel)) {
            self::log('Avatar rejected: invalid path: ' . $raw);
            return '';
        }
        if (strpos($rel, '..') !== false) {
            self::log('Avatar rejected: traversal: ' . $raw);
            return '';
        }

        $base = rtrim($basePath, '/') . '/';
        if (strpos($rel, ltrim($base, '/')) === 0) {
            return $base . substr($rel, strlen(ltrim($base, '/')));
        }
        return $base . $rel;
    }

    /**
     * Returns the site's own HTTP host (no port, lowercased) for same-origin checks.
     *
     * @return string
     * @since 1.5.1
     */
    private static function siteHost()
    {
        if (class_exists('\Joomla\CMS\Uri\Uri')) {
            $h = \Joomla\CMS\Uri\Uri::root();
            $host = parse_url($h, PHP_URL_HOST);
            return is_string($host) ? strtolower($host) : '';
        }
        if (!empty($_SERVER['HTTP_HOST'])) {
            $host = strtolower(preg_replace('/:[0-9]+$/', '', $_SERVER['HTTP_HOST']));
            if (preg_match('#^[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]$#', $host)
                && !preg_match('#^\d{1,3}(\.\d{1,3}){3}$#', $host)) {
                return $host;
            }
        }
        return '';
    }

    /**
     * Returns the default Joomla profile URL for the given user.
     *
     * @param int $userId
     * @return string
     * @since 1.6.0
     */
    public static function joomlaProfileUrl($userId)
    {
        try {
            return Route::_('index.php?option=com_users&view=profile&id=' . (int) $userId);
        } catch (\Throwable $e) {
            self::log('joomlaProfileUrl failed: ' . $e->getMessage());
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
     * @since 1.5.2
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
        if (preg_match('#[\x00-\x20<>"\'\\\\]#', $raw)) {
            self::log('Profile URL rejected (unsafe chars): ' . $raw);
            return '';
        }
        $decoded = rawurldecode($raw);
        if ($decoded !== $raw && preg_match('#[\x00-\x20<>"\']#', $decoded)) {
            self::log('Profile URL rejected (URL-encoded unsafe chars): ' . $raw);
            return '';
        }
        $doubleDecoded = rawurldecode($decoded);
        if ($decoded !== $doubleDecoded && preg_match('#[\x00-\x20<>"\']#', $doubleDecoded)) {
            self::log('Profile URL rejected (double-encoded unsafe chars): ' . $raw);
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
     * @since 1.5.2
     */
    public static function validateCss($raw)
    {
        if (!is_string($raw) || $raw === '') {
            return '';
        }
        if (!preg_match('#^[0-9a-z%(). +-]+$#i', $raw)) {
            self::log('CSS value rejected (unsafe chars): ' . $raw);
            return '';
        }
        if (preg_match('#!important#i', $raw)) {
            self::log('CSS value rejected (!important): ' . $raw);
            return '';
        }
        if (preg_match('#[a-z]\s*\(#i', $raw)) {
            self::log('CSS value rejected (function call): ' . $raw);
            return '';
        }
        return $raw;
    }

    /**
     * Validates the configured avatar base directory. Only allows a root-relative
     * path of safe characters with a leading slash.
     *
     * @param string $raw
     * @return string
     * @since 1.5.8
     */
    public static function validateBasePath($raw)
    {
        if (!is_string($raw) || $raw === '') {
            return '/images/';
        }
        if (preg_match('#^[a-z][a-z0-9+.\\-]*:#i', $raw)) {
            return '/images/';
        }
        if (strpos($raw, '//') === 0 || strpos($raw, '\\\\') !== false) {
            return '/images/';
        }
        if (!preg_match('#^/[a-zA-Z0-9_./-]+$#', $raw)) {
            return '/images/';
        }
        if (strpos($raw, '..') !== false || strpos($raw, '//') !== false || strpos($raw, './') !== false) {
            return '/images/';
        }
        $normalized = str_replace('/./', '/', $raw);
        if (strpos($normalized, '/./') !== false || preg_match('#/\.$#', $normalized)) {
            return '/images/';
        }
        return rtrim($normalized, '/') . '/';
    }

    private static $initFailed = false;

    /**
     * @since 1.2.0
     */
    protected static function log($msg)
    {
        try {
            Log::add('mod_cbprofileslim: ' . $msg, Log::WARNING, 'mod_cbprofileslim');
        } catch (\Throwable $e) {
        }
    }
}