<?php
/**
 * CB Profile Slim Display — Standalone Module
 *
 * Outputs: Display Name [avatar] for logged-in users, nothing for guests.
 * Reads user data via Community Builder (CB) API; works standalone — no
 * dependency on the sccard/cblogin module being on the page.
 * Top-level try/catch prevents any error from becoming a 500.
 *
 * @version 1.7.1
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

?>
<style>
#cbps-header {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  text-decoration: none;
  color: inherit;
  font-size: inherit;
  font-weight: inherit;
  padding: <?php echo htmlspecialchars($containerPadding, ENT_COMPAT, 'UTF-8'); ?>;
  margin: <?php echo htmlspecialchars($containerMargin, ENT_COMPAT, 'UTF-8'); ?>;
}
#cbps-header .cbps-header-link {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
}
#cbps-header .cbps-name {
  line-height: 1.2;
  white-space: nowrap;
}
#cbps-header .cbps-avatar-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: calc(<?php echo (int) $avatarSize; ?>px + 6px);
  height: calc(<?php echo (int) $avatarSize; ?>px + 6px);
  border-radius: 50%;
  border: 3px solid #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  overflow: visible;
  z-index: 10;
  transform: <?php echo htmlspecialchars($alignTransform, ENT_QUOTES, 'UTF-8'); ?>;
}
#cbps-header .cbps-avatar {
  width: <?php echo (int) $avatarSize; ?>px;
  height: <?php echo (int) $avatarSize; ?>px;
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid #e3ebf5;
  display: block;
}
.astroid-header,
.astroid-header * { z-index: 1; }
#cbps-header { position: relative; z-index: 10; }
</style>
<div id="cbps-header">
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
