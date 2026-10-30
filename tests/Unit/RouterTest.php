<?php

/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2026 Joomlashack.com. All rights reserved
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

namespace Tests\Unit;

use Alledia\OSMap\Router;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fixtures\SiteUri;

class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        SiteUri::setSite('https://www.example.com');
    }

    protected function tearDown(): void
    {
        SiteUri::reset();
    }

    public static function sanitizeProvider(): array
    {
        return [
            'double slashes'   => ['https://www.example.com//blog//article', 'https://www.example.com/blog/article'],
            'many slashes'     => ['https://www.example.com/blog////article', 'https://www.example.com/blog/article'],
            'scheme kept'      => ['https://www.example.com/blog', 'https://www.example.com/blog'],
            'relative'         => ['index.php//?option=com_content', 'index.php/?option=com_content'],
            'trailing slash'   => ['https://www.example.com/blog/', 'https://www.example.com/blog/'],
            'empty'            => ['', null],
            'null'             => [null, null],
        ];
    }

    #[DataProvider('sanitizeProvider')]
    public function testSanitizeURL(?string $url, ?string $expected)
    {
        $this->assertSame($expected, (new Router())->sanitizeURL($url));
    }

    public static function removeHashProvider(): array
    {
        return [
            'hash'       => ['https://www.example.com/page#section', 'https://www.example.com/page'],
            'only hash'  => ['#top', ''],
            'no hash'    => ['https://www.example.com/page', 'https://www.example.com/page'],
            'whitespace' => [' https://www.example.com/page ', 'https://www.example.com/page'],
            'empty'      => ['', null],
            'null'       => [null, null],
        ];
    }

    #[DataProvider('removeHashProvider')]
    public function testRemoveHashFromURL(?string $url, ?string $expected)
    {
        $this->assertSame($expected, (new Router())->removeHashFromURL($url));
    }

    public function testRouteURLLeavesNonRoutableUrlsAlone()
    {
        $router = new Router();

        $this->assertSame('https://www.example.com/page', $router->routeURL('https://www.example.com/page'));
        $this->assertSame('images/photo.jpg', $router->routeURL('images/photo.jpg', true));
    }

    public static function internalUrlProvider(): array
    {
        return [
            'relative path'          => ['images/photo.jpg', true],
            'root-relative path'     => ['/images/photo.jpg', true],
            'non-SEF link'           => ['index.php?option=com_content&view=article&id=1', true],
            'absolute site link'     => ['https://www.example.com/index.php?option=com_content', true],
            'absolute site page'     => ['https://www.example.com/blog/article', true],
            'site root'              => ['https://www.example.com', true],
            'other site'             => ['https://www.other.com/blog/article', false],
            'other site non-SEF'     => ['https://www.other.com/index.php?option=com_content', false],
            'site name in other url' => ['https://www.other.com/?ref=www.example.com', false],
        ];
    }

    #[DataProvider('internalUrlProvider')]
    public function testIsInternalURL(string $url, bool $expected)
    {
        $this->assertSame($expected, (new Router())->isInternalURL($url));
    }

    public function testIsInternalURLInSubfolderSite()
    {
        SiteUri::setSite('https://www.example.com/joomla');

        $router = new Router();

        $this->assertTrue($router->isInternalURL('https://www.example.com/joomla/blog/article'));
        $this->assertFalse($router->isInternalURL('https://www.other.com/joomla/blog/article'));
    }

    public static function relativeUriProvider(): array
    {
        return [
            'relative path'      => ['images/photo.jpg', true],
            'root-relative path' => ['/images/photo.jpg', true],
            'with query'         => ['index.php?option=com_content', false],
            'absolute'           => ['https://www.example.com/images/photo.jpg', false],
            'empty'              => ['', false],
            'null'               => [null, false],
        ];
    }

    #[DataProvider('relativeUriProvider')]
    public function testIsRelativeUri(?string $url, bool $expected)
    {
        $this->assertSame($expected, (new Router())->isRelativeUri($url));
    }

    public static function fullUriProvider(): array
    {
        return [
            'relative path'      => ['images/photo.jpg', 'https://www.example.com/images/photo.jpg'],
            'root-relative path' => ['/images/photo.jpg', 'https://www.example.com/images/photo.jpg'],
            'absolute'           => ['https://cdn.example.com/photo.jpg', 'https://cdn.example.com/photo.jpg'],
        ];
    }

    #[DataProvider('fullUriProvider')]
    public function testConvertRelativeUriToFullUri(string $path, string $expected)
    {
        $this->assertSame($expected, (new Router())->convertRelativeUriToFullUri($path));
    }

    public function testConvertRelativeUriToFullUriInSubfolderSite()
    {
        SiteUri::setSite('https://www.example.com/joomla');

        $router = new Router();

        $this->assertSame(
            'https://www.example.com/joomla/images/photo.jpg',
            $router->convertRelativeUriToFullUri('images/photo.jpg')
        );
        $this->assertSame(
            'https://www.example.com/joomla/images/photo.jpg',
            $router->convertRelativeUriToFullUri('/joomla/images/photo.jpg'),
            'Root-relative paths already include the subfolder'
        );
    }

    public function testCreateUrlHashIgnoresSiteRoot()
    {
        $router = new Router();

        $this->assertSame(md5('blog/article'), $router->createUrlHash('https://www.example.com/blog/article'));
        $this->assertSame(
            $router->createUrlHash('blog/article'),
            $router->createUrlHash('https://www.example.com/blog/article')
        );
        $this->assertSame(md5(''), $router->createUrlHash(null));
    }
}
