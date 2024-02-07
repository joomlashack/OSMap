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

namespace Alledia\OSMap\Sitemap;

use Alledia\OSMap\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

defined('_JEXEC') or die();

class Standard implements SitemapInterface
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var Registry
     */
    public $params;

    /**
     * @var bool
     */
    public $isDefault = false;

    /**
     * @var bool
     */
    public $isPublished = true;

    /**
     * @var string
     */
    public $createdOn;

    /**
     * @var int
     */
    public $linksCount = 0;

    /**
     * @var string
     */
    protected $type = 'standard';

    /**
     * @var Collector
     */
    protected $collector;

    /**
     * Limit in days for news sitemap items
     *
     * @var int
     */
    public $newsDateLimit = 2;

    /**
     * The constructor
     *
     * @param int $id
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(int $id)
    {
        /** @var \OSMapTableSitemap $sitemap */
        $sitemap = Factory::getTable('Sitemap');
        $sitemap->load($id);

        if (empty($sitemap) || !$sitemap->id) {
            throw new \Exception(Text::_('COM_OSMAP_SITEMAP_NOT_FOUND'), 404);
        }

        $this->id          = $sitemap->id;
        $this->name        = $sitemap->name;
        $this->isDefault   = $sitemap->is_default == 1;
        $this->isPublished = $sitemap->published == 1;
        $this->createdOn   = $sitemap->created_on;
        $this->linksCount  = (int)$sitemap->links_count;
        $this->params      = new Registry($sitemap->params);

        $this->initCollector();
    }

    /**
     * Method to initialize the items collector
     *
     * @return void
     */
    protected function initCollector()
    {
        $this->collector = new Collector($this);
    }

    /**
     * @inheritDoc
     */
    public function traverse(callable $callback, $triggerEvents = true, $updateCount = false)
    {
        if ($triggerEvents) {
            // Call the plugins, allowing to interact or override the collector
            PluginHelper::importPlugin('osmap');

            $eventParams = [$this, $callback];
            $results     = Factory::getApplication()->triggerEvent('osmapOnBeforeCollectItems', $eventParams);

            // A plugin asked to stop the traverse
            if (in_array(true, $results)) {
                return;
            }

            $results = null;
        }

        // Fetch the sitemap items
        $count = $this->collector->fetch($callback);

        if ($updateCount) {
            // Update the links count in the sitemap
            $this->updateLinksCount($count);
        }

        // Cleanup
        $this->collector->cleanup();
        $this->collector = null;
    }

    /**
     * Updates the count of links in the database
     *
     * @param int $count
     *
     * @return void
     */
    protected function updateLinksCount(int $count)
    {
        $db = Factory::getDbo();

        $updateObject = (object)[
            'id'          => $this->id,
            'links_count' => $count
        ];

        $db->updateObject('#__osmap_sitemaps', $updateObject, ['id']);
    }

    public function cleanup()
    {
        $this->collector = null;
        $this->params    = null;
    }
}
