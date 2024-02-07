<?php
/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2023-2024 Joomlashack.com. All rights reserved
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

use Alledia\OSMap\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die();

HTMLHelper::_('jquery.framework');
HTMLHelper::_('script', 'com_osmap/sitemapitems.min.js', ['relative' => true]);

$jsOptions = json_encode([
    'baseUri'     => Factory::getPimpleContainer()->uri::root(),
    'sitemapId'   => (int)$this->sitemap->id,
    'container'   => '#osmap-items-list',
    'language'    => $this->language,
    'frequencies' => HTMLHelper::_('osmap.frequencyList'),
    'priorities'  => HTMLHelper::_('osmap.priorityList'),
]);

$jTexts = [
    'COM_OSMAP_HOURLY',
    'COM_OSMAP_DAILY',
    'COM_OSMAP_WEEKLY',
    'COM_OSMAP_MONTHLY',
    'COM_OSMAP_YEARLY',
    'COM_OSMAP_NEVER',
    'COM_OSMAP_TOOLTIP_CLICK_TO_UNPUBLISH',
    'COM_OSMAP_TOOLTIP_CLICK_TO_PUBLISH',
];
foreach ($jTexts as $jText) {
    Text::script($jText);
}

Factory::getApplication()->getDocument()->addScriptDeclaration(
    ";jQuery(document).ready(function($) { new $.osmap.sitemapItems({$jsOptions}); });"
);
