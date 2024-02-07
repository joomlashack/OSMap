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

defined('_JEXEC') or die();

$languages = $this->languages ?: [''];
foreach ($languages as $language) :
    $langCode = empty($language->sef) ? null : $language->sef;
    ?>
    <span class="osmap-link">
        <?php
        echo HTMLHelper::_(
            'link',
            $this->getLink($this->item, 'xml', $langCode),
            Text::_('COM_OSMAP_XML_LINK'),
            [
                'target' => '_blank',
                'title'  => Text::_('COM_OSMAP_XML_LINK_TOOLTIP', true),
                'class'  => 'hasTooltip'
            ]
        );
        ?>
        <span class="icon-new-tab"></span>
    </span>

    <span class="osmap-link">
        <?php
        echo HTMLHelper::_(
            'link',
            $this->getLink($this->item, 'html', $langCode),
            Text::_('COM_OSMAP_HTML_LINK'),
            [
                'target' => '_blank',
                'title'  => Text::_('COM_OSMAP_HTML_LINK_TOOLTIP', true),
                'class'  => 'hasTooltip'
            ]
        );
        ?>
        <span class="icon-new-tab"></span>
    </span>

    <span class="osmap-link">
        <?php
        echo HTMLHelper::_(
            'link',
            $this->getLink($this->item, 'news', $langCode),
            Text::_('COM_OSMAP_NEWS_LINK'),
            [
                'target' => '_blank',
                'title'  => Text::_('COM_OSMAP_NEWS_LINK_TOOLTIP', true),
                'class'  => 'hasTooltip'
            ]
        );
        ?>
        <span class="icon-new-tab"></span>
    </span>

    <span class="osmap-link">
        <?php
        echo HTMLHelper::_(
            'link',
            $this->getLink($this->item, 'images', $langCode),
            Text::_('COM_OSMAP_IMAGES_LINK'),
            [
                'target' => '_blank',
                'title'  => Text::_('COM_OSMAP_IMAGES_LINK_TOOLTIP', true),
                'class'  => 'hasTooltip'
            ]
        );
        ?>
        <span class="icon-new-tab"></span>
    </span>
    <br>
<?php endforeach;
