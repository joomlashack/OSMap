/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2007-2014 XMap - Joomla! Vargas - Guillermo Vargas. All rights reserved.
 * @copyright 2016-2023 Joomlashack.com. All rights reserved.
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

;(function($) {
    $.osmap = $.extend({}, $.osmap);

    /**
     * @param {Object} params
     */
    $.osmap.sitemapItems = function(params) {
        this.$container = $(params.container);
        this.params     = params;
        this.url        = params.baseUri.replace(/\/$/, '')
            + '/index.php?option=com_osmap&view=adminsitemapitems&tmpl=component&id=' + params.sitemapId;

        if (params.language !== '') {
            this.url += '&lang=' + params.language;
        }

        // Init the change frequency dropdown template
        this.$frequencyField = $('<select>');
        for (let value in params.frequencies) {
            $('<option>')
                .attr('value', value)
                .text(params.frequencies[value])
                .appendTo(this.$frequencyField)
        }

        // Init the priority dropdown template
        this.$priorityField = $('<select>');
        for (let value of params.priorities) {
            $('<option>')
                .attr('value', value)
                .text(value)
                .appendTo(this.$priorityField)
        }

        this.load();
    };

    /**
     * @return void
     */
    $.osmap.sitemapItems.prototype.load = function() {
        let self = this;

        $.ajax({
            url    : this.url,
            async  : true,
            success: function(data) {
                self.$container.html(data);

                self.configureForm();

                $('.osmap-loading').remove();
            }
        });
    };

    /**
     * @return void
     */
    $.osmap.sitemapItems.prototype.configureForm = function() {
        let self      = this,
            $itemRows = $('tr.sitemapitem', this.$container);

        $itemRows
            .on('mouseenter', function() {
                self.setDropdown($('.sitemapitem-priority', this), self.$priorityField);
                self.setDropdown($('.sitemapitem-changefreq', this), self.$frequencyField);

                $(this).addClass('selected');
            })
            .on('mouseleave', function() {
                self.clearDropdown($('.sitemapitem-priority', this));
                self.clearDropdown($('.sitemapitem-changefreq', this));

                $(this).removeClass('selected');
            });

        this.initPublishing();

        $('.hasTooltip').tooltip();
    };

    /**
     * @param {jQuery} $cells
     * @param {jQuery} $template
     *
     * @return void
     */
    $.osmap.sitemapItems.prototype.setDropdown = function($cells, $template) {
        $cells.each(function() {
            let $this  = $(this),
                $input = $template.clone().val($this.data('value'));
            $(this).html('').append($input);

            $input.on('change', function() {
                    let $this = $(this);

                    $this.parent().data('value', $this.val());
                    $this.parents('tr').addClass('updated');
                }
            );
        });
    };

    /**
     * @param {jQuery} $cells
     *
     * @return void
     */
    $.osmap.sitemapItems.prototype.clearDropdown = function($cells) {
        $cells.text($cells.data('value'));
    }

    $.osmap.sitemapItems.prototype.initPublishing = function() {
        $('.sitemapitem-published', this.$container).on('click', function() {
            let $this     = $(this),
                newValue  = $this.data('value') === 1 ? 0 : 1,
                spanClass = newValue === 1 ? 'publish' : 'unpublish',
                $span     = $('span', this);

            $this.data('value', newValue);
            $this.parents('.sitemapitem').addClass('updated');

            $span
                .removeClass()
                .addClass('hasTooltip icon-' + spanClass)
                .attr(
                    'title',
                    newValue === 1
                        ? Joomla.JText._('COM_OSMAP_TOOLTIP_CLICK_TO_UNPUBLISH')
                        : Joomla.JText._('COM_OSMAP_TOOLTIP_CLICK_TO_PUBLISH')
                );

            $span.tooltip('fixTitle').tooltip('show');
        });
    }
})(jQuery);
