# SCC User Header Module — Fix History

## v1.2.0 — CB API Standalone Fix (current)

### Root cause (you were right)
The module only showed avatar when `sccard` (CB Login module) was also
loaded on the same page. When `sccard` was absent, the avatar showed SVG
fallback instead of the real image.

### Why it happened
CB's `getField('avatar', null, 'html', 'none', 'list', 0, false)` requires:
1. CB's plugin foundation (`plugin.foundation.php`) to be included
2. `cbimport('cb.html')` to load CB's HTML helpers
3. `$_PLUGINS->loadPluginGroup('user')` to load the avatar fieldtype plugin

When `sccard` is on the page, it calls all of these during its own rendering.
When `sccard` is NOT on the page, these aren't loaded, and `getField('avatar')`
fails silently — returning empty HTML with no `<img>` tag.

### Fix
The module now explicitly loads CB API before using `getField`:

```php
if (class_exists('CBuser') && !defined('SCC_CB_API_LOADED')) {
    include_once JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php';
    cbimport('cb.html');
    cbimport('cb.plugin.user');
    $GLOBALS['_PLUGINS']->loadPluginGroup('user');
    define('SCC_CB_API_LOADED', 1);
}
```

### Source
[Joomlapolis — Including CB API for usage outside of CB](https://www.joomlapolis.com/documentation/127-community-builder/279-tutorials/18357-including-cb-api-for-usage-outside-of-cb)
