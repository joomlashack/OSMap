<?php

/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2007-2014 XMap - Joomla! Vargas - Guillermo Vargas. All rights reserved.
 * @copyright 2016-2025 Joomlashack.com. All rights reserved.
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

use Alledia\OSMap\Sitemap\Item;
use Joomla\CMS\Language\Language;
use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') or die();

/**
 * @var OSMapViewXml $this
 * @var string       $template
 * @var string       $layout
 * @var string       $layoutTemplate
 * @var Language     $lang
 * @var string       $filetofind
 */

$ignoreDuplicates = (int)$this->osmapParams->get('ignore_duplicated_uids', 1);

$printNodeCallback = function (Item $node) use ($ignoreDuplicates) {
    $display = !$node->ignore
        && $node->published
        && (!$node->duplicate || !$ignoreDuplicates)
        && $node->visibleForRobots
        && $node->parentIsVisibleForRobots
        && $node->visibleForXML
        && $node->isInternal
        && trim($node->fullLink) != ''
        && $node->hasCompatibleLanguage();

    if ($display && !empty($node->images)) {
        echo '<url>';
        echo sprintf('<loc><![CDATA[%s]]></loc>', $node->fullLink);

        foreach ($node->images as $image) {
            if (!empty($image->src)) {
                echo '<image:image>';
                echo '<image:loc><![CDATA[' . $image->src . ']]></image:loc>';
                echo empty($image->title)
                    ? '<image:title/>'
                    : '<image:title><![CDATA[' . $image->title . ']]></image:title>';

                if (!empty($image->license)) {
                    echo '<image:license><![CDATA[' . $image->license . ']]></image:license>';
                }

                echo '</image:image>';
            }
        }

        echo '</url>';
    }

    /*
     * Return true if there were no images
     * so any child nodes will get checked
     */
    return $display || empty($node->images);
};

echo $this->addStylesheet();

$attributes = [
    'xmlns'       => 'http://www.sitemaps.org/schemas/sitemap/0.9',
    'xmlns:image' => 'http://www.google.com/schemas/sitemap-image/1.1'
];
echo sprintf('<urlset %s>', ArrayHelper::toString($attributes));

$this->sitemap->traverse($printNodeCallback);

echo '</urlset>';
