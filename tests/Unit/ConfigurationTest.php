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

use Alledia\OSMap\Configuration;
use Codeception\Test\Unit;

class ConfigurationTest extends Unit
{
    protected function createConfiguration(): Configuration
    {
        return new Configuration([
            'name'   => 'OSMap',
            'empty'  => null,
            'nested' => [
                'level1' => [
                    'level2' => 'deep',
                ],
            ],
            'object' => (object)['child' => ['value' => 'from object']],
        ]);
    }

    public function testIsValid()
    {
        $this->assertTrue((new Configuration())->isValid());
    }

    public function testGetTopLevelValue()
    {
        $configuration = $this->createConfiguration();

        $this->assertSame('OSMap', $configuration->get('name'));
        $this->assertSame('default', $configuration->get('missing', 'default'));
        $this->assertSame('default', $configuration->get('empty', 'default'), 'Null counts as missing');
    }

    public function testGetDottedPath()
    {
        $configuration = $this->createConfiguration();

        $this->assertSame('deep', $configuration->get('nested.level1.level2'));
        $this->assertSame(['level2' => 'deep'], $configuration->get('nested.level1'));
        $this->assertSame('from object', $configuration->get('object.child.value'));
    }

    public function testGetMissingDottedPathReturnsDefault()
    {
        $configuration = $this->createConfiguration();

        $this->assertSame('default', $configuration->get('nested.missing.level2', 'default'));
        $this->assertSame('default', $configuration->get('nested.level1.missing', 'default'));
        $this->assertSame('default', $configuration->get('name.child', 'default'));
    }

    public function testSetReturnsPreviousValue()
    {
        $configuration = $this->createConfiguration();

        $this->assertSame('OSMap', $configuration->set('name', 'Changed'));
        $this->assertSame('Changed', $configuration->get('name'));
        $this->assertNull($configuration->set('new', 'value'));
    }

    public function testSetDottedPathCreatesTree()
    {
        $configuration = new Configuration();

        $configuration->set('a.b.c', 'value');

        $this->assertSame(['b' => ['c' => 'value']], $configuration->get('a'));
    }

    public function testSetDottedPathReplacesScalarBranch()
    {
        $configuration = new Configuration(['a' => 'scalar']);

        $configuration->set('a.b', 'value');

        $this->assertSame(['b' => 'value'], $configuration->get('a'));
    }

    public function testSetNullRemovesDottedValue()
    {
        $configuration = $this->createConfiguration();

        $this->assertSame('deep', $configuration->set('nested.level1.level2', null));
        $this->assertSame([], $configuration->get('nested.level1'));
    }

    public function testToConfig()
    {
        $configuration = $this->createConfiguration();

        $copy = $configuration->toConfig();
        $copy->set('name', 'Copy');
        $this->assertSame('OSMap', $configuration->get('name'), 'The copy is independent');

        $branch = $configuration->toConfig('nested.level1');
        $this->assertInstanceOf(Configuration::class, $branch);
        $this->assertSame('deep', $branch->get('level2'));
    }

    public function testConversions()
    {
        $configuration = $this->createConfiguration();

        $this->assertSame('{"level2":"deep"}', $configuration->toString('nested.level1'));
        $this->assertEquals((object)['level2' => 'deep'], $configuration->toObject('nested.level1'));
        $this->assertSame(['level2' => 'deep'], $configuration->toArray('nested.level1'));
        $this->assertSame(['value' => 'from object'], $configuration->toArray()['object']['child']);
    }
}
