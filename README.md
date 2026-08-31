# Community Builder Profile Slim Display (`mod_cbprofileslim`)

Standalone **Joomla 3 module** that displays the **logged-in Community Builder user's
display name + avatar** in the site's top header navbar. It reads user data via the
**Community Builder API** (CB is required — that is where the name and avatar come from).

## Why this exists

Community Builder's own login module can show the avatar, but only when that module is
on the page. This module is **standalone**: it initialises the CB API itself and works on
every page (calendar, articles, anywhere) without depending on another CB module being present.

## Install

1. **Extensions → Install**, upload `mod_cbprofileslim.zip`.
2. Set the module position to your header navbar (e.g. `topbar-2` on the Astroid `tpl_jdseattle` template).
3. Clear Joomla cache.

## How the avatar works

- **Community Builder API first.** `initCbApi()` loads CB, then the avatar is resolved via
  `CBuser::getInstance()->getField('avatar', ...)` using three methods (csv → html-parse → property).
- **DB fallback is opt-in.** A module parameter `avatar_db_fallback` (default **No**) controls
  whether a direct `#__comprofiler` query is used when the CB API returns nothing. With it **off**,
  a blank avatar proves the CB API returned nothing on that page.
- The display name uses the CB `typename` field, falling back to the Joomla user name.
- **Avatar storage requirement (hard constraint).** `sanitizeAvatarUrl()` only accepts a
  **flat filename** (e.g. `383_abc.jpg`) stored under `/images/comprofiler/`. Absolute URLs,
  subdirectories, `..`, protocol-relative (`//`), or any non-filename characters are **rejected**
  and the avatar renders blank. If your CB stores avatars as absolute URLs or in subfolders,
  **fix the stored data first** — do not relax the validator. This is intentional hardening.

## Security model & update trust

- The Joomla update channel fetches `update.xml` from the immutable `update-info` release
  (not `master`), and verifies the downloaded zip against the SHA256 in that file.
- **This provides transport integrity only, not authenticity.** If the GitHub account is
  compromised, the `update-info` asset *and* its SHA256 can be swapped together, and every
  install would pull attacker code. Mitigations in place: branch protection + mandatory CODEOWNERS
  review + a required-approval `release` environment (see `.github/`). For untrusted distribution,
  additionally host `update.xml` + zips on infrastructure you control with restricted write access.

## Updates

The module registers a Joomla update server (`update.xml` on GitHub). After installing once,
**Extensions → Update** will offer newer versions, verified by SHA256 checksum.

## Version history

| Version | Notes |
|---------|-------|
| 1.2.0 | CB API init added for standalone operation |
| 1.2.1 | try/catch around CB API block |
| 1.2.2 | Top-level try/catch so module can't 500 the page |
| 1.2.3 | Removed `cbimport('cb.plugin.user')` (missing file fatal) |
| 1.2.4 | `loadPluginGroup('user')` wrapped in try/catch |
| 1.2.5 | Joomla name fallback for display name |
| 1.2.6 | Avatar uses `profile` view (master image) |
| 1.2.7 | Removed SVG/onerror fallbacks |
| 1.2.8 | Avatar DB query fallback (`#__comprofiler`) |
| 1.2.9 | CB field API primary + DB fallback |
| 1.3.0 | DB fallback uses `user_id`; keeps raw extension; profile_url param |
| 1.3.1 | Security cleanup: removed debug block, avatar path allowlist |
| 1.4.0 | Avatar DB fallback made opt-in (CB API only by default) |
| 1.4.1 | Robust CB API avatar extraction: initCbApi first, csv→html→property |
| 1.4.2 | Audit cleanup: docblock fix, htmlspecialchars on CSS params |
| 1.4.3 | Add update.xml + updateservers (Joomla self-update); author=jaydenrussell |
| 1.5.0 | **Renamed** SCC User Header → CB Profile Slim Display (`mod_cbprofileslim`); CSS `scc-` → `cbps-` |
| 1.5.1 | Security hardening: strict avatar URL sanitizer (blocks external/protocol-relative loads); JLog diagnostics replace silent catches; update channel pinned to immutable release asset (`update-info`) |
| 1.5.2 | MEDIUM fixes: validate `profile_url` (http(s)-only, XSS-safe) + CSS params; remove unused `Uri` import |
| 1.5.3 | Bump version; refresh `update.xml` SHA256 |
| 1.5.4 | Portable defaults: empty `profile_url` auto-links to CB profile; `avatar_base_path` made configurable |
| 1.5.5 | Rename to Community Builder Profile Slim Display; fix avatar path to accept CB subfolder/full paths |
| 1.5.6 | Update server migrated to `jaydenrussell.github.io/mod-cbprofileslim/update.xml` |
| 1.5.7 | Avatar sanitizer accepts same-site absolute URLs (fixes gallery/avatar not showing) |
| 1.5.8 | H1 base-path traversal fix; init-failure no longer wedges subsequent calls; L3/L4 cleanup; +CI tests |
| 1.6.0 | CSS escaping hardened; `!important` allowed in container styles; docs rewritten; CI matrix adds PHP 7.4; language keys completed; CHANGELOG and SECURITY.md added |

> Note: v1.5.0 is a **clean break** — the element name changed, so it will not auto-update
> from the old `mod_sccuserheader`. Uninstall the old module and install v1.5.0 fresh.

## License

GNU General Public License v2 or later.
