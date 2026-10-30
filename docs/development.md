# Development Guide

This guide covers setting up, building and changing OSMap Free. For how the code fits together, see [architecture.md](architecture.md). To add sitemap support for another component, see [plugins.md](plugins.md).

## Requirements

- PHP 7.4 or newer with the `json` and `simplexml` extensions
- Joomla 3.9 or newer
- [Phing](https://www.phing.info/), to build installable packages
- Local clones of the Joomlashack build and support repositories:
  - **ShackBuilder**: the Phing build scripts
  - **ShackFramework**: the `allediaframework` library that OSMap needs at runtime
  - **ShackInstaller**: the base installer script
  - **ShackDefaultFiles**: shared files bundled into the package
  - **ShackExtensionSupport**: the `osmylicensesmanager` system plugin

The minimum PHP and Joomla versions are set in the `<alledia>` block of `src/osmap.xml` (`<phpminimum>`, `<targetplatform>`). The installer enforces them.

## Building a package

1. Copy the property template and set the local paths:

   ```sh
   cp build.dist.properties build.properties
   ```

   ```properties
   builder.path=/path/to/ShackBuilder
   project.ShackInstaller.path=/path/to/ShackInstaller
   project.ShackDefaultFiles.path=/path/to/ShackDefaultFiles
   project.ShackFramework.path=/path/to/ShackFramework
   project.ShackExtensionSupport.path=/path/to/ShackExtensionSupport
   ```

   `build.properties` is git-ignored.

2. Run Phing from the repository root:

   ```sh
   phing
   ```

   `build.xml` loads `build.properties` and hands the build to `${builder.path}/src/build.xml`. ShackBuilder then:

   - copies `src/` into the package
   - bundles the extensions listed under `<relatedExtensions>` and `<include>` in `src/osmap.xml`
   - minifies the files listed under `<minify>`

   The finished zip goes to `packages/`, which is git-ignored. Optional settings in `build.properties` can move the package elsewhere (`project.movePackage`, `project.movePackageTo`) or run `php -l` over the assembled code (`builder.phplint=1`).

3. Install the zip through **System → Install → Extensions** on a test Joomla site.

### Working against a live site

The build isn't needed for day-to-day changes to PHP, templates or language files. You can copy or symlink the `src/` folders into a test site, using the same layout the installer creates:

| Source                                | Installed location                                   |
|---------------------------------------|------------------------------------------------------|
| `src/admin`                           | `administrator/components/com_osmap`                 |
| `src/site`                            | `components/com_osmap`                               |
| `src/media`                           | `media/com_osmap`                                    |
| `src/extensions/plg_osmap_<element>`  | `plugins/osmap/<element>`                            |

Language files are loaded from the component and plugin folders. The installer deletes any OSMap language files it finds in the core `language/` folders, so don't put any there.

Install the full package once first, so that the framework library, the database tables and the plugin records exist.

## Tests, coding standard and static analysis

Run these from the repository root. Each one installs the test tooling into `tests/vendor` first.

- `composer test` runs the [Codeception](https://codeception.com/) unit tests in
  `tests/Unit`.
- `composer lint` checks `src` and `tests` against PSR-12 with PHP_CodeSniffer.
- `composer lint:fix` automatically fixes the violations that phpcbf can correct.
- `composer lint:compat` checks `src` with PHPCompatibility for code that doesn't run on
  PHP 7.4 or later. The tools and IDEs run on newer PHP than the supported minimum.
- `composer analyse` runs [PHPStan](https://phpstan.org/) on `src` (see
  [Static analysis](#static-analysis)).
- `composer check` runs `lint`, `lint:compat`, `analyse` and `test` in that order,
  stopping at the first failure. Run it before committing.

Test dependencies live in `tests/composer.json`, separate from the shipped code. To update
them, run `composer --working-dir=tests update`, never a root `composer update`, and commit
`tests/composer.lock`. The tooling needs PHP 8.2 or newer to run.

### External checkouts

The tests and PHPStan need three checkouts that aren't part of this repository:

| Checkout          | Environment variable  | Property in `build.properties` |
|-------------------|-----------------------|--------------------------------|
| ShackFramework    | `SHACKFRAMEWORK_PATH` | `project.ShackFramework.path`  |
| ShackInstaller    | `SHACKINSTALLER_PATH` | `project.ShackInstaller.path`  |
| Joomla 4 or later | `JOOMLA_PATH`         | `project.Joomla.path`          |

`Tests\Support\Paths` looks for each one in the environment variable, then the property in
`build.properties`, then ShackBuilder's `global.properties`, the same as the build. The Joomla
checkout can be any Joomla source tree or site with `libraries/vendor` installed. The build
doesn't use it.

### Unit tests

OSMap's classes extend Joomla and ShackFramework classes, so `tests/bootstrap.php` loads them
from the external checkouts. It registers only the `Joomla\*` namespaces from the Joomla
checkout. Joomla's own composer autoloader would also load Joomla's copy of PHPUnit, which
Codeception runs on. Nothing
boots a Joomla application, so tests cover code that runs without one:

- `Tests\Support\Fixtures\TestFactory::setPimpleContainer()` replaces the container
  `Factory::getPimpleContainer()` returns, with only the services a test needs.
- `Tests\Support\Fixtures\SiteUri::setSite()` sets the site root that `Uri::root()` and
  `Uri::getInstance()` return. Call `SiteUri::reset()` in `tearDown()`.
- `Tests\Support\Fixtures\NullDateDatabase` stands in for the database where code only
  asks for `getNullDate()`.
- `Sitemap\Item` is built from a Joomla menu item, so `ItemTest` creates items without the
  constructor and sets the properties it needs.

`codeception.yml` turns every PHP error, deprecations included, into a test failure, so a
deprecation in `src` fails `composer test`. Codeception can't limit this to `src`, so Joomla's
own deprecations fail tests too when the PHP version is newer than the Joomla checkout
supports, such as Joomla 4 on PHP 8.4. To run the tests against another Joomla version, set
`JOOMLA_PATH`, using a PHP version that Joomla supports:

```sh
JOOMLA_PATH=/path/to/joomla4 php8.3 tests/vendor/bin/codecept run
```

### Static analysis

`phpstan.neon.dist` runs PHPStan at level 1 for PHP 7.4 to 8.5 against the external
checkouts. `tests/phpstan/bootstrap.php` defines the constants Joomla and `include.php`
create at runtime, and `tests/phpstan/stubs.php` declares Joomla 3 classes that newer
versions dropped. Analyse against Joomla 4 or later. Against Joomla 3, PHPStan reports the
Joomla 4+ classes that OSMap only uses behind version checks.

The `ignoreErrors` list covers reports that are wrong for Joomla code, each with a comment
explaining why. One example is `$this` and protected members in layouts, which Joomla
includes from inside the view. Fix new reports rather than adding to the list.

## Repository layout

```
build.xml, build.dist.properties   Phing entry point and the property template
composer.json                      Package metadata and the test, lint and analyse scripts
codeception.yml, phpstan.neon.dist  Codeception and PHPStan configuration
osmap.mwb                          MySQL Workbench model of the database schema
tests/
  composer.json                    Test tooling (Codeception, PHPStan, PHP_CodeSniffer)
  bootstrap.php, Unit.suite.yml    Codeception bootstrap and unit suite
  Unit/                            Unit tests
  Support/                         Paths helper and test fixtures
  phpstan/                         PHPStan bootstrap, stubs and checkout paths
src/
  osmap.xml                        Component manifest (version, files, related extensions, obsolete files)
  script.installer.php             Installer entry point
  admin/                           Administrator component
    include.php                    Bootstrap, required by every entry point and plugin
    library/Alledia/OSMap/         Namespaced core library (autoloaded)
    library/Installer/             Installer script implementation
    controllers/ models/ views/ tables/ form/ helpers/
    sql/                           Install, uninstall and update scripts
    language/
  site/                            Frontend component (xml, html, xsl and adminsitemapitems views)
  media/                           CSS and JS, with committed .min.* versions
  extensions/
    plg_osmap_joomla/              com_content integration, shipped with the component
    plg_osmap_demo/                Minimal example plugin, not shipped
```

## Coding conventions

- Follow PSR-12. Files that need exceptions use `// phpcs:disable` comments, for example the legacy non-namespaced MVC classes. `composer lint` checks this.
- Start every PHP file with the standard GPL file header (`@package OSMap`, copyright lines, license text), followed by `defined('_JEXEC') or die();`.
- New core classes go under `src/admin/library/Alledia/OSMap/` in the `Alledia\OSMap` namespace. They are autoloaded. MVC classes (controllers, models, views, tables) use Joomla's legacy naming, such as `OSMapModelSitemap` and `OSMapTableSitemap`.
- Prefer getting Joomla services through `Alledia\OSMap\Factory` (`getApplication()`, `getDatabase()`, `getDispatcher()`, …) or the Pimple container (`Factory::getPimpleContainer()->db`, `->input`, …). These calls work the same on Joomla 3 and Joomla 4+.
- Language strings go in the `en-GB` files. The `fr-FR` and `tr-TR` translations are kept in the repository as well.

### Joomla 3 and Joomla 4+

One codebase supports both:

- Layouts that differ come in pairs:
  - **View templates:** `default.php` for Joomla 4+ and `default.j3.php` for Joomla 3. On Joomla 3, ShackFramework's `TraitAllediaView::getVersionedLayoutName()` loads `<layout>.j3.php` when that file exists. Otherwise it falls back to the plain layout. On Joomla 4+, a list view with no items uses `emptystate.php` when it exists.
  - **Form field layouts** (`form/fields/layouts/osmap/`) use an underscore suffix, as in `menus_j3.php`. `Form\Field\TraitLayouts` loads that file on Joomla 3.

  When you change one file of a pair, check whether the other needs the same change.
- Where PHP code differs, it branches on `Joomla\CMS\Version::MAJOR_VERSION`.

### Front-end assets

`src/media/js/sitemapitems.js` is listed under `<minify>` and is minified during the build. The `.min.js` and `.min.css` files are committed, so regenerate and commit the minified file whenever you change its source.

### Free and Pro

OSMap Pro is built from a separate repository on top of this code. Free code finds Pro at runtime:

- `OSMAP_LICENSE` is `pro` when `library/Alledia/OSMap/Services/Pro.php` exists. `Factory::getPimpleContainer()` then registers `Services\Pro` instead of `Services\Free`.
- `Sitemap\Item` uses the `GenericPro` item adapter when that class exists.
- Plugins can call `Factory::getExtension('osmap', 'component')->isPro()`.

Don't remove these checks. When you change a signature in `Services\Free`, `Sitemap\*`, `ItemAdapter\*` or the plugin base classes, keep in mind that Pro extends them.

## Database changes

The schema lives in three places, and they must stay in sync:

1. `src/admin/sql/install/mysql/install.sql`: the full schema for new installs
2. `src/admin/sql/updates/mysql/<version>.sql`: the change for existing installs. Name the file after the release that introduces it. Joomla runs every update file newer than the recorded schema version.
3. `osmap.mwb`: the MySQL Workbench model, from which `install.sql` was generated

The installer (`Installer\OSMap\Free\AbstractScript::checkDatabaseSchema()`) also repairs known schema problems from older versions after an update. Add a check there if a change can't be expressed safely in plain SQL.

## Removing files on upgrade

When you delete or rename a file or folder, also add it to the `<obsolete>` block in `src/osmap.xml`. The installer then removes it from existing sites during upgrade. Retired plugins are listed there the same way, as `<extension>` entries.

## Branches and releases

- `development` is the integration branch. Branch from it and open pull requests against it.
- `main` holds released code. Each release is a single `Release vX.Y.Z` commit.

To prepare a release:

1. Update `<version>` and `<creationDate>` in `src/osmap.xml`.
2. Add any SQL update file named for that version.
3. Build the package with `phing` and test installing it fresh and as an upgrade on Joomla 3 and Joomla 4+.

## Debugging

- **XML sitemap:** turn on the **Debug** option on the sitemap's menu item. The output is then sent as `text/plain` with line breaks between entries.
- **HTML sitemap:** the **Debug** menu item option shows each item's UID, full, raw and original links, modified date, level, whether it's a duplicate, whether robots can see it, and its adapter class.
- **Logging:** errors go to Joomla's log folder as `com_osmap.errors.php`.
- **Memory:** content plugins call `Plugin\Base::checkMemory()` to stop with a readable message before PHP runs out of memory. You can change the limit with the component's **Max memory** option (`max_memory`).
