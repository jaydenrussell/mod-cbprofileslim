# Joomla Profile Slim Display (`mod_profileslim`)

Standalone **Joomla 3 module** that displays the **logged-in user's display name and
avatar** in the site's top header navbar.

It runs entirely on **Joomla's built-in profile system** — the display name, the avatar,
and the profile link are all resolved natively. No third-party profile extension is
required for anything the module does.

**Community Builder is optional.** When CB is installed, the module is *aware* of it and
uses only CB's canonical "View Profile" menu item for the profile link (see
"[Community Builder](#community-builder-optional)" below). Community Builder is a
compatibility layer, never a requirement.

## Why this exists

Joomla's own login module cannot show the name and avatar inline in a header bar. This
module is standalone: it works on every page (articles, calendar, anywhere) without
depending on another profile extension being loaded on the page.

## Install

1. **Extensions → Manage → Install**, upload `mod_profileslim.zip`.
2. Place the module in your header navbar position (e.g. `topbar-2` on Astroid templates,
   such as `tpl_jdseattle`).
3. Clear Joomla cache.

> **Upgrading from `mod_cbprofileslim` (v1.9.2 and earlier)?** See
> "[Upgrading](#upgrading-from-mod_cbprofileslim)" below — it is non-breaking.

## How the avatar works

- **Joomla profile system (default).** The avatar comes from the `avatar` profile field
  in Joomla's `#__user_profiles` table; several common keys are recognised
  (`avatar`, `profile.avatar`, `user.avatar`, `avatar_url`, `profile_picture`).
- **DB fallback is opt-in.** A module parameter `avatar_db_fallback` (default **No**)
  adds a direct `#__user_profiles` query when the profile API returns nothing.
- The display name uses the user's Joomla `name`, falling back to `username`.
- The default profile link is Joomla's native profile view
  (`index.php?option=com_users&view=profile&id=X`); it can be overridden with the
  `profile_url` module parameter (absolute http(s) or safe site-relative path).
- **Avatar storage requirement (hard constraint).** `sanitizeAvatarUrl()` only accepts a
  **relative path** under your configured avatar base directory — a flat filename
  (`383_abc.jpg`) **or** a subfolder path (`sub/dir/x.png`) — and, when one is returned,
  a same-site absolute URL (the foreign host is stripped to a same-origin relative
  path). It **rejects** foreign/absolute hosts, `javascript:`/`data:` and any other
  scheme, protocol-relative (`//`), backslashes, `..` path traversal, and any unsafe
  character; rejected values render the avatar blank. Do **not** relax the validator —
  it is intentional hardening.

### Community Builder (optional)

Only the profile **link** is CB-aware. When CB's files are present on the site, the
module reuses the canonical "View Profile" menu item resolution (the same
`SccCbMenuResolver` shipped by the `cblogin-modern-blue` template) so the profile link
follows CB's routing — this also covers CB's interception of Joomla profile links.
Name and avatar continue to come from Joomla's profile system. If no accessible CB
"View Profile" menu item exists, the module falls back to the configured or native
Joomla profile link and logs a warning.

## Upgrading from `mod_cbprofileslim`

v1.10.0 renamed the module element from `mod_cbprofileslim` to `mod_profileslim` so the
"CB" prefix no longer implies Community Builder is the primary function.

- **Existing installs update automatically.** Joomla's update channel matches the
  **update feed entry** against the **installed element**, so the repository keeps two
  feeds for the same release zip:
  - `update.xml` is the URL older installs are hardwired to (GitHub redirects it here
    after the repository rename) and advertises the zip under the legacy element
    `mod_cbprofileslim`. Joomla therefore **offers v1.10.0 in Extensions → Update** to
    every `mod_cbprofileslim` install from v1.9.2 back to earlier versions.
  - `update-profileslim.xml` is registered by this manifest going forward, so once a
    site runs `mod_profileslim` (fresh install **or** this upgrade) later versions
    keep arriving through Extensions → Update.
- **Installing the offered update (or the zip manually via Extensions → Manage →
  Install) is non-breaking.** The bundled `script.php`
  (`ModProfileslimInstallerScript`) migrates an existing `mod_cbprofileslim` module
  instance automatically:
  - parameters, title, position, ordering, published/access state, language, and
    page (menu) assignments are copied to the new `mod_profileslim` module, and
  - the legacy `mod_cbprofileslim` instance, its extension record, the
    `modules/mod_cbprofileslim` directory, and the stale legacy update site are
    removed.

  You end up with exactly one module, configured exactly as before.

## Updates

The module registers a Joomla update server (`update-profileslim.xml` on GitHub).
After installing once, **Extensions → Update** will offer newer versions, verified by
the SHA256 checksum in the feed. See "[Upgrading from
`mod_cbprofileslim`](#upgrading-from-mod-cbprofileslim)" for how the legacy feed keeps
pre-v1.10.0 installs on the same release path.

## Version history

| Version | Notes |
|---------|-------|
| 1.10.0 | Element renamed `mod_cbprofileslim` → `mod_profileslim` with automatic non-breaking migration; naming and docs repositioned Joomla-first with CB optional |
| 1.9.x  | Canonical CB menu-link resolution when CB is installed; loud failure handling; single-query avatar lookup; profile link falls back to native Joomla |
| 1.8.x  | Moved to Joomla's built-in profile system for name, avatar and link; display name became "Joomla Profile Slim Display" |
| 1.7.x–1.5.x | Security hardening, URL/CSS sanitizers, and the update channel work under the earlier `mod_cbprofileslim` element |

## License

GNU General Public License v2 or later.