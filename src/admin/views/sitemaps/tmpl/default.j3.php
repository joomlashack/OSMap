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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die();

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('formbehavior.chosen', 'select');

$container = Factory::getPimpleContainer();

$baseUrl   = $container->router->sanitizeURL(Uri::root());
$listOrder = $this->state->get('list.ordering');
$listDir   = $this->state->get('list.direction');
?>
<form action="<?php echo Route::_('index.php?option=com_osmap&view=sitemaps'); ?>"
      method="post"
      name="adminForm"
      id="adminForm">
    <div id="j-sidebar-container" class="span2">
        <?php echo $this->sidebar; ?>
    </div>

    <div id="j-main-container" class="span10">
        <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

        <?php if (empty($this->items)) : ?>
            <div class="alert alert-no-items">
                <?php echo Text::_('COM_OSMAP_NO_MATCHING_RESULTS'); ?>
            </div>

        <?php else : ?>
            <table class="adminlist table table-striped" id="sitemapList">
                <thead>
                <tr>
                    <th style="width: 1%">
                        <?php echo HTMLHelper::_('grid.checkall'); ?>
                    </th>

                    <th style="width: 1%; min-width:55px" class="nowrap center">
                        <?php
                        echo HTMLHelper::_(
                            'searchtools.sort',
                            'COM_OSMAP_HEADING_STATUS',
                            'sitemap.published',
                            $listDir,
                            $listOrder
                        );
                        ?>
                    </th>

                    <th class="title">
                        <?php
                        echo HTMLHelper::_(
                            'searchtools.sort',
                            'COM_OSMAP_HEADING_NAME',
                            'sitemap.name',
                            $listDir,
                            $listOrder
                        ); ?>
                    </th>

                    <?php
                    $editLinksWidth = empty($this->languages) ? '63' : '130';
                    $editLinksClass = empty($this->languages) ? 'center' : '';
                    ?>
                    <th style="width: 8%; min-width: <?php echo $editLinksWidth . 'px'; ?>"
                        class="<?php echo $editLinksClass; ?>">
                        <?php echo Text::_('COM_OSMAP_HEADING_SITEMAP_EDIT_LINKS'); ?>
                    </th>

                    <th style="width: 260px" class="center">
                        <?php echo Text::_('COM_OSMAP_HEADING_SITEMAP_LINKS'); ?>
                    </th>

                    <th style="width: 8%" class="nowrap center">
                        <?php echo Text::_('COM_OSMAP_HEADING_NUM_LINKS'); ?>
                    </th>

                    <th style="width: 1%" class="nowrap">
                        <?php
                        echo HTMLHelper::_(
                            'searchtools.sort',
                            'COM_OSMAP_HEADING_ID',
                            'sitemap.id',
                            $listDir,
                            $listOrder
                        ); ?>
                    </th>
                </tr>
                </thead>

                <tbody>
                <?php
                foreach ($this->items as $i => $this->item) :
                    $editLink = Route::_('index.php?option=com_osmap&view=sitemap&layout=edit&id=' . $this->item->id);
                    ?>
                    <tr class="<?php echo 'row' . ($i % 2); ?>">
                        <td class="center">
                            <?php echo HTMLHelper::_('grid.id', $i, $this->item->id); ?>
                        </td>

                        <td class="center">
                            <div class="btn-group">
                                <?php
                                echo HTMLHelper::_(
                                    'jgrid.published',
                                    $this->item->published,
                                    $i,
                                    'sitemaps.'
                                );

                                $defaultAttribs = [
                                    [
                                        'onclick'             => $this->item->is_default
                                            ? 'javascript:void(0);'
                                            : "return listItemTask('cb{$i}','sitemap.setAsDefault')",
                                        'class'               => 'btn btn-micro hasTooltip',
                                        'data-original-title' => Text::_('COM_OSMAP_SITEMAP_IS_DEFAULT_DESC')
                                    ]
                                ];
                                echo HTMLHelper::_(
                                    'link',
                                    '#',
                                    sprintf(
                                        '<span class="icon-%s"></span>',
                                        $this->item->is_default ? 'featured' : 'unfeatured'
                                    ),
                                    $defaultAttribs
                                );
                                ?>
                            </div>
                        </td>

                        <td class="nowrap">
                            <?php echo HTMLHelper::_('link', $editLink, $this->escape($this->item->name)); ?>
                        </td>

                        <td class="nowrap <?php echo $editLinksClass; ?>">
                            <?php echo $this->loadTemplate('editlinks'); ?>
                        </td>

                        <td class="nowrap center osmap-links">
                            <?php echo $this->loadTemplate('previews'); ?>
                        </td>

                        <td class="center">
                            <?php echo number_format($this->item->links_count); ?>
                        </td>

                        <td class="center">
                            <?php echo (int)$this->item->id; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <input type="hidden" name="task" value=""/>
    <input type="hidden" name="boxchecked" value="0"/>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
