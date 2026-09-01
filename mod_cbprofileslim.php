<?php
/**
 * CB Profile Slim Display — Standalone Module
 *
 * Outputs: Display Name [avatar] for logged-in users, nothing for guests.
 * Reads user data via Community Builder (CB) API; works standalone — no
 * dependency on the sccard/cblogin module being on the page.
 * Top-level try/catch prevents any error from becoming a 500.
 *
 * @version 1.8.1
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

try {

$user = Factory::getUser();
if ($user->guest) {
    return;
}

require_once __DIR__ . '/helper.php';

$profileUrl   = isset($params) ? ModCbProfileSlimHelper::validateUrl($params->get('profile_url', '')) : '';
if ($profileUrl === '') {
    // No explicit URL: link to the user's own Community Builder profile.
    $profileUrl = ModCbProfileSlimHelper::cbProfileUrl((int) $user->id);
}
$avatarBasePath = isset($params) ? ModCbProfileSlimHelper::validateBasePath($params->get('avatar_base_path', '/images/comprofiler/')) : '/images/comprofiler/';
if ($avatarBasePath === '') {
    $avatarBasePath = '/images/comprofiler/';
}
$avatarSize   = isset($params) ? (int) $params->get('avatar_size', 32) : 32;
if ($avatarSize < 16 || $avatarSize > 256) {
    $avatarSize = 32;
}
$displayName  = ModCbProfileSlimHelper::getDisplayName((int) $user->id) ?: $user->get('name');
$avatarUrl    = ModCbProfileSlimHelper::getAvatar((int) $user->id, $avatarSize, (bool) (isset($params) ? $params->get('avatar_db_fallback', 0) : 0), $avatarBasePath);

$containerPadding = isset($params) ? ModCbProfileSlimHelper::validateCss($params->get('container_padding', '0 0 0 0')) : '0 0 0 0';
if ($containerPadding === '') {
    $containerPadding = '0 0 0 0';
}
$containerMargin  = isset($params) ? ModCbProfileSlimHelper::validateCss($params->get('container_margin', '0')) : '0';
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
$cssUrl = \Joomla\CMS\Uri\Uri::base() . 'modules/mod_cbprofileslim/css/cbprofileslim.css';
$doc->addStylesheet($cssUrl);
$doc->addCustomTag('<link rel="preload" href="' . htmlspecialchars($cssUrl, ENT_QUOTES, 'UTF-8') . '" as="style">');

?>
<div id="cbps-header" style="
  --cbps-container-padding: <?php echo htmlspecialchars($containerPadding, ENT_COMPAT, 'UTF-8'); ?>;
  --cbps-container-margin: <?php echo htmlspecialchars($containerMargin, ENT_COMPAT, 'UTF-8'); ?>;
  --cbps-avatar-size: <?php echo (int) $avatarSize; ?>px;
  --cbps-avatar-wrap-size: calc(<?php echo (int) $avatarSize; ?>px + 6px);
  --cbps-align-transform: <?php echo htmlspecialchars($alignTransform, ENT_QUOTES, 'UTF-8'); ?>;
">
  <a href="<?php echo htmlspecialchars($profileUrl); ?>" class="cbps-header-link">
    <?php if ($displayName): ?><span class="cbps-name"><?php echo htmlspecialchars($displayName); ?></span><?php endif; ?>
    <span class="cbps-avatar-wrap">
      <?php if ($avatarUrl): ?>
      <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="<?php echo htmlspecialchars($displayName); ?>" class="cbps-avatar" />
      <?php endif; ?>
    </span>
  </a>
</div>
<?php
} catch (\Throwable $e) {
    // Never output anything on error; prevents 500.
}
