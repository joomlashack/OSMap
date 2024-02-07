<?php
/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2021-2024 Joomlashack.com. All rights reserved
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
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') or die();

/**
 * @var FileLayout $this
 * @var array      $displayData
 * @var string     $layoutOutput
 * @var string     $path
 */

/**
 * @var string    $autocomplete
 * @var boolean   $autofocus
 * @var string    $class
 * @var string    $description
 * @var boolean   $disabled
 * @var FormField $field
 * @var NULL      $group
 * @var boolean   $hidden
 * @var string    $hint
 * @var string    $id
 * @var string    $label
 * @var string    $labelclass
 * @var boolean   $multiple
 * @var string    $name
 * @var string    $onchange
 * @var string    $onclick
 * @var string    $pattern
 * @var string    $validationtext
 * @var boolean   $readonly
 * @var boolean   $repeat
 * @var boolean   $required
 * @var integer   $size
 * @var boolean   $spellcheck
 * @var string    $validate
 * @var array[]   $value
 * @var object[]  $options
 */
extract($displayData);

$attributes = [
    'type'  => $multiple ? 'checkbox' : 'radio',
    'size'  => $size,
    'class' => $class,
];

$sortableId = $id . '_menus';

HTMLHelper::_('jquery.framework');
HTMLHelper::_('draggablelist.draggable');

$script = <<<JSCRIPT
;jQuery(document).ready(function ($) { 
    let \$ordering = $('#{$id}_menus_ordering'),
        drake      = dragula([document.querySelector('.osmap-draggable')]);

    let menuItems = function() {
        return $('#{$sortableId}').find('[id^="{$id}_"]:checkbox');
    };
            
    let setOrdering = function() {
        let menu_ordering = [];
        
        menuItems().each(function() {
            if (this.checked) {
                menu_ordering.push('menu_' + this.value);
            }
        });
        \$ordering.val(menu_ordering.join(','));
    };
    
    menuItems().on('click', setOrdering);
    drake.on('drop', setOrdering);
    setOrdering();
});
JSCRIPT;

Factory::getDocument()->addScriptDeclaration($script);

if ($disabled || $readonly) :
    $attributes['disabled'] = 'disabled';
endif;

$changeFrequencyLabel = Text::_('COM_OSMAP_CHANGE_FREQUENCY_LABEL');
$priorityLabel        = Text::_('COM_OSMAP_PRIORITY_LABEL');
$selectedLabel        = Text::_('COM_OSMAP_SELECTED_LABEL');
$titleLabel           = Text::_('COM_OSMAP_TITLE_LABEL');
?>
<table id="<?php echo $sortableId; ?>" class="adminlist table table-striped">
    <thead>
    <tr>
        <th scope="col" class="w-1 text-center"><?php echo $selectedLabel; ?></th>
        <th scope="col" class="w-10"><?php echo $titleLabel; ?></th>
        <th scope="col" class="w-5"><?php echo $priorityLabel; ?></th>
        <th scope="col" class="w-5"><?php echo $changeFrequencyLabel; ?></th>
        <th scope="col">&nbsp;</th>
    </tr>
    </thead>

    <tbody class="osmap-draggable">
    <?php
    $currentItems            = array_keys($value);
    $nameRegex               = sprintf('/(%s\[[^]]+)(].*)/', $field->formControl);

    foreach ($options as $idx => $option) :
        $prioritiesName = preg_replace($nameRegex, '$1_priority$2', $name);
        $changeFrequencyName = preg_replace($nameRegex, '$1_changefreq$2', $name);
        $selected            = isset($value[$option->value]);
        $thisId              = $id . '_' . $idx;

        $changePriorityField   = HTMLHelper::_(
            'osmap.priorities',
            $prioritiesName,
            ($selected ? number_format($value[$option->value]['priority'], 1) : '0.5'),
            $idx
        );
        $changeChangeFreqField = HTMLHelper::_(
            'osmap.changefrequency',
            $changeFrequencyName,
            ($selected ? $value[$option->value]['changefreq'] : 'weekly'),
            $idx
        );

        $currentAttributes = array_filter(
            array_merge(
                $attributes,
                [
                    'id'    => $thisId,
                    'name'  => $name,
                    'value' => $option->value
                ]
            )
        );
        if ($selected) :
            $currentAttributes['checked'] = 'checked';
        endif;

        ?>
        <tr>
            <td class="text-center">
                <input <?php echo ArrayHelper::toString($currentAttributes); ?>/>
            </td>

            <td class="text-nowrap">
                <label for="<?php echo $thisId . '_id'; ?>" class="menu_label">
                    <?php echo $option->text; ?>
                </label>
            </td>

            <td class="w2">
                <?php echo $changePriorityField; ?>
            </td>

            <td class="w5">
                <?php echo $changeChangeFreqField; ?>
            </td>

            <td>&nbsp;</td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<input type="hidden"
       id="<?php echo $id . '_menus_ordering'; ?>"
       name="<?php echo sprintf('%s[menus_ordering]', $field->formControl); ?>"
       value=""/>
