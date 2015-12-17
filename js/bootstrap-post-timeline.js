/*
 Plugin Name: Infinite Timeline
 infinite-timeline.js
 Version: 1.0
 */
jQuery(function ($) {
    if (0 == jQuery('#timeline').length) {
        return;
    }
    function updateYearList() {
        var scrollPadding = 90;
        var screenFraction = 0.8;
        // cal space
        var yearsCount = jQuery('.year_head').length;
        var itemsTotalCount = jQuery('#timeline .item').length;
        var totalSpace = (jQuery(window).height() - scrollPadding - 15 - (yearsCount * 24)) * screenFraction;
        var spaceForItem = totalSpace / itemsTotalCount;
        var first = true;

        jQuery.when(
                jQuery.each(jQuery('[data-yearhead]'), function () {
                    var year = jQuery(this).data().yearhead;
                    var yearItemsCount = jQuery(jQuery('[data-yearpost=' + year + ']')).children('.item').length;

                    if (!first) {
                        jQuery('[data-yeartarget=' + year + ']').css('margin-top', yearItemsCount * spaceForItem + 'px');
                    }
                    first = false;
                })
                ).done(function () {

        });
    }
    function resetAffix(scrollPadding) {
        jQuery('#timeline .timeline-year-list').affix('checkPosition');
        jQuery('#timeline .timeline-year-list').data('bs.affix').options.offset = {
            top: function () {
                return (this.top = jQuery('#timeline').offset().top - scrollPadding);
            },
            bottom: function () {
                return (this.bottom = jQuery(document).height() - (jQuery('#timeline').offset().top + jQuery('#timeline').height()) + scrollPadding);
            }
        };

    }
    jQuery(window).load(function () {
        var scrollPadding = 90;
        updateYearList();

        // run init func with delay, wiat for resize to finish, + wait for css animations to finish!
        var resizeTimer;
        jQuery(window).on('resize', function (e) {
            resetAffix(scrollPadding);
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                // Run code here, resizing has "stopped"
                updateYearList();
            }, 400);
        });

        jQuery('#timeline .timeline-year-list').affix({
            offset: {
                top: function () {
                    return (this.top = jQuery('#timeline').offset().top - scrollPadding);
                },
                bottom: function () {
                    return (this.bottom = jQuery(document).height() - (jQuery('#timeline').offset().top + jQuery('#timeline').height()) + scrollPadding);
                }
            }
        });

        $('#timeline .timeline-year-list').on("click", "[data-yeartarget]", function () {
            var year = jQuery(this).data().yeartarget;
            jQuery('[data-yearpost=' + year + ']').collapse('show');
            // animate to
            $('html, body').animate({
                scrollTop: jQuery('[data-yearhead=' + year + ']').offset().top - 60
            }, 300, function () {
                // when done, add hash to url
                // (default click behaviour)
                // window.location.hash = hash;
            });
        });

        $('[data-yearpost]').on('shown.bs.collapse hidden.bs.collapse', function () {
            resetAffix(scrollPadding);
        });

        $('[data-yearpost]').on('show.bs.collapse', function () {
            var year = jQuery(this).data().yearpost;

            // if empty load posts
            if (jQuery(this).children('[data-type="timeline_post"]').length === 0) {
                var data = {
                    year: year,
                    action: 'getpost_by_year',
                    security: bpt.security,
                };
                function loadHtml(the_query) {
//                jQuery(this).append(the_query);
                    updateYearList();
                }
                jQuery(this).load(bpt.ajaxurl, data, loadHtml);
            } else {
                updateYearList();
            }

            // if loadmore.length

        })
    });
});
