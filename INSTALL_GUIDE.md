# SCC User Header — Installation Guide

## Install
1. **Extensions → Manage → Install** → upload `mod_sccuserheader.zip`
2. **Content → Site Modules → New** → "SCC User Header"
3. Set:
   - **Position:** `top-header-navbar`
   - **Access:** Registered
   - **Menu Assignment:** On all pages
4. Avatar size dropdown: **32px** (default) — options 16/24/32/36/40
5. Save

## Header Menu Changes
| Item | Action |
|------|--------|
| Login → Access = Guest | Hides for logged-in users |
| Sign up → Access = Guest | Optional |
| Profile icon (Dashboard) → Unpublish | Module replaces it |
| {module 423} → Delete | Obsolete |

## Key URLs
- Profile: `https://simcoecurlingclub.ca/scc-profile`
- Forgot Login: `https://simcoecurlingclub.ca/scc-forgot-login`

## Template Position
The module renders in the Astroid Layout Builder position `top-header-navbar`.
On pages where this position is not available (e.g., Club Calendar page),
the CB Login module's `sccard_logout` layout override handles the avatar.

## Files
- `mod_sccuserheader.zip` — installable module
- `scc_user_header_inline.php` — template snippet (alternative deployment)
- `scc-user-header-overrides.zip` — template override files
