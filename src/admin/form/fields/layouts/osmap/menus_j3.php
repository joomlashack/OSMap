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

HTMLHelper::_('jquery.ui', ['core', 'sortable']);
HTMLHelper::_('script', 'jui/sortablelist.js', false, true);
HTMLHelper::_('stylesheet', 'jui/sortablelist.css', false, true, false);

$sortableId = $id . '_menus';

$script = <<<JSCRIPT
;jQuery(document).ready(function ($) { 
    let \$menus = $('#{$sortableId}');
    
    \$menus.sortable({appendTo: document.body})
        .on('sortupdate', function() {
            let ordering = $(this).sortable('toArray').toString();
            $('#{$id}_menus_ordering').val(ordering);
        })
        .trigger('sortupdate');
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
<div class="osmap-table">
    <div class="osmap-list-header">
        <div class="osmap-cell osmap-col-selected"><?php echo $selectedLabel; ?></div>
        <div class="osmap-cell osmap-col-title"><?php echo $titleLabel; ?></div>
        <div class="osmap-cell osmap-col-priority"><?php echo $priorityLabel; ?></div>
        <div class="osmap-cell osmap-col-changefreq"><?php echo $changeFrequencyLabel; ?></div>
    </div>
    <ul id="<?php echo $sortableId; ?>" class="ul_sortable">
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
            <li id="<?php echo 'menu_' . $option->value; ?>"
                class="osmap-menu-item">
                <div class="osmap-cell osmap-col-selected">
                    <input <?php echo ArrayHelper::toString($currentAttributes); ?>/>
                </div>

                <div class="osmap-cell osmap-col-title">
                    <label for="<?php echo $thisId . '_id'; ?>" class="menu_label">
                        <?php echo $option->text; ?>
                    </label>
                </div>

                <div class="osmap-cell osmap-col-priority osmap-menu-options">
                    <?php echo $changePriorityField; ?>
                </div>

                <div class="osmap-cell osmap-col-changefreq osmap-menu-options">
                    <?php echo $changeChangeFreqField; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <input type="hidden"
           id="<?php echo $id . '_menus_ordering'; ?>"
           name="<?php echo sprintf('%s[menus_ordering]', $field->formControl); ?>"
           value=""/>
</div>
