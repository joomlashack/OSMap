<?php
/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2007-2014 XMap - Joomla! Vargas - Guillermo Vargas. All rights reserved.
 * @copyright 2016-2024 Joomlashack.com. All rights reserved.
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

namespace Alledia\OSMap\Services;

use Alledia\Framework\Profiler;
use Alledia\OSMap\Factory;
use Alledia\OSMap\Helper\Images;
use Alledia\OSMap\Router;
use Joomla\CMS\Uri\Uri;
use Pimple\Container as Pimple;
use Pimple\ServiceProviderInterface;

defined('_JEXEC') or die();

/**
 * Class Services
 *
 * Pimple services for OSMap. The container must be instantiated with
 * at least the following values:
 *
 * new \OSMap\Container(
 *    array(
 *       'configuration' => new Configuration($config)
 *    )
 * )
 *
 * @package OSMap
 */
class Free implements ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Pimple $pimple
     */
    public function register(Pimple $pimple)
    {
        $pimple['app'] = function () {
            return Factory::getApplication();
        };

        $pimple['db'] = function () {
            return Factory::getDbo();
        };

        $pimple['input'] = function () {
            return Factory::getApplication()->input;
        };

        $pimple['user'] = function () {
            return Factory::getUser();
        };

        $pimple['language'] = function () {
            return Factory::getLanguage();
        };

        $pimple['profiler'] = function () {
            return new Profiler();
        };

        $pimple['router'] = function () {
            return new Router();
        };

        $pimple['uri'] = function () {
            return new Uri();
        };

        $this->registerHelper($pimple);
    }

    /**
     * @param Pimple $pimple
     *
     * @return void
     */
    protected function registerHelper(Pimple $pimple)
    {
        $pimple['imagesHelper'] = function () {
            return new Images();
        };
    }
}
