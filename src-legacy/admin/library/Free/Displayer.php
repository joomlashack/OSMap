<?php
/**
 * @package   OSMap
 * @copyright 2007-2014 XMap - Joomla! Vargas. All rights reserved.
 * @copyright 2016 Open Source Training, LLC. All rights reserved..
 * @author    Guillermo Vargas <guille@vargas.co.cr>
 * @author    Alledia <support@alledia.com>
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 *
 * This file is part of OSMap.
 *
 * OSMap is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * OSMap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OSMap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Alledia\OSMap\Free;

use Alledia\Framework;
use JFactory;
use JDate;
use stdClass;
use JSite;
use JURI;
use JRegistry;

// No direct access
defined('_JEXEC') or die('Restricted access');

class Displayer
{
    /**
     *
     * @var int  Counter for the number of links on the sitemap
     */
    protected $count;

    /**
     *
     * @var JView
     */
    protected $jview;

    public $config;

    public $sitemap;

    /**
     *
     * @var int   Current timestamp
     */
    public $now;
    public $userLevels;
    /**
     *
     * @var string  The current value for the request var "view" (eg. html, xml)
     */
    public $view;

    public $canEdit;

    public function __construct($config, $sitemap)
    {
        jimport('joomla.utilities.date');
        jimport('joomla.user.helper');

        $user = JFactory::getUser();
        $date = new JDate();

        $this->userLevels = (array) $user->getAuthorisedViewLevels();
        $this->now        = $date->toUnix();
        $this->config     = $config;
        $this->sitemap    = $sitemap;
        $this->isNews     = false;
        $this->isImages   = false;
        $this->count      = 0;
        $this->canEdit    = false;
    }

    public function printNode($node)
    {
        return false;
    }

    public function printSitemap()
    {
        foreach ($this->jview->items as $menutype => &$items) {

            $node = new stdClass();

            $node->uid        = "menu-" . $menutype;
            $node->menutype   = $menutype;
            $node->priority   = null;
            $node->changefreq = null;
            $node->browserNav = 3;
            $node->type       = 'separator';
            /**
             * @todo allow the user to provide the module used to display that menu, or some other
             * workaround
             */
            $node->name = $this->getMenuTitle($menutype); // Get the name of this menu

            $this->startMenu($node);
            $this->printMenuTree($node, $items);
            $this->endMenu($node);
        }
    }

    public function setJView($view)
    {
        $this->jview = $view;
    }

    public function getMenuTitle($menutype)
    {
        $db  = JFactory::getDbo();

        //checking to see if menu is in menu_types table if not in modules table
        $db->setQuery(
            "SELECT * FROM `#__menu_types` WHERE menutype='{$menutype}' "
            . "LIMIT 1"
        );
        $module = $db->loadObject();

        $title = '';
        if (is_object($module)) {
            $title = $module->title;
        }

        return $title;
    }

    protected function startMenu(&$node)
    {
        return true;
    }

    protected function endMenu(&$node)
    {
        return true;
    }

    protected function printMenuTree($menu, &$items)
    {
        $this->changeLevel(1);

        $router = JSite::getRouter();

        // Add each menu entry to the root tree.
        foreach ($items as $item) {
            $excludeExternal = false;

            $node = new stdClass;

            $node->id         = $item->id;
            $node->uid        = $item->uid;
            // displayed name of node
            $node->name       = $item->title;
            // how to open link
            $node->browserNav = $item->browserNav;
            $node->priority   = $item->priority;
            $node->changefreq = $item->changefreq;
            // menuentry-type
            $node->type       = $item->type;
            // menuentry-type
            $node->menutype   = $menu->menutype;
            // If it's a home menu entry
            $node->home       = $item->home;
            $node->link       = $item->link;
            $node->option     = $item->option;
            $node->modified   = @$item->modified;
            $node->secure     = $item->params->get('secure');

            // New on OSMap 2.0: send the menu params
            $node->params =& $item->params;

            if ($node->home == 1) {
                // Correct the URL for the home page.
                $node->link = JURI::base();
            }

            switch ($item->type) {
                case 'separator':
                case 'heading':
                    $node->browserNav=3;
                    break;

                case 'url':
                    if ((strpos($item->link, 'index.php?') === 0) && (strpos($item->link, 'Itemid=') === false)) {
                        // If this is an internal Joomla link, ensure the Itemid is set.
                        $node->link = $node->link.'&Itemid='.$node->id;
                        // @todo: refactor to use JURI::isInternal()
                    } elseif (strpos($node->link, JURI::base()) === false) {
                        $excludeExternal = true;
                    }
                    break;

                case 'alias':
                    // If this is an alias use the item id stored in the parameters to make the link.
                    $node->link = 'index.php?Itemid='.$item->params->get('aliasoptions');
                    break;

                default:
                    if ($router->getMode() == JROUTER_MODE_SEF) {
                        $node->link = 'index.php?Itemid='.$node->id;
                    } elseif (!$node->home) {
                        $node->link .= '&Itemid='.$node->id;
                    }
                    break;
            }

            if ($excludeExternal || $this->printNode($node)) {

                // Restore the original link
                $node->link = $item->link;
                $this->printMenuTree($node, $item->items);

                if ($node->option && !empty($this->jview->extensions[$node->option])) {
                    $plugin = $this->jview->extensions[$node->option];

                    // Check if the method is static or not
                    $methodParams = array(&$this, &$node, &$plugin->params);

                    Framework\Helper::callMethod($plugin->className, 'getTree', $methodParams);

                    $node->uid = $node->option;
                }
            }

        }
        $this->changeLevel(-1);
    }

    public function changeLevel($step)
    {
        return true;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function &getExcludedItems()
    {
        static $_excluded_items;

        if (!isset($_excluded_items)) {
            $_excluded_items = array();
            $registry        = new JRegistry('_default');

            $registry->loadString($this->sitemap->excluded_items);

            $_excluded_items = $registry->toArray();
        }

        return $_excluded_items;
    }

    public function isExcluded($itemid, $uid)
    {
        $excludedItems = $this->getExcludedItems();
        $items         = null;

        if (!empty($excludedItems[$itemid])) {
            if (is_object($excludedItems[$itemid])) {
                $excludedItems[$itemid] = (array) $excludedItems[$itemid];
            }

            $items =& $excludedItems[$itemid];
        }

        if (!$items) {
            return false;
        }

        return ( in_array($uid, $items));
    }
}
