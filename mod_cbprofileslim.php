<?php
/**
 * Joomla Profile Slim Display — Standalone Module
 *
 * Outputs: Display Name [avatar] for logged-in users, nothing for guests.
 * Uses Joomla's built-in profile system for profile links and display
 * names. Works standalone — no dependency on any third-party profile
 * extension being on the page.
 * Top-level try/catch prevents any error from becoming a 500.
 *
 * @version 1.8.6
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

try {

$user = Factory::getUser();
if ($user->guest) {
    return;
}

require_once __DIR__ . '/helper.php';

$profileUrl   = isset($params) ? ModProfileSlimHelper::validateUrl($params->get('profile_url', '')) : '';
if ($profileUrl === '') {
    $profileUrl = ModProfileSlimHelper::joomlaProfileUrl((int) $user->id);
}
$avatarBasePath = isset($params) ? ModProfileSlimHelper::validateBasePath($params->get('avatar_base_path', '/images/')) : '/images/';
if ($avatarBasePath === '') {
    $avatarBasePath = '/images/';
}
$avatarSize   = isset($params) ? (int) $params->get('avatar_size', 32) : 32;
if ($avatarSize < 16 || $avatarSize > 256) {
    $avatarSize = 32;
}
$displayName  = ModProfileSlimHelper::getDisplayName((int) $user->id) ?: $user->get('name');
$avatarUrl    = ModProfileSlimHelper::getAvatar((int) $user->id, $avatarSize, (bool) (isset($params) ? $params->get('avatar_db_fallback', 0) : 0), $avatarBasePath);

$containerPadding = isset($params) ? ModProfileSlimHelper::validateCss($params->get('container_padding', '0 0 0 0')) : '0 0 0 0';
if ($containerPadding === '') {
    $containerPadding = '0 0 0 0';
}
$containerMargin  = isset($params) ? ModProfileSlimHelper::validateCss($params->get('container_margin', '0')) : '0';
if ($containerMargin === '') {
    $containerMargin = '0';
}

$avatarAlign = isset($params) ? $params->get('avatar_align', 'top') : 'top';
switch ($avatarAlign) {
    case 'none':   $alignTransform = 'none'; break;
    case 'center': $alignTransform = 'translateY(50%)'; break;
    case 'bottom': $alignTransform = 'translateY(100%)'; break;
    case 'top':
    default:       $alignTransform = 'translateY(0)'; break;
}

$doc = Factory::getDocument();
$cssUrl = \Joomla\CMS\Uri\Uri::base() . 'modules/mod_cbprofileslim/css/profile-slim.css';
$doc->addStylesheet($cssUrl);
$doc->addCustomTag('<link rel="preload" href="' . htmlspecialchars($cssUrl, ENT_QUOTES, 'UTF-8') . '" as="style" type="text/css" onerror="this.onerror=null;this.rel=\'stylesheet\'">');

?>
<div id="ps-header" style="
  --ps-container-padding: <?php echo htmlspecialchars($containerPadding, ENT_QUOTES, 'UTF-8'); ?>;
  --ps-container-margin: <?php echo htmlspecialchars($containerMargin, ENT_QUOTES, 'UTF-8'); ?>;
  --ps-avatar-size: <?php echo (int) $avatarSize; ?>px;
  --ps-avatar-wrap-size: calc(<?php echo (int) $avatarSize; ?>px + 6px);
  --ps-align-transform: <?php echo htmlspecialchars($alignTransform, ENT_QUOTES, 'UTF-8'); ?>;
">
  <a href="<?php echo htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8'); ?>" class="ps-header-link">
    <?php if ($displayName): ?><span class="ps-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
    <span class="ps-avatar-wrap">
      <?php if ($avatarUrl): ?>
      <img src="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>" class="ps-avatar" />
      <?php endif; ?>
    </span>
  </a>
</div>
<?php
} catch (\Throwable $e) {
}