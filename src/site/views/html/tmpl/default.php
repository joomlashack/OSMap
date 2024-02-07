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

use Alledia\OSMap\Factory;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die();

if ($this->params->get('use_css', 1)) :
    HTMLHelper::_('stylesheet', 'com_osmap/sitemap_html.min.css', ['relative' => true]);
endif;

if ($this->debug) :
    Factory::getApplication()->input->set('tmpl', 'component');
    HTMLHelper::_('stylesheet', 'com_osmap/sitemap_html_debug.min.css', ['relative' => true]);
endif;

if ($this->params->get('menu_text') !== null) :
    // We have a menu, so let's use its params to display the heading
    $pageHeading = $this->params->get('page_heading', $this->params->get('page_title'));
else :
    // We don't have a menu, so lets use the sitemap name
    $pageHeading = $this->sitemap->name;
endif;

$class = join(' ', array_filter([
    'osmap-sitemap',
    $this->debug ? 'osmap-debug' : '',
    $this->params->get('pageclass_sfx', '')
]));
?>

<div id="osmap" class="<?php echo $class; ?>">
    <!-- Heading -->
    <?php if ($this->params->get('show_page_heading', 1)) : ?>
        <div class="page-header">
            <h1><?php echo $this->escape($pageHeading); ?></h1>
        </div>
    <?php endif; ?>

    <!-- Description -->
    <?php if ($this->params->get('show_sitemap_description', 1)) : ?>
        <div class="osmap-sitemap-description">
            <?php echo $this->params->get('sitemap_description', ''); ?>
        </div>
    <?php endif; ?>

    <!-- Items -->
    <?php echo $this->loadTemplate('items'); ?>
</div>
