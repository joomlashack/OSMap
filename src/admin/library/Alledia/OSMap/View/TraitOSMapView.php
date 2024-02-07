<?php
/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2021-2024 Joomlashack.com. All rights reserved
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

namespace Alledia\OSMap\View;

use Alledia\OSMap\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

defined('_JEXEC') or die();

trait TraitOSMapView
{
    /**
     * Default admin screen title
     *
     * @param ?string $sub
     * @param string  $icon
     *
     * @return void
     */
    protected function setTitle(?string $sub = null, string $icon = 'osmap')
    {
        $title = Text::_('COM_OSMAP');
        if ($sub) {
            $title .= ': ' . Text::_($sub);
        }

        ToolbarHelper::title($title, $icon);
    }

    /**
     * Render the admin screen toolbar buttons
     *
     * @return void
     * @throws \Exception
     */
    protected function setToolBar()
    {
        $user = Factory::getUser();
        if ($user->authorise('core.admin', 'com_osmap')) {
            ToolbarHelper::preferences('com_osmap');
        }

        PluginHelper::importPlugin('osmap');

        Factory::getApplication()->triggerEvent(
            'osmapOnAfterSetToolBar',
            [
            strtolower(str_replace('OSMapView', '', $this->getName()))
        ]
        );
    }
}
