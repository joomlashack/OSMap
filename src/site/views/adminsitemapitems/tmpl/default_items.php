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
use Joomla\CMS\Version;

defined('_JEXEC') or die();


$frequncyOptions = HTMLHelper::_('osmap.frequencyList');
array_walk(
    $frequncyOptions,
    function (string &$text, string $value) {
        $text = HTMLHelper::_('select.option', $value, $text);
    }
);

$priorityOptions = array_map(
    function (float $priority) {
        return HTMLHelper::_('select.option', $priority, $priority);
    },
    HTMLHelper::_('osmap.priorityList')
);

$showItemUid       = $this->osmapParams->get('show_item_uid', 0);
$showExternalLinks = (int)$this->osmapParams->get('show_external_links', 0);
$items             = [];

$this->sitemap->traverse(
/**
 * @param object $item
 *
 * @return bool
 */
    function (object $item) use (&$items, &$showItemUid, &$showExternalLinks) {
        if (
            ($item->isInternal == false && $showExternalLinks === 0)
            || $item->hasCompatibleLanguage() == false
        ) :
            return false;
        endif;

        if ($showExternalLinks === 2) :
            // Display only in the HTML sitemap
            $item->addAdminNote('COM_OSMAP_ADMIN_NOTE_IGNORED_EXTERNAL_HTML');
        endif;

        // Add notes about sitemap visibility
        if ($item->visibleForXML == false) :
            $item->addAdminNote('COM_OSMAP_ADMIN_NOTE_VISIBLE_HTML_ONLY');
        endif;

        if ($item->visibleForHTML == false) :
            $item->addAdminNote('COM_OSMAP_ADMIN_NOTE_VISIBLE_XML_ONLY');
        endif;

        if ($item->visibleForRobots == false) :
            $item->addAdminNote('COM_OSMAP_ADMIN_NOTE_INVISIBLE_FOR_ROBOTS');
        endif;

        if ($item->parentIsVisibleForRobots == false) :
            $item->addAdminNote('COM_OSMAP_ADMIN_NOTE_PARENT_INVISIBLE_FOR_ROBOTS');
        endif;

        $items[] = $item;

        return true;
    },
    false,
    true
);

$count = count($items);
?>
    <table class="adminlist table table-striped" id="itemList">
        <thead>
        <tr>
            <th style="width: 1%;min-width:55px" class="text-center center">
                <?php echo Text::_('COM_OSMAP_HEADING_STATUS'); ?>
            </th>

            <th class="title">
                <?php echo Text::_('COM_OSMAP_HEADING_URL'); ?>
            </th>

            <th class="title">
                <?php echo Text::_('COM_OSMAP_HEADING_TITLE'); ?>
            </th>

            <th class="text-center center">
                <?php echo Text::_('COM_OSMAP_HEADING_PRIORITY'); ?>
            </th>

            <th class="text-center center">
                <?php echo Text::_('COM_OSMAP_HEADING_CHANGE_FREQ'); ?>
            </th>
        </tr>
        </thead>

        <tbody>
        <?php
        foreach ($items as $row => $item) : ?>
            <tr class="sitemapitem <?php echo 'row' . $row; ?> <?php echo ($showItemUid) ? 'with-uid' : ''; ?>"
                data-uid="<?php echo $item->uid; ?>"
                data-settings-hash="<?php echo $item->settingsHash; ?>">

                <td class="text-center center">
                    <div class="sitemapitem-published"
                         data-original="<?php echo $item->published ? '1' : '0'; ?>"
                         data-value="<?php echo $item->published ? '1' : '0'; ?>">

                        <?php
                        $class = $item->published ? 'publish' : 'unpublish';
                        $title = $item->published
                            ? 'COM_OSMAP_TOOLTIP_CLICK_TO_UNPUBLISH'
                            : 'COM_OSMAP_TOOLTIP_CLICK_TO_PUBLISH';
                        ?>

                        <span title="<?php echo Text::_($title); ?>"
                              class="hasTooltip icon-<?php echo $class; ?>">
                    </span>
                    </div>
                    <?php
                    if ($notes = $item->getAdminNotesString()) : ?>
                        <span class="icon-warning hasTooltip osmap-info" title="<?php echo $notes; ?>"></span>
                    <?php endif; ?>
                </td>

                <td class="sitemapitem-link">
                    <?php if ($item->level > 0) : ?>
                        <span class="level-mark"><?php echo str_repeat('—', $item->level); ?></span>
                    <?php endif;

                    if ($item->rawLink !== '#' && $item->link !== '#') :
                        if (Version::MAJOR_VERSION < 4) :
                            echo '<span class="icon-new-tab"></span>';
                        endif;

                        echo HTMLHelper::_(
                            'link',
                            $item->rawLink,
                            $item->rawLink,
                            [
                                'target' => '_blank',
                                'class'  => 'hasTooltip',
                                'title'  => $item->link,
                            ]
                        );

                    else :
                        echo sprintf('<span>%s</span>', $item->name ?? '');
                    endif;

                    if ($showItemUid) :
                        echo sprintf(
                            '<br><div class="small osmap-item-uid">%s: %s</div>',
                            Text::_('COM_OSMAP_UID'),
                            $item->uid
                        );
                    endif;
                    ?>
                </td>

                <td class="sitemapitem-name">
                    <?php echo $item->name ?? ''; ?>
                </td>

                <td class="text-center center">
                    <div class="sitemapitem-priority"
                         data-original="<?php echo $item->priority; ?>"
                         data-value="<?php echo sprintf('%03.1f', $item->priority); ?>">

                        <?php echo sprintf('%03.1f', $item->priority); ?>
                    </div>
                </td>

                <td class="text-center center">
                    <div class="sitemapitem-changefreq"
                         data-original="<?php echo $item->changefreq; ?>"
                         data-value="<?php echo $item->changefreq; ?>">

                        <?php echo Text::_('COM_OSMAP_' . strtoupper($item->changefreq)); ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div><?php echo Text::sprintf('COM_OSMAP_NUMBER_OF_ITEMS_FOUND', $count); ?></div>

<?php if (empty($count)) : ?>
    <div class="alert alert-warning">
        <?php echo Text::_('COM_OSMAP_NO_ITEMS'); ?>
    </div>
<?php endif;
