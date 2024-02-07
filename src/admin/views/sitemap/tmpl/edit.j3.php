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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die();

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('formbehavior.chosen', 'select');

$input = Factory::getApplication()->input;

$actionQuery = [
    'option' => 'com_osmap',
    'view'   => 'sitemap',
    'layout' => 'edit',
    'id'     => (int)$this->item->id
];
?>
<form action="<?php echo Route::_('index.php?' . http_build_query($actionQuery)); ?>"
      method="post"
      name="adminForm"
      id="adminForm"
      class="form-validate sitemap">

    <?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

    <div class="form-horizontal">
        <div class="row-fluid">
            <div class="span9">
                <?php echo $this->form->getField('menus')->renderField(['hiddenLabel' => true]); ?>
            </div>

            <div class="span3">
                <?php echo $this->form->renderFieldset('params'); ?>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value=""/>
    <input type="hidden" name="return" value="<?php echo $input->getCmd('return'); ?>"/>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

