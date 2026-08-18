<?php

/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2007-2014 XMap - Joomla! Vargas - Guillermo Vargas. All rights reserved.
 * @copyright 2016-2026 Joomlashack.com. All rights reserved.
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

namespace Alledia\OSMap\Plugin;

use Alledia\Framework\Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
defined('_JEXEC') or die();

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

abstract class Base extends CMSPlugin
{
    /**
     * @var ?int
     */
    protected static ?int $memoryLimit = null;

    /**
     * Minimum memory in MB required to continue on sites with limited memory
     *
     * @var int
     */
    protected static int $memoryMinimum = 4;

    /**
     * @var array
     */
    protected static array $magnitudes = [
        'K' => 1024,
        'M' => 1024 * 1024,
        'G' => 1024 * 1024 * 1024,
    ];

    /**
     * @inheritDoc
     */
    public function __construct(&$subject, $config = [])
    {
        require_once JPATH_ADMINISTRATOR . '/components/com_osmap/include.php';

        parent::__construct($subject, $config);
    }

    /**
     * Set memory limit to unlimited. If unable to do so,
     * we'll want to check that we have enough memory left to continue,
     * so we can fail gracefully
     *
     * @return void
     */
    protected static function fixMemoryLimit()
    {
        if (static::$memoryLimit === null) {
            // Convert minimum to megabytes
            static::$memoryMinimum *= static::$magnitudes['M'];
            static::$memoryLimit   = false;

            $c = function ($shorthand) {
                if (is_string($shorthand) && is_numeric($shorthand) == false) {
                    $regex = sprintf('/(\d*)([%s])/', join(array_keys(static::$magnitudes)));
                    if (preg_match($regex, $shorthand, $match)) {
                        return $match[1] * static::$magnitudes[$match[2]];
                    }
                }

                return $shorthand ?: 0;
            };

            $maxMemory = $c(ComponentHelper::getParams('com_osmap')->get('max_memory', -1));

            $limit = $c(ini_get('memory_limit'));
            if ($maxMemory && ($limit < $maxMemory || $maxMemory < 0)) {
                // Request more memory than currently configured
                @ini_set('memory_limit', $maxMemory);

                $limit = $c(ini_get('memory_limit'));
            }

            if ($limit > 0) {
                // We've ended up with limited memory, allow for checking
                static::$memoryLimit = $c($limit);
            }
        }
    }

    /**
     * Check to see if we're about to run out of memory. If things get too tight
     * all we can do is throw an informative message or redirect somewhere else
     * that isn't an OSMap page
     *
     * @TODO: Decide whether to implement the redirect option
     *
     * @return void
     * @throws Exception
     */
    protected static function checkMemory()
    {
        if (static::$memoryLimit === null) {
            static::fixMemoryLimit();
        }

        if (static::$memoryLimit && (static::$memoryLimit - memory_get_usage(true) < static::$memoryMinimum)) {
            $message = Text::sprintf('COM_OSMAP_WARNING_OOM', get_called_class());
            throw new Exception($message, 500);
        }
    }

    /**
     * @param string $url
     *
     * @return string
     */
    protected static function getViewFromUrl(string $url): string
    {
        $linkUrl = parse_url($url);
        if (isset($linkUrl['query'])) {
            parse_str($linkUrl['query'], $linkQuery);

            if (isset($linkQuery['view'])) {
                return $linkQuery['view'];
            }
        }

        return '';
    }
}
