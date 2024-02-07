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

namespace Alledia\OSMap\Button;

use Joomla\CMS\Button\ActionButton;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die();

class DefaultButton extends ActionButton
{
    protected function preprocess()
    {
        $this->addState(
            0,
            'sitemap.setAsDefault',
            'icon-unfeatured',
            Text::_('COM_OSMAP_SITEMAP_IS_DEFAULT_LABEL'),
            ['tip_title' => Text::_('COM_OSMAP_SITEMAP_IS_DEFAULT_DESC')]
        );
        $this->addState(
            1,
            'sitemap.setAsDefault',
            'icon-color-featured icon-star',
            Text::_('COM_OSMAP_SITEMAP_IS_DEFAULT_LABEL')
        );

        parent::preprocess();
    }
}
