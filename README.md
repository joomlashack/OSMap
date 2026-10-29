[![Joomlashack](https://www.joomlashack.com/images/logo_circle_small.png)](https://www.joomlashack.com)

OSMap
============

## About

OSMap is the easiest way to create a Joomla sitemap. It builds sitemaps from your Joomla menus, and plugins add the content behind each menu item, such as the articles in a category.

Each sitemap can be output as:

- an **XML sitemap** for search engines, with optional XSL styling for viewing in a browser
- an **HTML sitemap** for visitors
- a **Google News sitemap** with recently published items
- a **Google Images sitemap** with images found in content

Administrators choose which menus each sitemap includes and can set the priority, change frequency and published state of individual items.

OSMap Free includes the **OSMap - Joomla** plugin for core articles and categories (`com_content`). [OSMap Pro](https://www.joomlashack.com/joomla-extensions/osmap/) adds support for more components and other features.

## Requirements

- Joomla 3.9 or newer
- PHP 7.4 or newer

The last release compatible with Joomla 2.5 was v3.3.0.

## Installation

Download the latest release from [Joomlashack](https://www.joomlashack.com/joomla-extensions/osmap/) and install it through **System → Install → Extensions**. On a fresh install, OSMap creates a "Default Sitemap" that includes all of your site menus.

To publish a sitemap, create a menu item of type **OSMap Free → Sitemap - XML Format** or **OSMap Free → Sitemap - HTML Format**. You can also use the preview links on the OSMap **Sitemaps** page. Submit the XML sitemap URL to search engines and reference it in your `robots.txt`.

User documentation is at [joomlashack.com/docs/osmaps](https://www.joomlashack.com/docs/osmaps/start/).

## How to migrate from Xmap

1. Make a full backup of your site.
1. Do **not** uninstall Xmap.
1. Install the latest version of OSMap. It detects Xmap and migrates your data, including menus. If the migration succeeds, Xmap is disabled.
   * Any Xmap plugins are preserved.
1. Review the menu items, sitemaps and other relevant information.
   * Did the migration succeed? Let us, and the world, know :)
   * Did you have a problem? Restore your site from the backup and open an issue or support request with the details. Don't post private information in the public repository.

## Developer documentation

- [Development guide](docs/development.md): build setup, repository layout, conventions and releases
- [Architecture](docs/architecture.md): how sitemaps are generated, the data model, views and events
- [Writing an OSMap plugin](docs/plugins.md): adding sitemap support for other components

## How to contribute

* Fork the repository and clone it locally. Pull the latest changes.
* Create a new branch from **development** for your edits.
* Edit your files. See the [development guide](docs/development.md) for building and testing.
* Commit and push to your new branch.
* Create a pull request against the **development** branch.

## License

OSMap is released under the [GNU General Public License](https://www.gnu.org/licenses/gpl.html), version 2 or (at your option) any later version. See [LICENSE](LICENSE).
