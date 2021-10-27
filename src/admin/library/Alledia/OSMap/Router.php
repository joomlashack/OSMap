<?php
/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2007-2014 XMap - Joomla! Vargas - Guillermo Vargas. All rights reserved.
 * @copyright 2016-2021 Joomlashack.com. All rights reserved.
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

namespace Alledia\OSMap;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die();


class Router
{
    /**
     * Route the given URL using the site application. If in admin, the result
     * needs to be the same as the frontend.
     *
     * @param string $url
     * @param bool   $absolute
     *
     * @return string
     */
    public function routeURL(string $url, bool $absolute = false): string
    {
        if ((strpos($url, '&') !== 0) && (strpos($url, 'index.php') !== 0)) {
            // Not a routable URL
            return $url;
        }

        if ($absolute) {
            return Route::link('site', $url, true,Route::TLS_IGNORE, true);
        }

        return Route::link('site', $url);
    }

    /**
     * Checks if the supplied URL is internal
     *
     * @param string $url
     *
     * @return boolean
     *
     * @return bool
     * @throws \Exception
     */
    public function isInternalURL($url)
    {
        $uri      = Uri::getInstance($url);
        $base     = $uri->toString(['scheme', 'host', 'port', 'path']);
        $host     = $uri->toString(['scheme', 'host', 'port']);
        $path     = $uri->toString(['path']);
        $baseHost = Uri::getInstance($uri::root())->toString(['host']);

        if ($path === $url) {
            return true;

        } elseif (empty($host) && strpos($path, 'index.php') === 0
            || !empty($host) && preg_match('#' . preg_quote($uri::root(), '#') . '#', $base)
            || !empty($host) && $host === $baseHost && strpos($path, 'index.php') !== false
            || !empty($host) && $base === $host
            && preg_match('#' . preg_quote($base, '#') . '#', $uri::root())
        ) {
            return true;
        }

        return false;
    }

    /**
     * Returns the result of JUri::base() from the site used in the sitemap.
     * This is better than the JUri::base() because when we are editing a
     * sitemap in the admin that method returns the /administrator and mess
     * all the urls, which should point to the frontend only.
     *
     * @return string
     * @throws \Exception
     */
    public function getFrontendBase()
    {
        return Factory::getPimpleContainer()->uri::root();
    }

    /**
     * Check if the given URL is a relative URI. Returns true, if afirmative.
     *
     * @param string
     *
     * @return bool
     * @throws \Exception
     */
    public function isRelativeUri($url)
    {
        $container = Factory::getPimpleContainer();

        $uri = $container->uri::getInstance($url);

        return $uri->toString(['path']) === $url;
    }

    /**
     * Converts an internal relative URI into a full link.
     *
     * @param string $path
     *
     * @return string
     * @throws \Exception
     */
    public function convertRelativeUriToFullUri($path)
    {
        if ($path[0] == '/') {
            $scheme = ['scheme', 'user', 'pass', 'host', 'port'];
            $path   = Factory::getPimpleContainer()->uri::getInstance()->toString($scheme) . $path;

        } elseif ($this->isRelativeUri($path)) {
            $path = $this->getFrontendBase() . $path;
        }

        return $path;
    }

    /**
     * Returns a sanitized URL, removing double slashes and trailing slash.
     *
     * @return string
     */
    public function sanitizeURL($url)
    {
        return preg_replace('#([^:])(/{2,})#', '$1/', $url);
    }

    /**
     * Returns a URL without the hash
     *
     * @return string
     */
    public function removeHashFromURL($url)
    {
        // Check if the URL has a hash to remove it (XML sitemap shouldn't have hash on the URL)
        $hashPos = strpos($url, '#');

        if ($hashPos !== false) {
            // Remove the hash
            $url = substr($url, 0, $hashPos);
        }

        return trim($url);
    }

    /**
     * Create a consistent url hash regardless of scheme or site root.
     *
     * @param string $url
     *
     * @return string
     * @throws \Exception
     */
    public function createUrlHash($url)
    {
        return md5(str_replace(Factory::getPimpleContainer()->uri::root(), '', $url));
    }
}
