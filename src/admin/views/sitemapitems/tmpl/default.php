<?php
/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2007-2014 XMap - Joomla! Vargas - Guillermo Vargas. All rights reserved.
 * @copyright 2016-2021 Joomlashack.com. All rights reserved.
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

use Alledia\OSMap;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die();

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.core');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('formbehavior.chosen', 'select');

$listFields = json_encode([
    'frequencies' => HTMLHelper::_('osmap.frequencyList'),
    'priorities'  => HTMLHelper::_('osmap.priorityList')
]);

$jscript = <<<JSCRIPT
;(function($) {
    $.osmap = $.extend({}, $.osmap);
    
    $.osmap.fields = {$listFields};
})(jQuery);
JSCRIPT;
OSMap\Factory::getDocument()->addScriptDeclaration($jscript);

HTMLHelper::_('script', 'com_osmap/sitemapitems.min.js', ['relative' => true]);

$container = OSMap\Factory::getPimpleContainer();
?>

<form action="<?php echo Route::_('index.php?option=com_osmap&view=sitemapitems&id=' . (int)$this->sitemapId); ?>"
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
    <input type="hidden" name="id" value="<?php echo $this->sitemapId; ?>"/>
    <input type="hidden" name="update-data" id="update-data" value=""/>
    <input type="hidden" name="language" value="<?php echo $this->language; ?>"/>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
<script>
    ;jQuery(function($) {
        $(function() {
            $.fn.osmap.loadSitemapItems({
                baseUri  : '<?php echo $container->uri::root(); ?>',
                sitemapId: '<?php echo $this->sitemapId; ?>',
                container: '#osmap-items-list',
                language : '<?php echo $this->language; ?>',
                lang     : {
                    'COM_OSMAP_HOURLY'                    : '<?php echo Text::_('COM_OSMAP_HOURLY'); ?>',
                    'COM_OSMAP_DAILY'                     : '<?php echo Text::_('COM_OSMAP_DAILY'); ?>',
                    'COM_OSMAP_WEEKLY'                    : '<?php echo Text::_('COM_OSMAP_WEEKLY'); ?>',
                    'COM_OSMAP_MONTHLY'                   : '<?php echo Text::_('COM_OSMAP_MONTHLY'); ?>',
                    'COM_OSMAP_YEARLY'                    : '<?php echo Text::_('COM_OSMAP_YEARLY'); ?>',
                    'COM_OSMAP_NEVER'                     : '<?php echo Text::_('COM_OSMAP_NEVER'); ?>',
                    'COM_OSMAP_TOOLTIP_CLICK_TO_UNPUBLISH': '<?php echo Text::_('COM_OSMAP_TOOLTIP_CLICK_TO_UNPUBLISH'); ?>',
                    'COM_OSMAP_TOOLTIP_CLICK_TO_PUBLISH'  : '<?php echo Text::_('COM_OSMAP_TOOLTIP_CLICK_TO_PUBLISH'); ?>'
                }
            });
        });
    });
</script>
