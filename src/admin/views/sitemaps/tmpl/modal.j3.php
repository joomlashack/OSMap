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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die();

HTMLHelper::_('behavior.core');
HTMLHelper::_('bootstrap.tooltip', '.hasTooltip', ['placement' => 'bottom']);
HTMLHelper::_('bootstrap.popover', '.hasPopover', ['placement' => 'bottom']);

$function  = Factory::getApplication()->input->getString('function', 'jSelectSitemap');
$baseUrl   = Uri::root();
$ordering  = $this->escape($this->state->get('list.ordering'));
$direction = $this->escape($this->state->get('list.direction'));

$formAction = [
    'option'   => 'com_osmap',
    'view'     => 'sitemaps',
    'layout'   => 'modal',
    'tmpl'     => 'component',
    'function' => $function
];
?>
<form action="<?php echo Route::_('index.php?' . http_build_query($formAction)); ?>"
      method="post"
      name="adminForm"
      id="adminForm">

    <?php if (empty($this->items)) : ?>
        <div class="alert alert-no-items">
            <?php echo Text::_('COM_OSMAP_NO_MATCHING_RESULTS'); ?>
        </div>
    <?php else : ?>
        <div id="j-main-container">
            <table class="adminlist table table-striped" id="sitemapList">
                <thead>
                <tr>
                    <th class="title">
                        <?php
                        echo HTMLHelper::_(
                            'grid.sort',
                            'COM_OSMAP_HEADING_NAME',
                            'sitemap.name',
                            $direction,
                            $ordering
                        ); ?>
                    </th>

                    <th style="width: 1%; min-width:55px" class="nowrap center">
                        <?php
                        echo HTMLHelper::_(
                            'grid.sort',
                            'COM_OSMAP_HEADING_STATUS',
                            'sitemap.published',
                            $direction,
                            $ordering
                        );
                        ?>
                    </th>

                    <th style="width: 8%" class="nowrap center">
                        <?php echo Text::_('COM_OSMAP_HEADING_NUM_LINKS'); ?>
                    </th>

                    <th style="width: 1%" class="nowrap">
                        <?php
                        echo HTMLHelper::_(
                            'grid.sort',
                            'COM_OSMAP_HEADING_ID',
                            'sitemap.id',
                            $direction,
                            $ordering
                        ); ?>
                    </th>
                </tr>
                </thead>

                <tbody>
                <?php foreach ($this->items as $i => $item) :
                    ?>
                    <tr class="<?php echo 'row' . ($i % 2); ?>">
                        <td>
                            <?php
                            echo HTMLHelper::_(
                                'link',
                                'javascript:void(0);',
                                $this->escape($item->name),
                                [
                                    'style'   => 'cursor: pointer;',
                                    'onclick' => sprintf(
                                        "if (window.parent) window.parent.%s('%s', '%s');",
                                        $function,
                                        $item->id,
                                        $this->escape($item->name)
                                    )
                                ]
                            );
                            ?>
                        </td>

                        <td class="center">
                            <?php if ($item->published) : ?>
                                <span class="icon-publish"></span>
                            <?php else : ?>
                                <span class="icon-unpublish"></span>
                            <?php endif; ?>

                            <?php if ($item->is_default) : ?>
                                <span class="icon-featured"></span>
                            <?php endif; ?>
                        </td>

                        <td class="center">
                            <?php echo (int)$item->links_count; ?>
                        </td>

                        <td class="center">
                            <?php echo (int)$item->id; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <input type="hidden" name="task" value=""/>
    <input type="hidden" name="filter_order" value="<?php echo $ordering; ?>"/>
    <input type="hidden" name="filter_order_Dir" value="<?php echo $direction; ?>"/>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
