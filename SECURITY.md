# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.8.x   | :white_check_mark: |
| 1.7.x   | :white_check_mark: |
| 1.6.x   | :white_check_mark: |
| < 1.6.0 | :x:                |

## Reporting a Vulnerability

If you discover a security vulnerability in `mod_cbprofileslim`, please report it
responsibly via email to the repository owner ([@jaydenrussell](https://github.com/jaydenrussell)).
Do not open a public issue for security vulnerabilities.

Please include:
- A description of the vulnerability
- Steps to reproduce
- Affected versions
- Suggested fix (if any)

You will receive a response within 5 business days. Critical issues will be patched
and released as soon as possible.

## Update Trust Model

This extension uses Joomla's built-in update server. Updates are verified by SHA256
checksum as declared in `update.xml`. This provides **transport integrity only, not
authenticity**:

- If the GitHub account or the `update-info` release asset is compromised, both the
  zip and its SHA256 can be swapped together.
- Mitigations in place: branch protection, mandatory code-owner review, and
  required approval workflows on the `release` environment.

For higher assurance on production sites, consider:
1. Downloading the zip manually from GitHub Releases.
2. Verifying the SHA256 against a value published out-of-band (e.g., your own
   release notes or signed message).
3. Installing via **Extensions → Manage → Install** from the verified file.

## Security Hardening History

- **v1.5.1**: Strict avatar URL sanitizer; JLog diagnostics replace silent catches;
  update channel pinned to immutable release asset.
- **v1.5.2**: `validateUrl()` (http(s)-only) and `validateCss()` added; unused `Uri`
  import removed.
- **v1.5.8**: Base-path traversal fix in `validateBasePath()`; init-failure no longer
  caches a false negative.
- **v1.6.0**: CSS context escaping hardened; `!important` allowed in container styles;
  CI matrix covers PHP 7.4 and 8.2.
- **v1.7.0**: `validateCss()` rejects CSS function-call tokens (`expression(`/`url(`/`calc(`);
  `avatar_align` top/center made distinct.
- **v1.8.2**: `validateUrl()` rejects single quotes + URL-encoded dangerous chars; `htmlspecialchars` upgraded to `ENT_QUOTES`; `validateCss()` rejects `!important`; `validateBasePath()` rejects `./` segments; `siteHost()` validates `HTTP_HOST` against domain regex; `initCbApi()` caches failed initialization; CSS preload gets `type="text/css"` + `onerror` fallback; complete `@supports` IE11 fallback; `index.html` added to `css/`; PHP minimum bumped to 8.0; targetplatform updated to Joomla 4.x.
