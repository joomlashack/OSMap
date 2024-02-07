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

use Alledia\OSMap\Button\DefaultButton;
use Joomla\CMS\Button\PublishedButton;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('bootstrap.tooltip');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_osmap&view=sitemaps'); ?>"
      method="post"
      name="adminForm"
      id="adminForm">

    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php
                echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]);

                if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span><span
                            class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>

                <?php else : ?>
                    <table class="table itemList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_OSMAP_SITEMAP_TABLE_CAPTION'); ?>,
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                            <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                        </caption>
                        <thead>
                        <tr>
                            <th class="w-1 text-center">
                                <?php echo HTMLHelper::_('grid.checkall'); ?>
                            </th>
                            <th scope="col" class="w-1 text-center">
                                <?php
                                echo HTMLHelper::_(
                                    'searchtools.sort',
                                    'JSTATUS',
                                    'sitemap.published',
                                    $listDirn,
                                    $listOrder
                                );
                                ?>
                            </th>
                            <th scope="col" class="w-1 text-center">
                                <?php echo Text::_('COM_OSMAP_HEADING_DEFAULT'); ?>
                            </th>
                            <th scope="col">
                                <?php
                                echo HTMLHelper::_(
                                    'searchtools.sort',
                                    'COM_OSMAP_HEADING_NAME',
                                    'sitemap.name',
                                    $listDirn,
                                    $listOrder
                                ); ?>
                            </th>
                            <?php
                            $editLinksWidth = empty($this->languages) ? '63' : '130';
                            $editLinksClass = empty($this->languages) ? 'text-center' : '';
                            ?>
                            <th scope="col"
                                class="m-8 <?php echo $editLinksClass; ?>"
                                style="min-width: <?php echo $editLinksWidth . 'px'; ?>">
                                <?php echo Text::_('COM_OSMAP_HEADING_SITEMAP_EDIT_LINKS'); ?>
                            </th>
                            <th scope="col" class="m-260 text-center">
                                <?php echo Text::_('COM_OSMAP_HEADING_SITEMAP_LINKS'); ?>
                            </th>

                            <th scope="col" class="m-8 text-nowrap text-center">
                                <?php echo Text::_('COM_OSMAP_HEADING_NUM_LINKS'); ?>
                            </th>
                            <th scope="col" class="w-5 d-none d-md-table-cell">
                                <?php
                                echo HTMLHelper::_(
                                    'searchtools.sort',
                                    'COM_OSMAP_HEADING_ID',
                                    'sitemap.id',
                                    $listDirn,
                                    $listOrder
                                );
                                ?>
                            </th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php
                        foreach ($this->items as $i => $this->item) :
                            $editLink = Route::_('index.php?option=com_osmap&view=sitemap&layout=edit&id=' . $this->item->id);
                            ?>
                            <tr class="<?php echo 'row' . ($i % 2); ?>">
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('grid.id', $i, $this->item->id); ?>
                                </td>

                                <td class="text-center">
                                        <?php
                                        echo (new PublishedButton())->render(
                                            (int)$this->item->published,
                                            $i,
                                            [
                                                'task_prefix' => 'sitemaps.',
                                                'id'          => 'state-' . $this->item->id
                                            ]
                                        );
                                        ?>
                                </td>

                                <td class="text-center">
                                    <?php
                                    echo (new DefaultButton())->render(
                                        (int)$this->item->is_default,
                                        $i,
                                        [
                                            'id' => 'state-' . $this->item->id
                                        ]
                                    );
                                    ?>
                                </td>

                                <td class="text-nowrap">
                                    <?php echo HTMLHelper::_('link', $editLink, $this->escape($this->item->name)); ?>
                                </td>

                                <td class="text-nowrap <?php echo $editLinksClass; ?>">
                                    <?php echo $this->loadTemplate('editlinks'); ?>
                                </td>

                                <td class="text-nowrap text-center osmap-links">
                                    <?php echo $this->loadTemplate('previews'); ?>
                                </td>

                                <td class="text-center">
                                    <?php echo (int)$this->item->links_count; ?>
                                </td>

                                <td class="text-center">
                                    <?php echo (int)$this->item->id; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value=""/>
    <input type="hidden" name="boxchecked" value="0"/>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
