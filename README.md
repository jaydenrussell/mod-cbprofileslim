# mod_sccuserheader — SCC User Header (Joomla 3 Module)

Displays the logged-in user's **display name + avatar** in the site's top header
navbar. Works as a standalone module — it does **not** depend on any other module
(e.g. the CB Login / `sccard` module) being published on the page.

## Features

- Shows display name (Community Builder `typename` → Joomla name fallback)
- Shows avatar image (CB field API, with a DB fallback to `#__comprofiler`)
- Fully standalone — no dependency on `sccard` / CB Login module
- Guarded with top-level `try/catch` so it can never throw a 500
- Joomla 3.x compatible (`extension type="module" version="3.0"`)

## Installation

1. In Joomla Administration: **Extensions → Install**, upload `mod_sccuserheader.zip`.
2. Publish the module to your header position (e.g. `topbar-2` on Astroid `tpl_jdseattle`).
3. Set **Profile URL**, **Avatar Size**, **Avatar Alignment**, padding/margin in the
   module's options.
4. Clear Joomla cache after installing/updating.

> **Note:** The avatar uses a direct DB query fallback to `#__comprofiler` keyed on
> `user_id`. Community Builder's `getField('avatar')` only renders reliably when CB's
> fieldtype renderer is loaded (i.e. on a CB page or when a CB module is present). The
> DB fallback guarantees the image loads on every page.

## Repository layout

```
mod_sccuserheader/
├── mod_sccuserheader.php      # Module entry point (renders name + avatar)
├── mod_sccuserheader.xml      # Joomla install manifest
├── helper.php                 # ModSccUserHeaderHelper (name + avatar logic)
├── index.html                 # Directory placeholder
├── language/en-GB/            # Language strings
├── mod_sccuserheader.zip      # Installable package (built from the above)
├── html/mod_cblogin/          # sccard override — the proven avatar reference
│   ├── sccard.php
│   └── sccard_logout.php
├── INSTALL_GUIDE.md
├── POSITIONING_GUIDE.md
└── FIX_AVATAR_URL.md
```

## Version history

| Version | Notes |
|---------|-------|
| 1.2.0 | CB API init added for standalone operation |
| 1.2.1 | try/catch around CB API block |
| 1.2.2 | Top-level try/catch so module can't 500 the page |
| 1.2.3 | Removed `cbimport('cb.plugin.user')` (missing file fatal) |
| 1.2.4 | `loadPluginGroup('user')` wrapped in try/catch |
| 1.2.5 | Joomla name fallback for display name |
| 1.2.6 | Avatar uses `profile` view (master image, no missing-thumbnail 404) |
| 1.2.7 | Removed SVG/onerror fallbacks |
| 1.2.8 | Avatar DB query fallback (`#__comprofiler`, same as sccard) |
| 1.2.9 | CB field API primary + DB fallback |
| 1.3.0 | DB fallback uses `user_id`; keeps raw extension; profile_url param |
| 1.3.1 | Security cleanup: removed debug block, avatar path allowlist |
| 1.4.0 | Avatar DB fallback made opt-in (CB API only by default); `avatar_db_fallback` param |
| 1.4.1 | Robust CB API avatar extraction: initCbApi first, csv→html→property methods; cbimport cb.database |
| 1.4.2 | Audit cleanup: docblock version fix, htmlspecialchars on CSS params |

## License

GNU General Public License v2 or later.
