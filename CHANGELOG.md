# Changelog

All notable changes to `mod_cbprofileslim` are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.9.0] - 2026-09-07

### Added
- **Canonical CB menu URL resolver**: Vendored `cbmenu.php` (collision-safe `SccCbMenuResolver` class, same as `cblogin-modern-blue`) into the package. The module profile link is now built through `SccCbMenuResolver::instance()->getProfileUrl($userId, $profileItemid)`, so the module emits the same canonical Community Builder SEF route (same `Itemid`, same `user` param) as the CB login template override. No hardcoded `cb-profile` aliases and no raw `/component/com_comprofiler/` URLs.
- **`profile_itemid` parameter**: Optional module param for the canonical "View Profile" menu Itemid (default `0` = auto-detect via `#__menu`). Added manifest field + language keys.
- **Graceful fallback**: If `cbmenu.php` is missing or `SccCbMenuResolver` can't load (e.g. other access level), the module falls back to the previous routing (`profile_url` param, then Joomla native profile link).

### Changed
- **Manifest**: `cbmenu.php` added to `<filename>` install list.
- Avatar/display-name data source and CSS/JHtml behavior unchanged.

## [1.8.9] - 2026-09-07

### Fixed
- **Fatal error (module never rendered)**: `mod_cbprofileslim.php` called the nonexistent method `ModProfileSlimHelper::joomlaProfileUrl()`. The top-level `try/catch` swallowed the resulting `Call to undefined method` fatal, so the module output nothing on the frontend. Now correctly calls `ModProfileSlimHelper::profileUrl()`.

## [1.8.8] - 2026-09-07

### Fixed
- **CB initialization**: `cbAvailable()` now properly initializes Community Builder by calling `include_once` the foundation file, `cbimport()`, and `loadPluginGroup('user')` before checking `class_exists('CBuser')`. Previously it only checked if the class existed without initializing, causing module failures when CB was installed.
- **CBuser namespace**: Removed backslash prefix from `\CBuser` references — changed to `CBuser` for proper global namespace resolution in PHP.

## [1.8.7] - 2026-09-07

### Changed
- **Dual avatar support**: `getAvatar()` now checks Community Builder first (if installed), then falls back to Joomla's `#__user_profiles` table. Checks multiple profile keys (`avatar`, `profile.avatar`, `user.avatar`, `avatar_url`, `profile_picture`) to support extensions that store avatars in Joomla's native profile system.
- **Dual profile URL support**: `profileUrl()` checks if CB is available → uses CB's `userProfileURL()` (which CB intercepts/redirects when CB is installed). Without CB, falls back to Joomla's native `index.php?option=com_users&view=profile&id=X`.
- **Dual display name support**: `getDisplayName()` checks CB first, then Joomla native `$user->get('name')` / `$user->get('username')`.
- **CB availability detection**: Added `cbAvailable()` method that checks for CB foundation file, initializes CB if present, and caches results per request. No longer uses a global `CB_LOADED_FLAG`.
- **DB fallback**: Avatar DB fallback now queries `#__user_profiles` with `LIKE '%avatar%'` to match multiple possible profile keys.

## [1.8.6] - 2026-09-07

### Changed
- **Rename**: Module renamed from "Community Builder Profile Slim Display" to "Joomla Profile Slim Display". All references to Community Builder stripped from code, XML manifests, and language files.
- **Profile links**: Profile URL now defaults to Joomla's built-in user profile (`index.php?option=com_users&view=profile&id=X`) instead of Community Builder's `cbProfileUrl()`.
- **Display names**: `getDisplayName()` now uses Joomla's native `$user->get('name')` / `$user->get('username')` instead of CB's `getField('typename')`.
- **Avatars**: `getAvatar()` now reads avatar data from Joomla's `#__user_profiles` table instead of CB's `#__comprofiler`. DB fallback queries `#__user_profiles` instead of `#__comprofiler`.
- **Removed**: All `initCbApi()`, `cbProfileUrl()`, `CB_LOADED_FLAG`, `siteHost()` methods and Community Builder API dependencies removed from `helper.php`.
- **CSS**: Renamed `cbprofileslim.css` to `profile-slim.css` with class prefixes updated from `cbps-` to `ps-`.
- **Avatar base path**: Default changed from `/images/comprofiler/` to `/images/`.

## [1.8.5] - 2026-09-07

