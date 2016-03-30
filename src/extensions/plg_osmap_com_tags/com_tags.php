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
defined('_JEXEC') or die('Restricted access');
class osmap_com_tags
{
    /*
    * @var string Value for $_GET param 'option'
    */
    protected static $option = 'com_tags';
    /*
    * @var array view types to add links too
    */
    protected static $views = array('tags', 'tag');
    /*
    * @var boolean stores if plugin is enabled
    */
    private static $enabled = null;
    /*
    * @var object stores instance of self
    */
    private static $instance = null;
    /*
    * @return instance of this class
    */
    public static function getInstance()
    {
        if (empty(static::$instance)) {
            $instance = new self;
            static::$instance = $instance;
        }
        return static::$instance;
    }
    /*
    * Runs through tree, checks if view is equal with
    * current link then runs method for that view
    *
    * @param OSMapXmlDisplayer $osmap
    * @param stdClass $parent (current osmap link)
    * @param array $params (values form admin settings)
    */
    public function getTree($osmap, $parent, &$params)
    {
        $uri = new JUri($parent->link);
        if (!static::isEnabled()
            || !in_array($uri->getVar('view'), static::$views)
            || $uri->getVar('option') !== static::$option
        ) {
            return;
        }
        $ids = (array) $uri->getVar('id');
        
        $params['groups'] = implode(',', JFactory::getUser()->getAuthorisedViewLevels());
        $params['language_filter'] = JFactory::getApplication()->getLanguageFilter();
        $params['include_links'] = JArrayHelper::getValue($params, 'include_links', 1);
        $params['include_links'] = ($params['include_links'] == 1
            || ($params['include_links'] == 2 && $osmap->view == 'xml')
            || ($params['include_links'] == 3 && $osmap->view == 'html')
        );
        $params['show_unauth'] = JArrayHelper::getValue($params, 'show_unauth', 0);
        $params['show_unauth'] = ($params['show_unauth'] == 1
            || ($params['show_unauth'] == 2 && $osmap->view == 'xml')
            || ($params['show_unauth'] == 3 && $osmap->view == 'html')
        );
        $params['category_priority']   = JArrayHelper::getValue($params, 'category_priority', $parent->priority);
        $params['category_changefreq'] = JArrayHelper::getValue($params, 'category_changefreq', $parent->changefreq);
        if ($params['category_priority'] == -1) {
            $params['category_priority'] = $parent->priority;
        }
        if ($params['category_changefreq'] == -1) {
            $params['category_changefreq'] = $parent->changefreq;
        }
        $params['link_priority']   = JArrayHelper::getValue($params, 'link_priority', $parent->priority);
        $params['link_changefreq'] = JArrayHelper::getValue($params, 'link_changefreq', $parent->changefreq);
        if ($params['link_priority'] == -1) {
            $params['link_priority'] = $parent->priority;
        }
        if ($params['link_changefreq'] == -1) {
            $params['link_changefreq'] = $parent->changefreq;
        }
        switch ($uri->getVar('view')) {
            case 'tags':
                static::printViewTagsLinks($osmap, $parent, $params);
                break;
            case 'tag':
                static::printViewTaglinks($osmap, $parent, $params, $ids);
                break;
        }
    }
    /*
    * Prints Links by what is passed in $itemIds array
    *
    * @param OSMapXmlDisplayer $osmap
    * @param stdClass $parent (current osmap link)
    * @param array $params (values form admin settings)
    * @param array $itemIds (tag ids to print to osmap)
    */
    protected static function printViewTagLinks($osmap, $parent, $params, $itemIds)
    {
        if (!$params['include_links']) {
            return;
        }
        $itemIds = implode(', ', $itemIds);
        
        $db            = JFactory::getDbo();
        $tagQuery = $db->getQuery(true)
            ->select(
                array(
                    'tag.id',
                    'tag.title',
                    'tag.path'
                )
            )
            ->from('#__tags AS tag')
            ->where(
                array(
                    'tag.id IN (' . $itemIds . ')',
                    'tag.published=1'
                )
            );
        $tagItems = $db->setQuery($tagQuery)->loadObjectList();
        if (empty($tagItems)) {
            return;
        }
        foreach ($tagItems as $tagItem) {
            $tagPath = explode('/', $tagItem->path);
            $tagPathCount = count($tagPath) - 1;
            $osmap->changeLevel(1);
            $node             = new stdclass;
            $node->id         = $parent->id;
            $node->name       = $tagItem->title;
            $node->uid        = 'com_tags' . $tagItem->id;
            $node->browserNav = $parent->browserNav;
            $node->priority   = $params['link_priority'];
            $node->changefreq = $params['link_changefreq'];
            $node->link       = 'index.php?option='.static::$option.'&id='
                                .$tagItem->id.':'.$tagPath[$tagPathCount]
                                .'&view=tag';
            $osmap->printNode($node);
            $osmap->changeLevel(-1);
        }
    }
    /*
    * Prints all Tags that are listed.
    *
    * @param OSMapXmlDisplayer $osmap
    * @param stdClass $parent (current osmap link)
    * @param array $params (values form admin settings)
    */
    protected static function printViewTagsLinks($osmap, $parent, $params)
    {
        if (!$params['include_links']) {
            return;
        }
        
        $db            = JFactory::getDbo();
        $tagQuery = $db->getQuery(true)
            ->select(
                array(
                    'tag.id',
                    'tag.title',
                    'tag.path'
                )
            )
            ->from('#__tags AS tag')
            ->where(
                array(
                    'tag.published=1',
                    'tag.level > 0'
                )
            );
        $tagItems = $db->setQuery($tagQuery)->loadObjectList();
        if (empty($tagItems)) {
            return;
        }
        foreach ($tagItems as $tagItem) {
            $tagPath = explode('/', $tagItem->path);
            $tagPathCount = count($tagPath) - 1;
            $osmap->changeLevel(1);
            $node             = new stdclass;
            $node->id         = $parent->id;
            $node->name       = $tagItem->title;
            $node->uid        = 'com_tags' . $tagItem->id;
            $node->browserNav = $parent->browserNav;
            $node->priority   = $params['link_priority'];
            $node->changefreq = $params['link_changefreq'];
            $node->link       = 'index.php?option='.static::$option.'&id='
                                .$tagItem->id.':'.$tagPath[$tagPathCount]
                                .'&view=tag';
            $osmap->printNode($node);
            $osmap->changeLevel(-1);
        }
    }
    
    /*
    * Checks if plugin is enabled
    *
    * @return boolean static::$enabled
    */
    protected static function isEnabled()
    {
        if (null === static::$enabled) {
            $db = JFactory::getDbo();
            $db->setQuery('Select enabled From #__extensions Where name=' . $db->quote('com_tags'));
            static::$enabled = (bool)$db->loadResult();
        }
        return static::$enabled;
    }
}
