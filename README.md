# Static Cache plugin installation

[![Release](https://img.shields.io/github/v/release/franck-paul/staticCache)](https://github.com/franck-paul/staticCache/releases)
[![Date](https://img.shields.io/github/release-date/franck-paul/staticCache)](https://github.com/franck-paul/staticCache/releases)
[![Issues](https://img.shields.io/github/issues/franck-paul/staticCache)](https://github.com/franck-paul/staticCache/issues)
[![License](https://img.shields.io/github/license/franck-paul/staticCache)](https://github.com/franck-paul/staticCache/blob/master/LICENSE)

## Configuration

Add the following constants in your main config.php file:

* `DC_SC_CACHE_ENABLE` : set to false to disable caching system (default false)

Optionnally:

* `DC_SC_CACHE_DIR`    : full path to cache directory (default: dcstaticcache in your cache directory)

* `DC_SC_CACHE_BLOGS_ON` : list of blogs ID that should be cached (default: all blogs will be cached)
* `DC_SC_CACHE_BLOGS_OFF` : list of blogs ID that must not be cached

  Note: `DC_SC_CACHE_BLOGS_OFF` has higher priority than `DC_SC_CACHE_BLOGS_ON`

* `DC_SC_EXCLUDED_URL` : list of URL types excluded from cache

Note : preview and pagespreview URL types are always excluded

## Hint

If you want cache to be called before *any* connection to database, add a require statement to `_post_config.php` at the end of your configuration file.