### Fixed
- **Security (P0)**: `validateUrl()` now rejects single quotes (`'`) and URL-encoded dangerous characters to prevent href breakout XSS. All `htmlspecialchars()` calls upgraded to `ENT_QUOTES`.
- **Security (P2)**: `siteHost()` now validates `HTTP_HOST` against a domain regex to prevent header-injection-based same-origin bypass.
- **Security (P2)**: `initCbApi()` caches failed CB initialization via `$initFailed` flag to prevent per-request re-initialization attempts (resource exhaustion).
- **Security (P2)**: `validateBasePath()` now rejects `./` dot segments and normalizes path separators.
- **Security (P1)**: `validateCss()` rejects `!important` to prevent CSS property override injection.
- **Security (P1)**: CSS preload link now includes `type="text/css"` and `onerror` fallback to load stylesheet if preload fails.
- **Security (P1)**: Added `index.html` to `css/` directory to prevent directory listing.
- **Security (P0)**: Updated `php_minimum` to 8.0.0 (PHP 7.4 reached EOL Nov 2022). Updated `targetplatform` to Joomla 4.x.
- **UX (P1)**: Completed `@supports not (--cbps: initial)` fallback to cover all CSS properties for IE11 (width, height, position, z-index, transform, overflow, display).

## [1.8.1] - 2026-09-01

### Fixed
- **Bug**: CSS path now uses `\Joomla\CMS\Uri\Uri::base()` instead of `Uri::root()` for correct subdirectory support.
- **Performance**: Added `<link rel="preload">` hint for the module stylesheet to eliminate render-blocking.
- **Tests**: Rewrote `ModuleEntryPointTest.php` with output-buffering-based guest detection and fatal-error containment verification. Added `phpunit.xml.dist` testsuite registration for the entry-point suite.
- **CI**: Added `@supports not (--cbps: initial)` fallback in `css/cbprofileslim.css` for browsers that do not support CSS custom properties (e.g. IE11).
- **Quality**: Converted `// @since` inline comments to proper `@since` docblock tags in `initCbApi()` and `log()`.

## [1.8.0] - 2026-09-01

### Changed
- **Performance**: Extracted inline `<style>` block into external `css/cbprofileslim.css` for browser caching. Dynamic values (padding, margin, avatar size, alignment) are now passed via CSS custom properties (`--cbps-*`) set inline on `#cbps-header`. This reduces per-page inline CSS and allows the stylesheet to be cached across page loads.
- **Quality**: `JFactory::getDocument()->addStylesheet()` used to enqueue the module stylesheet in Joomla's document object.

## [1.7.1] - 2026-09-01

### Fixed
- **Bug**: `siteHost()` `class_exists` guard used an incorrect string literal (`\\\\Joomla\\\\CMS\\\\Uri\\\\Uri`), causing the Joomla `Uri` fallback to never activate. Now correctly checks `\Joomla\CMS\Uri\Uri`, restoring reliable same-site absolute avatar URL handling behind reverse proxies and in CLI contexts.
- **Quality**: Added `@since` tags to all `helper.php` methods for auditability and IDE support.
- **CI**: Added `phpunit.xml.dist` for IDE/CI consistency.
- **Tests**: Added `ModuleEntryPointTest.php` covering guest detection, logged-in user flow, and top-level error containment.

## [1.7.0] - 2026-09-01

### Changed
- **Bug**: `avatar_align` `top` option now maps to `translateY(0)` (uppermost). Previously `top` and `center` both produced `translateY(50%)`, so the two options were visually identical. `top`/`center`/`bottom` are now distinct and monotonic (0% / 50% / 100%).
- **Security**: `validateCss()` now rejects CSS function-call tokens (`expression(`/`url(`/`calc(`/`var(` — any letter followed by `(`). Closes the legacy `expression()` CSS-injection vector while preserving legitimate numeric/unit values. Added regression cases to `tests/SanitizerTest.php`.

## [1.6.0] - 2026-08-31

### Changed
- **Security**: Inline CSS now escapes `$avatarSize` with `(int)` cast and `$alignTransform` with `htmlspecialchars(..., ENT_QUOTES)` to prevent template-context injection if params are corrupted.
- **Security**: `validateCss()` allowlist extended to include `!important`, enabling legitimate CSS usage without weakening the injection barrier (`;`, `{}`, `url(` remain blocked).
- **Quality**: Added `@param` type annotations to all `helper.php` methods for IDE and static-analysis support.
- **CI**: GitHub Actions matrix now tests PHP 7.4 and 8.2, matching the declared `php_minimum` in `update.xml`.

