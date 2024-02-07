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
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die();

HTMLHelper::_('behavior.core');
HTMLHelper::_('bootstrap.tooltip', '.hasTooltip', ['placement' => 'bottom']);
HTMLHelper::_('bootstrap.popover', '.hasPopover', ['placement' => 'bottom']);

$function  = Factory::getApplication()->input->getString('function', 'osmapSelectSitemap');
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

    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <?php echo Text::_('COM_OSMAP_NO_MATCHING_RESULTS'); ?>
                    </div>

                <?php else : ?>
                    <table class="adminlist table table-sm">
                        <thead>
                        <tr>
                            <th scope="col">
                                <?php
                                echo HTMLHelper::_(
                                    'searchtools.sort',
                                    'COM_OSMAP_HEADING_NAME',
                                    'sitemap.name',
                                    $direction,
                                    $ordering
                                ); ?>
                            </th>

                            <th scope="col" class="w-1 text-nowrap text-center">
                                <?php
                                echo HTMLHelper::_(
                                    'searchtools.sort',
                                    'COM_OSMAP_HEADING_STATUS',
                                    'sitemap.published',
                                    $direction,
                                    $ordering
                                );
                                ?>
                            </th>

                            <th style="width: 8%" class="w-8 text-center text-nowrap ">
                                <?php echo Text::_('COM_OSMAP_HEADING_NUM_LINKS'); ?>
                            </th>

                            <th class="w-1 text-nowrap">
                                <?php
                                echo HTMLHelper::_(
                                    'searchtools.sort',
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
                                        null,
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

                                <td class="text-center">
                                    <div class="btn-group osmap-modal-status">
                                        <?php if ($item->published) : ?>
                                            <i class="icon-save"></i>
                                        <?php else : ?>
                                            <i class="icon-remove"></i>
                                        <?php endif; ?>

                                        <?php if ($item->is_default) : ?>
                                            <i class="icon-star"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td class="text-center">
                                    <?php echo (int)$item->links_count; ?>
                                </td>

                                <td class="text-center">
                                    <?php echo (int)$item->id; ?>
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
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
