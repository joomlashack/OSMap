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
use Alledia\OSMap\Helper\General;
use Alledia\OSMap\Plugin\Base;
use Alledia\OSMap\Plugin\ContentInterface;
use Alledia\OSMap\Sitemap\Collector;
use Alledia\OSMap\Sitemap\Item;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die();

if (class_exists(RouteHelper::class) == false) {
    $siteContentPath = JPATH_SITE . '/components/com_content/helpers/route.php';
    if (is_file($siteContentPath)) {
        require_once $siteContentPath;
        class_alias(ContentHelperRoute::class, RouteHelper::class);
    }
}

// phpcs:enable PSR1.Files.SideEffects
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

/**
 * Handles standard Joomla's Content articles/categories
 *
 * This plugin is able to expand the categories keeping the right order of the
 * articles according to the menu settings and the user session data (user state).
 *
 * This is a very complex plugin, if you are trying to build your own plugin
 * for other component, I suggest you to take a look to another plugis as
 * they are usually most simple. ;)
 */
class PlgOSMapJoomla extends Base implements ContentInterface
{
    /**
     * @var self
     */
    protected static $instance = null;

    /**
     * @var bool
     */
    protected static $prepareContent = null;

    /**
     * @inheritDoc
     */
    public static function getInstance()
    {
        if (empty(static::$instance)) {
            $dispatcher       = Factory::getDispatcher();
            static::$instance = new self($dispatcher);
        }

        return static::$instance;
    }