### Added
- `CHANGELOG.md` — structured release notes.
- `SECURITY.md` — vulnerability disclosure process and update trust model.
- Missing language INI keys for `avatar_base_path` and `avatar_db_fallback`.

### Fixed
- Deprecated stale legacy documentation (`INSTALL_GUIDE.md`, `POSITIONING_GUIDE.md`, `FIX_AVATAR_URL.md`) that referenced the old `sccuserheader` module name.

## [1.5.8] - 2026-08-27

### Changed
- H1 base-path traversal fix: `validateBasePath()` now rejects `..` and `//` segments.
- Init-failure no longer wedges subsequent calls: `initCbApi()` only defines the load flag if `CBuser` actually exists.
- L3/L4 cleanup: minor logging and error-handling refinements.
- CI: Added GitHub Actions workflow (php-lint + PHPUnit sanitizer suite, 25 assertions).

## [1.5.7] - 2026-08-27

### Changed
- Avatar sanitizer accepts same-site absolute URLs (fixes gallery/avatar not showing when CB returns full site-rooted URLs).

## [1.5.6] - 2026-08-27

### Changed
- Update server migrated to `jaydenrussell.github.io/mod-cbprofileslim/update.xml`.

## [1.5.5] - 2026-08-27

### Changed
- Renamed to Community Builder Profile Slim Display (`mod_cbprofileslim`).
- Fixed avatar path to accept CB subfolder/full paths.

## [1.5.4] - 2026-08-27

### Changed
- Portable defaults: empty `profile_url` auto-links to CB profile.
- `avatar_base_path` made configurable.

## [1.5.3] - 2026-08-27

### Changed
- Version bump and `update.xml` SHA256 refresh.

## [1.5.2] - 2026-08-26

### Changed
- MEDIUM fixes: validate `profile_url` (http(s)-only, XSS-safe) + CSS params.
- Removed unused `Joomla\CMS\Uri\Uri` import.
- 25-assertion regex test pass (avatar/url/css sanitizers).

## [1.5.1] - 2026-08-26

### Changed
- F1: Strict `sanitizeAvatarUrl()` rejects scheme/protocol-relative/external paths.
- F1: Replace silent catches with JLog warnings (operational visibility).
- F1: Rename CB-loaded constant to `MOD_CBPROFILESLIM_CB_LOADED`.
- F2: Update channel pinned to immutable release asset.

## [1.5.0] - 2026-08-26

### Changed
- **BREAKING**: Renamed from `mod_sccuserheader` to `mod_cbprofileslim`.
- CSS namespace changed from `scc-` to `cbps-`.
- Clean break — old module must be uninstalled before installing v1.5.0.

## [1.4.3] - 2026-08-26

### Changed
- Add `update.xml` + `updateservers` for Joomla self-update.

## [1.4.2] - 2026-08-26

### Changed
- Audit cleanup: docblock fix, `htmlspecialchars` on CSS params.

## [1.4.1] - 2026-08-26

### Changed
- Robust CB API avatar extraction: `initCbApi` first, csv→html→property fallback chain.

## [1.4.0] - 2026-08-26

### Changed
- Avatar DB fallback made opt-in (CB API only by default).

## [1.3.1] - 2026-08-26

### Changed
- Security cleanup: removed debug block, avatar path allowlist.

## [1.3.0] - 2026-08-26

### Changed
- DB fallback uses `user_id`; keeps raw extension; `profile_url` param added.

## [1.2.9] - 2026-08-26

### Changed
- CB field API primary + DB fallback.

## [1.2.8] - 2026-08-26

### Changed
- Avatar DB query fallback (`#__comprofiler`).

## [1.2.7] - 2026-08-26

### Changed
- Removed SVG/onerror fallbacks.

## [1.2.6] - 2026-08-26

### Changed
- Avatar uses `profile` view (master image).

## [1.2.5] - 2026-08-26

### Changed
- Joomla name fallback for display name.

## [1.2.4] - 2026-08-26

### Changed
- `loadPluginGroup('user')` wrapped in try/catch.

## [1.2.3] - 2026-08-26

### Changed
- Removed `cbimport('cb.plugin.user')` (missing file fatal).

## [1.2.2] - 2026-08-26

### Changed
- Top-level try/catch so module can't 500 the page.

## [1.2.1] - 2026-08-26

### Changed
- try/catch around CB API block.

## [1.2.0] - 2026-08-26

### Changed
- CB API init added for standalone operation.
