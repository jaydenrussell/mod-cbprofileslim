# SCC User Header — Positioning Guide

## Module Position
- **top-header-navbar** in Astroid Layout Builder
- This is where the header menu lives; the user avatar replaces the profile icon

## Template Style Issue
The Club Calendar page (`/club-events/club-calendar`) uses a **cloned template style** that
does NOT have the `top-header-navbar` position assigned. On those pages:

1. `scc_user_header_inline.php` does NOT render (no position)
2. Instead, `mod_cblogin` with `sccard_logout` layout renders in the sidebar
3. Both use the same CB avatar fetching logic (v1.1.5) for consistency

## Deploying Template Overrides
Files in `scc-user-header-overrides.zip` go to:
```
templates/tpl_jdseattle/
├── scc_user_header_inline.php
└── html/mod_cblogin/
    ├── scard.php        (login form)
    └── sccard_logout.php (logged-in card)
```

## Astroid Layout Builder
1. Edit the template style for the page
2. Go to Layout Builder → Header → top-header-navbar
3. Ensure the module position is added to the header row
4. Assign "SCC User Header" module to this position

## Mobile Considerations
- Avatar wrapper: 38px × 38px (32px avatar + 3px border × 2)
- `translateY(50%)` makes avatar overlap header boundary
- Recommended topbar height: 56px minimum for touch targets
- Use 24px avatar on tight headers (wrapper becomes ~36px)
