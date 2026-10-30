<?php

/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2026 Joomlashack.com. All rights reserved
 * @license   https://www.gnu.org/licenses/gpl.html GNU/GPL
 *
 * This file is part of OSMap.
 *
 * OSMap is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * OSMap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OSMap.  If not, see <https://www.gnu.org/licenses/>.
 */

// phpcs:disable PSR1.Files.SideEffects

use Alledia\Framework\AutoLoader;
use Tests\Support\Paths;

require_once __DIR__ . '/Support/Paths.php';

$framework = Paths::framework();
$joomla    = Paths::joomla();

// Extension and Joomla 3/4 files exit unless loaded from within Joomla
define('_JEXEC', 1);
define('JPATH_PLATFORM', $joomla . '/libraries');

define('OSMAP_LICENSE', 'free');

// Pimple and the Alledia\Framework classes come from ShackFramework at runtime
require_once $framework . '/src/vendor/autoload.php';
require_once $framework . '/src/Framework/AutoLoader.php';
AutoLoader::register('Alledia\\Framework', $framework . '/src/Framework');

/*
 * OSMap classes extend Joomla classes, so they load from a Joomla checkout. Joomla's
 * own composer autoloader would also bring in its copy of PHPUnit, so only the
 * Joomla namespaces are registered, after the test autoloader. Joomla 3 registers
 * Joomla\CMS with its own loader instead of composer.
 */
$joomlaNamespaces = array_filter(
    require $joomla . '/libraries/vendor/composer/autoload_psr4.php',
    function (string $namespace): bool {
        return strpos($namespace, 'Joomla\\') === 0;
    },
    ARRAY_FILTER_USE_KEY
);
$joomlaNamespaces += ['Joomla\\CMS\\' => [$joomla . '/libraries/src']];

spl_autoload_register(function (string $class) use ($joomlaNamespaces) {
    foreach ($joomlaNamespaces as $namespace => $paths) {
        if (strpos($class, $namespace) === 0) {
            $file = str_replace('\\', '/', substr($class, strlen($namespace))) . '.php';
            foreach ($paths as $path) {
                if (is_file($path . '/' . $file)) {
                    require_once $path . '/' . $file;

                    return;
                }
            }
        }
    }
});
