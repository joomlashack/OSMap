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

namespace Alledia\OSMap\View\Site;

use Alledia\OSMap\Factory;
use Alledia\OSMap\Sitemap\Standard;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

defined('_JEXEC') or die();


class AbstractList extends \Alledia\Framework\Joomla\View\Site\AbstractList
{
    /**
     * @var Standard
     */
    protected $sitemap = null;

    /**
     * @var Registry
     */
    protected $osmapParams = null;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var int
     */
    protected $showExternalLinks = 0;

    /**
     * @var int
     */
    protected $showMenuTitles = 1;

    /**
     * @var int
     */
    public $generalCounter = 0;

    /**
     * List of found items to render the sitemap
     *
     * @var array
     */
    protected $menus = [];

    /**
     * A list of last items per level. Used to identify the parent items
     *
     * @var array
     */
    protected $lastItemsPerLevel = [];

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->params            = Factory::getApplication()->getParams();
        $this->debug             = (bool)$this->params->get('debug', 0);
        $this->osmapParams       = ComponentHelper::getParams('com_osmap');
        $this->showExternalLinks = (int)$this->osmapParams->get('show_external_links', 0);
        $this->showMenuTitles    = (int)$this->params->get('show_menu_titles', 1);
    }

    /**
     * The display method
     *
     * @param string $tpl
     *
     * @return void
     * @throws \Exception
     */
    public function display($tpl = null)
    {
        $container = Factory::getPimpleContainer();

        $id = $container->input->getInt('id');

        $this->osmapParams = ComponentHelper::getParams('com_osmap');

        $this->sitemap = Factory::getSitemap($id);

        if (!$this->sitemap->isPublished) {
            throw new \Exception(Text::_('COM_OSMAP_MSG_SITEMAP_IS_UNPUBLISHED'), 404);
        }

        $app = Factory::getApplication();
        if ($title = $this->params->def('page_title')) {
            if ($app->get('sitename_pagetitles', 0) == 1) {
                $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);

            } elseif ($app->get('sitename_pagetitles', 0) == 2) {
                $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
            }
            $this->document->setTitle($title);
        }

        if ($description = $this->params->get('menu-meta_description')) {
            $this->document->setDescription($description);
        }

        if ($keywords = $this->params->get('menu-meta_keywords')) {
            $this->document->setMetaData('keywords', $keywords);
        }

        if ($robots = $this->params->get('robots')) {
            $this->document->setMetaData('robots', $robots);
        }

        parent::display($tpl);
    }

    /**
     * The callback called to print each node. Returns true if it was
     * able to print. False, if not.
     *
     * @param object $node
     *
     * @return bool
     */
    public function registerNodeIntoList(object $node): bool
    {
        $ignoreDuplicatedUIDs = (int)$this->osmapParams->get('ignore_duplicated_uids', 1);

        $display = !$node->ignore
            && $node->published
            && (!$node->duplicate || !$ignoreDuplicatedUIDs)
            && $node->visibleForHTML;

        if ($display && !$node->isInternal) {
            // Show external links if so configured
            $display = $this->showExternalLinks > 0;
        }

        if (!$node->hasCompatibleLanguage()) {
            $display = false;
        }

        if (!$display) {
            return false;
        }

        // Check if the menu was already registered and register if needed
        if ($node->level === 0 && !isset($this->menus[$node->menuItemType])) {
            $queueItem = (object)[
                'menuItemId'    => $node->menuItemId,
                'menuItemTitle' => $node->menuItemTitle,
                'menuItemType'  => $node->menuItemType,
                'level'         => -1,
                'children'      => []
            ];

            // Add the menu to the main list of items
            $this->menus[$node->menuItemType] = $queueItem;

            // Add this menu as the last one on the list of menus
            $this->lastItemsPerLevel[-1] = $queueItem;
        }

        // Instantiate the current item
        $queueItem           = (object)[];
        $queueItem->rawLink  = $node->rawLink;
        $queueItem->type     = $node->type;
        $queueItem->level    = $node->level;
        $queueItem->name     = $node->name;
        $queueItem->uid      = $node->uid;
        $queueItem->children = [];

        // Add debug information, if debug is enabled
        if ($this->debug) {
            $queueItem->fullLink         = $node->fullLink;
            $queueItem->link             = $node->link;
            $queueItem->modified         = $node->modified;
            $queueItem->duplicate        = $node->duplicate;
            $queueItem->visibleForRobots = $node->visibleForRobots;
            $queueItem->adapter          = get_class($node->adapter);
            $queueItem->menuItemType     = $node->menuItemType;
        }

        // Add this item to its parent children list
        $this->lastItemsPerLevel[$queueItem->level - 1]->children[] = $queueItem;

        // Add this item as the last one on the level
        $this->lastItemsPerLevel[$queueItem->level] = $queueItem;

        unset($node);

        return true;
    }

    /**
     * Print debug info for a note
     *
     * @param object $node
     *
     * @return void
     */
    public function printDebugInfo($node)
    {
        $debugRow = '<div><span>%s:</span>&nbsp;%s</div>';
        echo '<div class="osmap-debug-box">'
            . sprintf('<div><span>#:</span>&nbsp;%s</div>', $this->generalCounter)
            . sprintf($debugRow, Text::_('COM_OSMAP_UID'), $node->uid)
            . sprintf($debugRow, Text::_('COM_OSMAP_FULL_LINK'), htmlspecialchars($node->fullLink))
            . sprintf($debugRow, Text::_('COM_OSMAP_RAW_LINK'), htmlspecialchars($node->rawLink))
            . sprintf($debugRow, Text::_('COM_OSMAP_LINK'), htmlspecialchars($node->link))
            . sprintf($debugRow, Text::_('COM_OSMAP_MODIFIED'), htmlspecialchars($node->modified))
            . sprintf($debugRow, Text::_('COM_OSMAP_LEVEL'), $node->level)
            . sprintf($debugRow, Text::_('COM_OSMAP_DUPLICATE'), Text::_($node->duplicate ? 'JYES' : 'JNO'))
            . sprintf(
                $debugRow,
                Text::_('COM_OSMAP_VISIBLE_FOR_ROBOTS'),
                Text::_($node->visibleForRobots ? 'JYES' : 'JNO')
            )
            . sprintf($debugRow, Text::_('COM_OSMAP_ADAPTER_CLASS'), $node->adapter);

        if (method_exists($node, 'getAdminNotesString')) {
            if ($adminNotes = $node->getAdminNotesString()) {
                echo sprintf($debugRow, Text::_('COM_OSMAP_ADMIN_NOTES'), nl2br($adminNotes));
            }
        }
        echo '</div>';
    }

    /**
     * Print an item
     *
     * @param object $item
     *
     * @return void
     */
    public function printItem(object $item)
    {
        $this->generalCounter++;

        $liClass = $this->debug ? 'osmap-debug-item' : '';
        $liClass .= $this->generalCounter % 2 == 0 ? ' even' : '';

        if (empty($item->children) == false) {
            $liClass .= ' osmap-has-children';
        }

        $sanitizedUID = ApplicationHelper::stringURLSafe($item->uid);

        echo sprintf('<li class="%s" id="osmap-li-uid-%s">', $liClass, $sanitizedUID);

        // Some items are just separator, without a link. Do not print as link then
        if (trim($item->rawLink ?? '') === '') {
            $type = $item->type ?? 'separator';
            echo sprintf('<span class="osmap-item-%s">%s</span>', $type, htmlspecialchars($item->name));

        } else {
            echo sprintf(
                '<a href="%s" target="_self" class="osmap-link">%s</a>',
                $item->rawLink,
                htmlspecialchars($item->name)
            );
        }

        if ($this->debug) {
            $this->printDebugInfo($item);
        }

        if (empty($item->children) == false) {
            $this->printMenu($item);
        }

        echo '</li>';
    }

    /**
     * Renders html sitemap
     *
     * @return void
     */
    public function renderSitemap()
    {
        if (!empty($this->menus)) {
            $columns = max((int)$this->params->get('columns', 1), 1);

            foreach ($this->menus as $menuType => $menu) {
                if (
                    isset($menu->menuItemTitle)
                    && $this->showMenuTitles
                    && empty($menu->children) == false
                ) {
                    if ($this->debug) {
                        $debug = sprintf(
                            '<div><span>%s:</span>&nbsp;%s: %s</div>',
                            Text::_('COM_OSMAP_MENUTYPE'),
                            $menu->menuItemId,
                            $menu->menuItemType
                        );
                    }

                    echo sprintf(
                        '<h2 id="osmap-menu-uid-%s">%s%s</h2>',
                        ApplicationHelper::stringURLSafe($menu->menuItemType),
                        $menu->menuItemTitle,
                        empty($debug) ? '' : $debug
                    );
                }

                $this->printMenu($menu, $columns);
            }
        }
    }

    /**
     * Render the menu item and its children items
     *
     * @param object $menu
     * @param ?int   $columns
     *
     * @return void
     */
    protected function printMenu(object $menu, ?int $columns = null)
    {
        if (isset($menu->menuItemType)) {
            $sanitizedUID = ApplicationHelper::stringURLSafe($menu->menuItemType);
        } else {
            $sanitizedUID = ApplicationHelper::stringURLSafe($menu->uid);
        }

        $class = ['level_' . ($menu->level + 1)];
        if ($columns && $columns > 1) {
            $class[] = 'columns_' . $columns;
        }

        echo sprintf(
            '<ul class="%s" id="osmap-ul-uid-%s">',
            join(' ', $class),
            $sanitizedUID
        );

        foreach ($menu->children as $item) {
            $this->printItem($item);
        }

        echo '</ul>';
    }
}
