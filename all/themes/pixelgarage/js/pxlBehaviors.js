/**
 * This file contains all Drupal behaviours of the Apia theme.
 *
 * Created by ralph on 05.01.14.
 */

(function ($) {

    /**
     * Place and color the links in the intro page correctly.
     */
    Drupal.behaviors.placeIntroLinks = {
        attach: function () {
            var $links = $('.node-introstep .field-name-field-course-links a');

            $links.each(function(i, e) {
                var $this = $(this);
                if ($this.hasClass('button-red')) {
                    $this.parent().addClass('button-red');
                } else if ($this.hasClass('link-special')){
                    var classes = $this.attr('class');
                    $this.parent().attr('class', 'field-item ' + classes);
                    $this.parent().parent().addClass('link-special');
                }
            });
        }
    };

    /**
     * Allows full size clickable items.
     */
    Drupal.behaviors.fullSizeClickableItems = {
        attach: function () {
            var $block = $('#block-views-courses-block-in-depth-menu').add('#block-multiblock-2'),
                $clickableItems = $block.find('.views-row');

            $block.once('click', function () {
                $clickableItems.on('click', function () {
                    window.location = $(this).find(".field-item a:first").attr("href");
                    return false;
                });
            });
        }
    };

    /**
     * This behavior adds active state to link wrappers to support image links.
     */
    Drupal.behaviors.subMenuLinks = {
        attach: function () {
            var $block = $('#block-views-courses-block-course-menu').add('#block-multiblock-1'),
                $linkWrappers = $block.find('.views-row .field-content'),
                $activeWrapper = $block.find('.views-row a.active').parent().addClass('active');

            $block.once('click', function() {
                $linkWrappers.on('click', function() {
                    $activeWrapper.removeClass("active");
                    $(this).addClass("active");
                    $activeWrapper = $(this);
                });
            });
        }
    };

    /**
     * Generates tabs for header links.
     */
    Drupal.behaviors.block_with_header_links = {
        attach: function (context) {
            $('.block-views-contacts-block-course-contacts, #block-views-locations-block', context).once(function() {
                var _header = $('<ul class="header-links" />');
                $(this).find('.view .item-list>h3').each(function(i, e) {
                    var _s = $(this);
                    var _l = $('<li />').html('<a href="#" data-i="' + i + '">' + _s.html() + '</a>');
                    _header.append(_l);
                    _s.remove();
                });
                $(this).find('.view').prepend(_header);
                $(this).find('.header-links a').click(function() {
                    $(this).closest('ul').find('a').removeClass('active');
                    $(this).addClass('active').closest('.view').find('.item-list').hide().filter(':eq(' + $(this).data('i') + ')').show();
                    return false;
                }).filter(':first').click();
            });
        }
    };

    /**
     * This behavior adds shadow to header on scroll.
     *
     */
    Drupal.behaviors.addHeaderShadow = {
        attach: function (context) {
            $(window).on("scroll", function() {
                if ($(window).scrollTop() > 10) {
                    $("header.navbar .container").css( "box-shadow", "0 4px 3px -4px gray");
                } else {
                    $("header.navbar .container").css( "box-shadow", "none");
                }
            });
        }
    };



})(jQuery);