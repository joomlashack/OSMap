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

namespace Alledia\OSMap;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;

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
            return Route::link('site', $url, true, Route::TLS_IGNORE, true);
        }

        return Route::link('site', $url);
    }

    /**
     * Checks if the supplied URL is internal
     *
     * @param ?string $url
     *
     * @return bool
     */
    public function isInternalURL(?string $url): bool
    {
        $uri      = Uri::getInstance($url);
        $base     = $uri->toString(['scheme', 'host', 'port', 'path']);
        $host     = $uri->toString(['scheme', 'host', 'port']);
        $path     = $uri->toString(['path']);
        $baseHost = Uri::getInstance($uri::root())->toString(['host']);

        if ($path === $url) {
            return true;

        } elseif (
            empty($host)
            && strpos($path, 'index.php') === 0
            || empty($host) == false && preg_match('#' . preg_quote($uri::root(), '#') . '#', $base)
            || empty($host) == false && $host === $baseHost && strpos($path, 'index.php') !== false
            || empty($host) == false && $base === $host
            && preg_match('#' . preg_quote($base, '#') . '#', $uri::root())
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check if the given URL is a relative URI. Returns true, if affirmative.
     *
     * @param ?string $url
     *
     * @return bool
     */
    public function isRelativeUri(?string $url): bool
    {
        if ($url) {
            $urlPath = (new Uri($url))->toString(['path']);

            return $urlPath === $url;
        }

        return false;
    }

    /**
     * Converts an internal relative URI into a full link.
     *
     * @param string $path
     *
     * @return string
     */
    public function convertRelativeUriToFullUri(string $path): string
    {
        if (
            Version::MAJOR_VERSION > 3
            && strpos($path, '#joomlaImage:') !== false
        ) {
            $media = HTMLHelper::_('cleanImageURL', $path);
            $path  = $media->url;
        }

        if ($path[0] == '/') {
            $scheme = ['scheme', 'user', 'pass', 'host', 'port'];
            $path   = Uri::getInstance()->toString($scheme) . $path;

        } elseif ($this->isRelativeUri($path)) {
            $path = Uri::root() . $path;
        }

        return $path;
    }

    /**
     * Returns a sanitized URL, removing double slashes and trailing slash.
     *
     * @param ?string $url
     *
     * @return ?string
     */
    public function sanitizeURL(?string $url): ?string
    {
        if ($url) {
            return preg_replace('#([^:])(/{2,})#', '$1/', $url);
        }

        return null;
    }

    /**
     * Returns a URL without the hash
     *
     * @param ?string $url
     *
     * @return ?string
     */
    public function removeHashFromURL(?string $url): ?string
    {
        if ($url) {
            // Check if the URL has a hash to remove it (XML sitemap shouldn't have hash on the URL)
            $hashPos = strpos($url, '#');

            if ($hashPos !== false) {
                // Remove the hash
                $url = substr($url, 0, $hashPos);
            }

            return trim($url);
        }

        return null;
    }

    /**
     * Create a consistent url hash regardless of scheme or site root.
     *
     * @param ?string $url
     *
     * @return string
     */
    public function createUrlHash(?string $url): string
    {
        return md5(str_replace(Uri::root(), '', (string)$url));
    }
}