    /**
     * @inheritDoc
     */
    public function getComponentElement()
    {
        return 'com_content';
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public static function prepareMenuItem($node, $params)
    {
        static::checkMemory();

        $db        = Factory::getDbo();
        $container = Factory::getPimpleContainer();

        $link      = parse_url($node->link);
        $linkQuery = $link['query'] ?? null;
        if ($linkQuery) {
            parse_str(html_entity_decode($linkQuery), $linkVars);

            $view = ArrayHelper::getValue($linkVars, 'view', '');
            $id   = ArrayHelper::getValue($linkVars, 'id', 0);

            switch ($view) {
                case 'archive':
                    $node->adapterName = 'JoomlaCategory';
                    $node->uid         = 'joomla.archive.' . $node->id;
                    $node->expandible  = true;

                    break;

                case 'featured':
                    $node->adapterName = 'JoomlaCategory';
                    $node->uid         = 'joomla.featured.' . $node->id;
                    $node->expandible  = true;

                    break;

                case 'categories':
                case 'category':
                    $node->adapterName = 'JoomlaCategory';
                    $node->uid         = 'joomla.category.' . $id;
                    $node->expandible  = true;

                    break;

                case 'article':
                    $node->adapterName = 'JoomlaArticle';
                    $node->expandible  = false;

                    $paramAddPageBreaks = $params->get('add_pagebreaks', 1);
                    $paramAddImages     = $params->get('add_images', 1);

                    $query = $db->getQuery(true)
                        ->select([
                            $db->quoteName('created'),
                            $db->quoteName('modified'),
                            $db->quoteName('publish_up'),
                            $db->quoteName('metadata'),
                            $db->quoteName('attribs'),
                        ])
                        ->from($db->quoteName('#__content'))
                        ->where($db->quoteName('id') . '=' . (int)$id);

                    if ($paramAddPageBreaks || $paramAddImages) {
                        $query->select([
                            $db->quoteName('introtext'),
                            $db->quoteName('fulltext'),
                            $db->quoteName('images'),
                        ]);
                    }

                    $db->setQuery($query);

                    if (($item = $db->loadObject()) != null) {
                        // Set the node UID
                        $node->uid = 'joomla.article.' . $id;

                        // Set dates
                        $node->modified  = $item->modified;
                        $node->created   = $item->created;
                        $node->publishUp = $item->publish_up;

                        $item->params = $item->attribs;

                        $text = '';
                        if (isset($item->introtext) && isset($item->fulltext)) {
                            $text = $item->introtext . $item->fulltext;
                        }

                        static::prepareContent($text, $params);

                        if ($paramAddImages) {
                            $maxImages = $params->get('max_images', 1000);

                            $node->images = [];

                            // Images from text
                            $node->images = array_merge(
                                $node->images,
                                $container->imagesHelper->getImagesFromText($text, $maxImages)
                            );

                            // Images from params
                            if (!empty($item->images)) {
                                $node->images = array_merge(
                                    $node->images,
                                    $container->imagesHelper->getImagesFromParams($item)
                                );
                            }
                        }

                        if ($paramAddPageBreaks) {
                            $node->subnodes   = General::getPagebreaks($text, $node->link, $node->uid);
                            $node->expandible = (count($node->subnodes) > 0); // This article has children
                        }
                    } else {
                        return false;
                    }

                    break;
            }

            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public static function getTree($collector, $parent, $params)
    {
        $db = Factory::getDbo();

        $link      = parse_url($parent->link);
        $linkQuery = $link['query'] ?? null;
        if ($linkQuery == false) {
            return;
        }

        parse_str(html_entity_decode($linkQuery), $linkVars);
        $view = ArrayHelper::getValue($linkVars, 'view', '');
        $id   = intval(ArrayHelper::getValue($linkVars, 'id', ''));

        /*
         * Parameters Initialisation
         */
        $paramExpandCategories = $params->get('expand_categories', 1) > 0;
        $paramExpandFeatured   = $params->get('expand_featured', 1);
        $paramIncludeArchived  = $params->get('include_archived', 2);

        $paramAddPageBreaks = $params->get('add_pagebreaks', 1);

        $paramCatPriority   = $params->get('cat_priority', $parent->priority);
        $paramCatChangefreq = $params->get('cat_changefreq', $parent->changefreq);

        if ($paramCatPriority == '-1') {
            $paramCatPriority = $parent->priority;
        }
        if ($paramCatChangefreq == '-1') {
            $paramCatChangefreq = $parent->changefreq;
        }
        $params->set('cat_priority', $paramCatPriority);
        $params->set('cat_changefreq', $paramCatChangefreq);

        $paramArtPriority   = $params->get('art_priority', $parent->priority);
        $paramArtChangefreq = $params->get('art_changefreq', $parent->changefreq);

        if ($paramArtPriority == '-1') {
            $paramArtPriority = $parent->priority;
        }

        if ($paramArtChangefreq == '-1') {
            $paramArtChangefreq = $parent->changefreq;
        }

        $params->set('art_priority', $paramArtPriority);
        $params->set('art_changefreq', $paramArtChangefreq);

        // If enabled, loads the page break language
        if ($paramAddPageBreaks && defined('OSMAP_PLUGIN_JOOMLA_LOADED') == false) {
            // Load it just once
            define('OSMAP_PLUGIN_JOOMLA_LOADED', 1);

            Factory::getLanguage()->load('plg_content_pagebreak');
        }

        switch ($view) {
            case 'category':
                if (empty($id)) {
                    $id = intval($params->get('id', 0));
                }

                if ($paramExpandCategories && $id) {
                    static::expandCategory($collector, $parent, $id, $params, $parent->id);
                }

                break;

            case 'featured':
                if ($paramExpandFeatured) {
                    static::includeCategoryContent($collector, $parent, 'featured', $params);
                }

                break;

            case 'categories':
                if ($paramExpandCategories) {
                    if (empty($id)) {
                        $id = 1;
                    }

                    static::expandCategory($collector, $parent, $id, $params, $parent->id);
                }

                break;

            case 'archive':
                if ($paramIncludeArchived) {
                    static::includeCategoryContent($collector, $parent, 'archived', $params);
                }

                break;

            case 'article':
                // if it's an article menu item, we have to check if we have to expand the
                // article's page breaks
                if ($paramAddPageBreaks) {
                    $query = $db->getQuery(true)
                        ->select([
                            $db->quoteName('introtext'),
                            $db->quoteName('fulltext'),
                            $db->quoteName('alias'),
                            $db->quoteName('catid'),
                            $db->quoteName('attribs') . ' AS params',
                            $db->quoteName('metadata'),
                            $db->quoteName('created'),
                            $db->quoteName('modified'),
                            $db->quoteName('publish_up'),
                        ])
                        ->from($db->quoteName('#__content'))
                        ->where($db->quoteName('id') . '=' . $id);
                    $db->setQuery($query);

                    $item = $db->loadObject();

                    $item->uid = 'joomla.article.' . $id;

                    $parent->slug = $item->alias ? ($id . ':' . $item->alias) : $id;
                    $parent->link = RouteHelper::getArticleRoute($parent->slug, $item->catid);

                    $parent->subnodes = General::getPagebreaks(
                        $item->introtext . $item->fulltext,
                        $parent->link,
                        $item->uid
                    );
                    static::printSubNodes($collector, $parent, $params, $parent->subnodes, $item);
                }
        }
    }

    /**
     * Get all content items within a content category.
     * Returns an array of all contained content items.
     *
     * @param Collector $collector
     * @param Item      $parent the menu item
     * @param int       $catid  the id of the category to be expanded
     * @param Registry  $params parameters for this plugin on Xmap
     * @param int       $itemid the itemid to use for this category's children
     * @param ?int      $currentLevel
     *
     * @return void
     * @throws Exception
     */
    protected static function expandCategory(
        Collector $collector,
        Item $parent,
        int $catid,
        Registry $params,
        int $itemid,
        ?int $currentLevel = 0
    ): void {
        static::checkMemory();

        $db = Factory::getDbo();

        $where = [
            'a.parent_id = ' . $catid,
            'a.published = 1',
            'a.extension=' . $db->quote('com_content'),
        ];

        if (!$params->get('show_unauth', 0)) {
            $where[] = 'a.access IN (' . General::getAuthorisedViewLevels() . ') ';
        }

        $query = $db->getQuery(true)
            ->select([
                'a.id',
                'a.title',
                'a.alias',
                'a.access',
                'a.path AS route',
                'a.created_time AS created',
                'a.modified_time AS modified',
                'a.params',
                'a.metadata',
                'a.metakey',
            ])
            ->from('#__categories AS a')
            ->where($where)
            ->order('a.lft');

        $items = $db->setQuery($query)->loadObjectList();

        $currentLevel++;

        $maxLevel = $parent->params->get('max_category_level', 100);

        if ($currentLevel <= $maxLevel) {
            if (count($items) > 0) {
                $collector->changeLevel(1);

                foreach ($items as $item) {
                    $node = (object)[
                        'id'                       => $item->id,
                        'uid'                      => 'joomla.category.' . $item->id,
                        'browserNav'               => $parent->browserNav,
                        'priority'                 => $params->get('cat_priority'),
                        'changefreq'               => $params->get('cat_changefreq'),
                        'name'                     => $item->title,
                        'expandible'               => true,
                        'secure'                   => $parent->secure,
                        'newsItem'                 => 1,
                        'adapterName'              => 'JoomlaCategory',
                        'pluginParams'             => &$params,
                        'parentIsVisibleForRobots' => $parent->visibleForRobots,
                        'created'                  => $item->created,
                        'modified'                 => $item->modified,
                        'publishUp'                => $item->created,
                    ];

                    // Keywords
                    $paramKeywords = $params->get('keywords', 'metakey');
                    $keywords      = null;
                    if ($paramKeywords !== 'none') {
                        $keywords = $item->metakey;
                    }
                    $node->keywords = $keywords;

                    $node->slug   = $item->route ? ($item->id . ':' . $item->route) : $item->id;
                    $node->link   = RouteHelper::getCategoryRoute($node->slug);
                    $node->itemid = $itemid;

                    // Correct for an issue in Joomla core with occasional empty variables
                    $linkUri = new Uri($node->link);
                    $linkUri->setQuery(array_filter((array)$linkUri->getQuery(true)));
                    $node->link = $linkUri->toString();

                    if ($collector->printNode($node)) {
                        static::expandCategory($collector, $parent, $item->id, $params, $node->itemid, $currentLevel);
                    }
                }

                $collector->changeLevel(-1);
            }
        }

        // Include Category's content
        static::includeCategoryContent($collector, $parent, $catid, $params);
    }

    /**
     * Get all content items within a content category.
     * Returns an array of all contained content items.
     *
     * @param Collector  $collector
     * @param Item       $parent
     * @param int|string $catid
     * @param Registry   $params
     *
     * @return void
     * @throws Exception
     *
     */
    protected static function includeCategoryContent(
        Collector $collector,
        Item $parent,
        $catid,
        Registry $params
    ): void {
        static::checkMemory();

        $db        = Factory::getDbo();
        $container = Factory::getPimpleContainer();

        $nullDate = $db->quote($db->getNullDate());
        $nowDate  = $db->quote(Factory::getDate()->toSql());

        $selectFields = [
            'a.id',
            'a.title',
            'a.alias',
            'a.catid',
            'a.created',
            'a.modified',
            'a.publish_up',
            'a.attribs AS params',
            'a.metadata',
            'a.language',
            'a.metakey',
            'a.images',
            'c.title AS categMetakey',
        ];
        if ($params->get('add_images', 1) || $params->get('add_pagebreaks', 1)) {
            $selectFields[] = 'a.introtext';
            $selectFields[] = 'a.fulltext';
        }

        if ($params->get('include_archived', 2)) {
            $where = ['(a.state = 1 or a.state = 2)'];
        } else {
            $where = ['a.state = 1'];
        }

        if ($catid == 'featured') {
            $where[] = 'a.featured=1';
        } elseif ($catid == 'archived') {
            $where = ['a.state=2'];
        } elseif (is_numeric($catid)) {
            $where[] = 'a.catid=' . (int)$catid;
        }

        if (!$params->get('show_unauth', 0)) {
            $where[] = 'a.access IN (' . General::getAuthorisedViewLevels() . ') ';
        }

        $where[] = sprintf(
            '(ISNULL(a.publish_up) OR a.publish_up = %s OR a.publish_up <= %s)',
            $nullDate,
            $nowDate
        );
        $where[] = sprintf(
            '(ISNULL(a.publish_down) OR a.publish_down = %s OR a.publish_down >= %s)',
            $nullDate,
            $nowDate
        );

        //@todo: Do we need join for frontpage?
        $query = $db->getQuery(true)
            ->select($selectFields)
            ->from('#__content AS a')
            ->join('LEFT', '#__content_frontpage AS fp ON (a.id = fp.content_id)')
            ->join('LEFT', '#__categories AS c ON (a.catid = c.id)')
            ->where($where);

        // Ordering
        $orderOptions    = [
            'a.created',
            'a.modified',
            'a.publish_up',
            'a.hits',
            'a.title',
            'a.ordering',
        ];
        $orderDirOptions = [
            'ASC',
            'DESC',
        ];

        $order    = ArrayHelper::getValue($orderOptions, $params->get('article_order', 0), 0);
        $orderDir = ArrayHelper::getValue($orderDirOptions, $params->get('article_orderdir', 0), 0);

        $orderBy = ' ' . $order . ' ' . $orderDir;
        $query->order($orderBy);

        $maxArt = (int)$params->get('max_art');
        $db->setQuery($query, 0, $maxArt);

        $items = $db->loadObjectList();

        if (count($items) > 0) {
            $collector->changeLevel(1);

            $paramExpandCategories = $params->get('expand_categories', 1);
            $paramExpandFeatured   = $params->get('expand_featured', 1);
            $paramIncludeArchived  = $params->get('include_archived', 2);

            foreach ($items as $item) {
                $node = (object)[
                    'id'                       => $item->id,
                    'uid'                      => 'joomla.article.' . $item->id,
                    'browserNav'               => $parent->browserNav,
                    'priority'                 => $params->get('art_priority'),
                    'changefreq'               => $params->get('art_changefreq'),
                    'name'                     => $item->title,
                    'created'                  => $item->created,
                    'modified'                 => $item->modified,
                    'publishUp'                => $item->publish_up,
                    'expandible'               => false,
                    'secure'                   => $parent->secure,
                    'newsItem'                 => 1,
                    'language'                 => $item->language,
                    'adapterName'              => 'JoomlaArticle',
                    'parentIsVisibleForRobots' => $parent->visibleForRobots,
                ];

                $keywords = [];

                $paramKeywords = $params->get('keywords', 'metakey');
                if ($paramKeywords !== 'none') {
                    if (in_array($paramKeywords, ['metakey', 'both'])) {
                        $keywords[] = $item->metakey;
                    }

                    if (in_array($paramKeywords, ['category', 'both'])) {
                        $keywords[] = $item->categMetakey;
                    }
                }
                $node->keywords = join(',', $keywords);

                $node->slug    = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
                $node->catslug = $item->catid;
                $node->link    = RouteHelper::getArticleRoute($node->slug, $node->catslug);

                // Set the visibility for XML or HTML sitempas
                if ($catid == 'featured') {
                    // Check if the item is visible in the XML or HTML sitemaps
                    $node->visibleForXML  = in_array($paramExpandFeatured, [1, 2]);
                    $node->visibleForHTML = in_array($paramExpandFeatured, [1, 3]);
                } elseif ($catid == 'archived') {
                    // Check if the item is visible in the XML or HTML sitemaps
                    $node->visibleForXML  = in_array($paramIncludeArchived, [1, 2]);
                    $node->visibleForHTML = in_array($paramIncludeArchived, [1, 3]);
                } elseif (is_numeric($catid)) {
                    // Check if the item is visible in the XML or HTML sitemaps
                    $node->visibleForXML  = in_array($paramExpandCategories, [1, 2]);
                    $node->visibleForHTML = in_array($paramExpandCategories, [1, 3]);
                }

                // Add images to the article
                $text = '';
                if (isset($item->introtext) && isset($item->fulltext)) {
                    $text = $item->introtext . $item->fulltext;
                }

                if ($params->get('add_images', 1)) {
                    $maxImages = $params->get('max_images', 1000);

                    $node->images = [];

                    // Images from text
                    $node->images = array_merge(
                        $node->images,
                        $container->imagesHelper->getImagesFromText($text, $maxImages)
                    );

                    // Images from params
                    if (!empty($item->images)) {
                        $node->images = array_merge(
                            $node->images,
                            $container->imagesHelper->getImagesFromParams($item)
                        );
                    }
                }

                if ($params->get('add_pagebreaks', 1)) {
                    $node->subnodes = General::getPagebreaks($text, $node->link, $node->uid);
                    // This article has children
                    $node->expandible = (count($node->subnodes) > 0);
                }

                if ($collector->printNode($node) && $node->expandible) {
                    static::printSubNodes($collector, $parent, $params, $node->subnodes, $node);
                }
            }

            $collector->changeLevel(-1);
        }
    }

    /**
     * @param Collector $collector
     * @param Item      $parent
     * @param Registry  $params
     * @param array     $subnodes
     * @param object    $item
     *
     * @return void
     * @throws Exception
     */
    protected static function printSubNodes(
        Collector $collector,
        Item $parent,
        Registry $params,
        array $subnodes,
        object $item
    ): void {
        static::checkMemory();

        $collector->changeLevel(1);

        foreach ($subnodes as $subnode) {
            $subnode->browserNav = $parent->browserNav;
            $subnode->priority   = $params->get('art_priority');
            $subnode->changefreq = $params->get('art_changefreq');
            $subnode->secure     = $parent->secure;
            $subnode->created    = $item->created;
            $subnode->modified   = $item->modified;
            $subnode->publishUp  = $item->publish_up ?? $item->created;

            $collector->printNode($subnode);

            $subnode = null;
            unset($subnode);
        }

        $collector->changeLevel(-1);
    }

    /**
     * @param string   $text
     * @param Registry $params
     *
     * @return void
     */
    protected static function prepareContent(string &$text, Registry $params): void
    {
        if (static::$prepareContent === null) {
            $isPro   = Factory::getExtension('osmap', 'component')->isPro();
            $isHtml  = Factory::getDocument()->getType() == 'html';
            $prepare = $params->get('prepare_content', true);

            static::$prepareContent = $isPro && $prepare && $isHtml;
        }

        if (static::$prepareContent) {
            $text = HTMLHelper::_('content.prepare', $text, null, 'com_content.article');
        }
    }
}
