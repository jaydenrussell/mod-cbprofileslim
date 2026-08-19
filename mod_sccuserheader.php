<?php
/**
 * SCC User Header — Standalone Module (v1.4.2)
 *
 * Outputs: Display Name [avatar] for logged-in users, nothing for guests.
 * Works standalone — no dependency on sccard/cblogin module being on page.
 * Top-level try/catch prevents any error from becoming a 500.
 *
 * @version 1.4.3
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

try {

$user = Factory::getUser();
if ($user->guest) {
    return;
}

require_once __DIR__ . '/helper.php';

$profileUrl   = isset($params) ? $params->get('profile_url', 'https://simcoecurlingclub.ca/scc-profile') : 'https://simcoecurlingclub.ca/scc-profile';
$avatarSize   = isset($params) ? (int) $params->get('avatar_size', 32) : 32;
$displayName  = ModSccUserHeaderHelper::getDisplayName((int) $user->id) ?: $user->get('name');
$avatarUrl    = ModSccUserHeaderHelper::getAvatar((int) $user->id, $avatarSize, (bool) (isset($params) ? $params->get('avatar_db_fallback', 0) : 0));

$containerPadding = isset($params) ? $params->get('container_padding', '0 0 0 0') : '0 0 0 0';
$containerMargin  = isset($params) ? $params->get('container_margin', '0') : '0';

$avatarAlign = isset($params) ? $params->get('avatar_align', 'top') : 'top';
switch ($avatarAlign) {
    case 'none':   $alignTransform = 'none'; break;
    case 'center': $alignTransform = 'translateY(50%)'; break;
    case 'bottom': $alignTransform = 'translateY(100%)'; break;
    case 'top':
    default:       $alignTransform = 'translateY(50%)'; break;
}

?>
<style>
#scc-user-header {
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
#scc-user-header .scc-header-link {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
}
#scc-user-header .scc-name {
  line-height: 1.2;
  white-space: nowrap;
}
#scc-user-header .scc-avatar-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: calc(<?php echo $avatarSize; ?>px + 6px);
  height: calc(<?php echo $avatarSize; ?>px + 6px);
  border-radius: 50%;
  border: 3px solid #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  overflow: visible;
  z-index: 10;
  transform: <?php echo $alignTransform; ?>;
}
#scc-user-header .scc-avatar {
  width: <?php echo $avatarSize; ?>px;
  height: <?php echo $avatarSize; ?>px;
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid #e3ebf5;
  display: block;
}
.astroid-header,
.astroid-header * { z-index: 1; }
#scc-user-header { position: relative; z-index: 10; }
</style>
<div id="scc-user-header">
  <a href="<?php echo htmlspecialchars($profileUrl); ?>" class="scc-header-link">
    <span class="scc-name"><?php echo htmlspecialchars($displayName); ?></span>
    <span class="scc-avatar-wrap">
      <?php if ($avatarUrl): ?>
      <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="<?php echo htmlspecialchars($displayName); ?>" class="scc-avatar" />
      <?php endif; ?>
    </span>
  </a>
</div>
<?php
} catch (\Throwable $e) {
    // Never output anything on error; prevents 500.
}
