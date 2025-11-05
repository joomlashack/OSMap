<?php

/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2021-2025 Joomlashack.com. All rights reserved
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

use Alledia\Framework\Factory;

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
defined('_JEXEC') or die();
// phpcs:enable PSR1.Files.SideEffects.FoundWithSymbols
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

trait TraitShack
{
    /**
     * @var ?bool
     */
    protected static ?bool $frameworkLoaded = null;

    /**
     * @return bool
     */
    protected function isPro(): bool
    {
        if ($this->isFrameworkLoaded()) {
            $license = Factory::getExtension('osmap', 'component');
            return $license->isPro();
        }

        return false;
    }

    /**
     * @return null
     */
    protected function isFrameworkLoaded()
    {
        if (static::$frameworkLoaded === null) {
            if (defined('ALLEDIA_FRAMEWORK_LOADED') == false) {
                $path = JPATH_SITE . '/libraries/allediaframework/include.php';
                if (is_file($path)) {
                    require_once $path;
                }
            }

            static::$frameworkLoaded = defined('ALLEDIA_FRAMEWORK_LOADED');
        }

        return static::$frameworkLoaded;
    }
}
