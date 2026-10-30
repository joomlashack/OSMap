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

use Alledia\OSMap\Helper\General;
use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use Tests\Support\Fixtures\NullDateDatabase;
use Tests\Support\Fixtures\TestFactory;

class GeneralTest extends Unit
{
    public function testGetPagebreaks()
    {
        $text = '<p>Page one</p>'
            . '<hr class="system-pagebreak" title="Second page" alt="Second alt">'
            . '<p>Page two</p>'
            . '<hr id="divider" title="Not a page break">'
            . '<hr class="system-pagebreak" alt="Third alt" title="Third page" />'
            . '<p>Page three</p>';

        $pages = General::getPagebreaks($text, 'index.php?option=com_content&view=article&id=5', 'joomla.article.5');

        $this->assertCount(2, $pages);

        $this->assertSame('Second alt', $pages[0]->name);
        $this->assertSame('joomla.article.5.1', $pages[0]->uid);
        $this->assertSame('index.php?option=com_content&view=article&id=5&limitstart=1', $pages[0]->link);
        $this->assertFalse($pages[0]->expandible);

        $this->assertSame('Third alt', $pages[1]->name);
        $this->assertSame('joomla.article.5.2', $pages[1]->uid);
        $this->assertSame('index.php?option=com_content&view=article&id=5&limitstart=2', $pages[1]->link);
    }

    public function testGetPagebreaksUsesTitleWithoutAlt()
    {
        $pages = General::getPagebreaks('<hr class="system-pagebreak" title="Only title">', 'index.php', 'uid');

        $this->assertCount(1, $pages);
        $this->assertSame('Only title', $pages[0]->name);
    }

    public function testGetPagebreaksWithoutPagebreaks()
    {
        $this->assertSame([], General::getPagebreaks('<p>One page</p><hr>', 'index.php', 'uid'));
    }

    public static function emptyDateProvider(): array
    {
        return [
            'empty string'    => ['', true],
            'null'            => [null, true],
            'false'           => [false, true],
            'zero'            => [0, true],
            'zero string'     => ['0', true],
            'minus one'       => [-1, true],
            'minus one str'   => ['-1', true],
            'null date'       => ['0000-00-00 00:00:00', true],
            'null day'        => ['0000-00-00', true],
            'date'            => ['2024-05-01 10:30:00', false],
            'day'             => ['2024-05-01', false],
            'timestamp'       => [1714559400, false],
            'timestamp string' => ['1714559400', false],
        ];
    }

    #[DataProvider('emptyDateProvider')]
    public function testIsEmptyDate($date, bool $expected)
    {
        TestFactory::setPimpleContainer(['db' => new NullDateDatabase()]);

        $this->assertSame($expected, General::isEmptyDate($date));
    }

    public function testIsEmptyDateUsesDatabaseNullDate()
    {
        TestFactory::setPimpleContainer(['db' => new NullDateDatabase('1000-01-01 00:00:00')]);

        $this->assertTrue(General::isEmptyDate('1000-01-01 00:00:00'));
    }

    public function testCallUserFuncCallsStaticMethod()
    {
        $instance = new GeneralTestPlugin();

        $this->assertSame(
            'static:a,b',
            General::callUserFunc(GeneralTestPlugin::class, $instance, 'staticMethod', ['a', 'b'])
        );
    }

    public function testCallUserFuncCallsInstanceMethod()
    {
        $instance = new GeneralTestPlugin();

        $this->assertSame(
            'instance:a',
            General::callUserFunc(GeneralTestPlugin::class, $instance, 'instanceMethod', ['a'])
        );
    }

    public function testCallUserFuncReturnsNullForMissingMethod()
    {
        $this->assertNull(
            General::callUserFunc(GeneralTestPlugin::class, new GeneralTestPlugin(), 'noSuchMethod')
        );
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
class GeneralTestPlugin
{
    public static function staticMethod(...$args): string
    {
        return 'static:' . implode(',', $args);
    }

    public function instanceMethod(...$args): string
    {
        return 'instance:' . implode(',', $args);
    }
}
