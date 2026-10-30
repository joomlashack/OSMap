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

namespace Tests\Support;

/**
 * Finds the external checkouts the tests and static analysis need. Each path
 * comes from an environment variable, then project.<Name>.path in build.properties,
 * then ShackBuilder's global.properties, the same as the build.
 */
class Paths
{
    /**
     * @var ?string[]
     */
    protected static ?array $properties = null;

    /**
     * @return string
     */
    public static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * @return string
     */
    public static function framework(): string
    {
        return static::find(
            'SHACKFRAMEWORK_PATH',
            'project.ShackFramework.path',
            null,
            '/src/vendor/autoload.php',
            'ShackFramework'
        );
    }

    /**
     * @return string
     */
    public static function installer(): string
    {
        return static::find(
            'SHACKINSTALLER_PATH',
            'project.ShackInstaller.path',
            null,
            '/src/library/Installer',
            'ShackInstaller'
        );
    }

    /**
     * @return string
     */
    public static function joomla(): string
    {
        return static::find(
            'JOOMLA_PATH',
            'project.Joomla.path',
            null,
            '/libraries/src/Factory.php',
            'Joomla'
        );
    }

    /**
     * @param string  $envName
     * @param string  $property
     * @param ?string $default
     * @param string  $check    Path inside the checkout that must exist
     * @param string  $title
     *
     * @return string The checkout folder, without a trailing slash
     */
    protected static function find(
        string $envName,
        string $property,
        ?string $default,
        string $check,
        string $title
    ): string {
        $path = getenv($envName) ?: (static::getProperties()[$property] ?? $default);
        $path = rtrim((string)$path, '/');

        if ($path === '' || file_exists($path . $check) == false) {
            fwrite(
                STDERR,
                sprintf(
                    "%s not found at %s\nSet %s or %s in build.properties\n",
                    $title,
                    $path . $check,
                    $envName,
                    $property
                )
            );
            exit(1);
        }

        return $path;
    }

    /**
     * @return string[]
     */
    protected static function getProperties(): array
    {
        if (static::$properties === null) {
            static::$properties = static::parse(static::root() . '/build.properties');

            if (isset(static::$properties['builder.path'])) {
                static::$properties += static::parse(
                    rtrim(static::$properties['builder.path'], '/') . '/global.properties'
                );
            }
        }

        return static::$properties;
    }

    /**
     * @param string $file
     *
     * @return string[]
     */
    protected static function parse(string $file): array
    {
        if (is_file($file)) {
            return parse_ini_file($file, false, INI_SCANNER_RAW) ?: [];
        }

        return [];
    }
}
