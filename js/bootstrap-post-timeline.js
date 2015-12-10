/*
 Plugin Name: Infinite Timeline
 infinite-timeline.js
 Version: 1.0
 */
jQuery(function () {
    if (0 == jQuery('#infinite_timeline').length) {
        return;
    }

    jQuery(window).load(function () {

        path = '';
        if (jQuery('#infinite_timeline .rewrite_url').length) {
            // using_permalinks
            path = new Array();
            path.push(location.href + '?infinite_timeline_next=');
            path.push("");
        }

        // infinitescroll
        var loading = jQuery('#infinite_timeline img.loading').attr('src');
        jQuery('#infinite_timeline').infinitescroll({
            navSelector: "#infinite_timeline .pagenation",
            nextSelector: "#infinite_timeline .pagenation a",
            itemSelector: "#infinite_timeline .box",
            loading: {
                img: loading,
            },
            path: path
        },
        function (newElements) {
            // Loaded
            jQuery(newElements).imagesLoaded(function () {
                infinite_timeline_adjust_vertical_position(newElements);
                jQuery('#infscr-loading').remove();
                updateYearList();
            });
        });

        function updateYearList() {
            var scrollPadding = 90;
            // time line year leggend + proporcjonalne lata do postów = odległość head od top
            if (0 == jQuery('#infinite_timeline .timeline-year-list').length) {
                jQuery('#infinite_timeline').prepend('<div class="timeline-container"><ul class="timeline-year-list nav"></ul></div>');
            }
            // cal space
            var yearsCount = jQuery('.year_head').length;
            var itemsTotalCount = jQuery('#infinite_timeline .item').length;
            var totalSpace = (jQuery(window).height() - scrollPadding - 15 - (yearsCount * 24)) / yearsCount;
            var spaceForItem = totalSpace / itemsTotalCount;
            var first = true;

            jQuery('#infinite_timeline .timeline-year-list').empty();
//            add id="year-list-top"
            jQuery('#infinite_timeline .timeline-year-list').append('<li class="year-list-item-top"><a href="#timeline-top"><i class="fa fa-home"></i></a></li>');

            jQuery.when(
                    jQuery.each(jQuery('.year_head'), function () {
                        var year = jQuery(this).data().yearpost;
                        var yearItemsCount = jQuery(jQuery('[data-yearpost=' + year + ']')).children('.item').length;

                        var listItem = jQuery('<li class="year-list-item"><span></span><a href="#' + jQuery(this).attr('name') + '">' + jQuery(this).text() + '</a></li>');
                        if (!first) {
                            listItem.css('margin-top', yearItemsCount * spaceForItem + 'px');
                        }
                        first = false;
                        jQuery('#infinite_timeline .timeline-year-list').append(listItem);
                    })
                    ).done(function () {
                var timelineTop = jQuery('#infinite_timeline').offset().top
                jQuery('#infinite_timeline .timeline-year-list').affix({
                    offset: {
                        top: function () {
                            return (this.top = timelineTop - scrollPadding);
                        },
                        bottom: function () {
                            return (this.bottom = jQuery(document).height() - (timelineTop + jQuery('#infinite_timeline').height()) + scrollPadding)
                        }
                    }
                });
            });
        }
        infinite_timeline_adjust_vertical_position(0);
        updateYearList();
        // run init func with delay, wiat for resize to finish, + wait for css animations to finish!
        var resizeTimer;
        jQuery(window).on('resize', function (e) {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                // Run code here, resizing has "stopped"
                updateYearList();
            }, 400);
        });
    });
});

/////////////
// adjust vertical position of days gone by
function infinite_timeline_adjust_vertical_position(newElements) {

    var elements;
    if (newElements) {
        // more
        elements = jQuery(newElements).find('.item');
    }
    else {
        // Initialize
        elements = jQuery('#infinite_timeline .item');
    }

    elements.each(function (i, elem) {
        // this post position
        var top = parseInt(jQuery(this).offset().top);
        var bottom = top + parseInt(jQuery(this).outerHeight());

        // prev post position
        var bottom_prev = 0;
        if (jQuery(this).prev().length) {
            bottom_prev = parseInt(jQuery(this).prev().offset().top + jQuery(this).prev().outerHeight());
        }

        if (bottom_prev >= bottom) {
            // adjust this post position under the prev post
            var height = parseInt(jQuery(this).find('.title').height());
            if (jQuery(this).find('img').length) {
                height = parseInt(jQuery(this).find('img').height());
            }

            var margin_top = parseInt(jQuery(this).css('margin-top'));
            margin_top += bottom_prev - top - height;
            jQuery(this).css('margin-top', margin_top + 'px');
        }
    });
}