/*
 Plugin Name: Infinite Timeline
 infinite-timeline.js
 Version: 1.0
 */
jQuery(function ($) {
    if (0 == jQuery('#timeline').length) {
        return;
    }

    function get_params_from_href(href) {
        var paramstr = href.split('?')[1];        // get what's after '?' in the href
        var paramsarr = paramstr.split('&');      // get all key-value items
        var params = {};
        for (var i = 0; i < paramsarr.length; i++) {
            var tmparr = paramsarr[i].split('='); // split key from value
            params[tmparr[0]] = tmparr[1];        // sort them in a arr[key] = value way
        }
        return params;
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
                    var yearItemsCount = jQuery(jQuery('[data-yearpost=' + year + ']')).length;

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

    function loadMore(offset) {

    }

    function viewPost(postId) {
        // find elem
        // if loaded show
        // else get post anchor offset and loadMore elements with offset
    }

    jQuery(window).load(function () {
        var scrollPadding = 90;
        updateYearList();

        // run init func with delay, wiat for resize to finish, + wait for css animations to finish!
        var resizeTimer;
        jQuery(window).on('resize', function (e) {// + DO IT ON height CHANGE
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
//        jQuery('#timeline .timeline-year-list').on("affix.bs.affix", function () {
//            resetAffix(scrollPadding);
//        });


        $('#timeline .timeline-year-list').on("click", "[data-yeartarget]", function () {
            var year = jQuery(this).data().yeartarget;
            jQuery('[data-yearposts=' + year + ']').collapse('show');
            // animate to
            $('html, body').animate({
                scrollTop: jQuery('[data-yearhead=' + year + ']').offset().top - 60
            }, 300, function () {
                // when done, add hash to url
                // (default click behaviour)
                // window.location.hash = hash;
            });
        });

        $('[data-yearposts]').on('shown.bs.collapse hidden.bs.collapse', function () {
            resetAffix(scrollPadding);
        });

        $('[data-yearposts]').on('show.bs.collapse', function () {
            console.log(this);
            var yearposts = jQuery(this);
            var year = yearposts.data().yearposts;

            // if empty load posts
            if (yearposts.children('[data-type="timeline_post"]').length === 0) {
                console.log(this);
                console.log(yearposts.children('[data-type="timeline_post"]'))
                var params = {
                    action: 'getpost_by_year',
                    from_year: year,
                    get: bpt.get,
                    start: 0, //bpt.start,
                    security: bpt.security,
                    paged: 1,
                    cur: 1,
                    max: 0
                };
//                jQuery(this).load(bpt.ajaxurl, data, loadHtml);
                jQuery.get(bpt.ajaxurl, params, function (data) {
                    // remove first "offset" number of post anchors
                    jQuery('a.item-anchor[data-yearpost="' + params.from_year + '"]').slice(0, params.get).remove();
                    // load posts first time

                    yearposts.html(jQuery(data).filter('[data-yearposts]').html());
                    yearposts.after(jQuery(data).filter('a.loadmore'));
                    updateYearList();
                }, 'html');
            } else {
                updateYearList();
            }
        });
        jQuery('#timeline').on("click", "a.loadmore", function (e) {
            e.preventDefault();
            var moreLink = jQuery(this);
            var urlParams = get_params_from_href(moreLink.attr('href'));
            var params = {
                action: 'getpost_by_year',
                security: bpt.security,
                get: bpt.get,
                start: 0, //(urlParams.paged-1) * bpt.get,
                from_year: urlParams.from_year,
                paged: urlParams.paged,
                cur: urlParams.cur,
                max: urlParams.max
            };
            jQuery.get(bpt.ajaxurl, params, function (data) {
                // remove first "offset" number of post anchors
                jQuery('a.item-anchor[data-yearpost="' + params.from_year + '"]').slice(0, params.get).remove();
                jQuery('[data-yearposts="' + params.from_year + '"]').last().after(jQuery(data));
                updateYearList();
            }).done(function () {
                moreLink.remove();
            });
            return false;
        });
    });
});
