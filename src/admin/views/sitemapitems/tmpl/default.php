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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die();

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.core');
HTMLHelper::_('behavior.keepalive');

$sitemapId = (int)$this->sitemap->id;

?>
    <h1>
        <?php echo Text::sprintf('COM_OSMAP_SITEMAPITEMS_HEADING', $this->escape($this->sitemap->name)); ?>
    </h1>

    <form action="<?php echo Route::_('index.php?option=com_osmap&view=sitemapitems&id=' . $sitemapId); ?>"
          method="post"
          name="adminForm"
          id="adminForm"
          class="form-validate">
        <div class="row-fluid">
            <div class="col-12">
                <div id="osmap-items-container">
                    <div class="osmap-loading">
                        <span class="icon-loop spin"></span>
                        <?php echo Text::_('COM_OSMAP_LOADING'); ?>
                    </div>

                    <div id="osmap-items-list"></div>
                </div>
            </div>
        </div>

        <input type="hidden" id="menus_ordering" name="jform[menus_ordering]" value=""/>
        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="id" value="<?php echo $sitemapId; ?>"/>
        <input type="hidden" name="update-data" id="update-data" value=""/>
        <input type="hidden" name="language" value="<?php echo $this->language; ?>"/>
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
<?php
echo $this->loadTemplate('script');
