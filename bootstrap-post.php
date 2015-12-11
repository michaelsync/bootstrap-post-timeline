<?php

/*
  To change this license header, choose License Headers in Project Properties.
  To change this template file, choose Tools | Templates
  and open the template in the editor.
 */
$time_last = false;
$opened = false;
foreach ($myposts as $post) {
    setup_postdata($post);

    $title = get_the_title();

    $add_class = '';
    if ($count % 2) {
        $add_class .= ' right';
    } else {
        $add_class .= ' left';
    }

// days gone by
    $time_current = (integer) get_post_time();
    if (!$time_last) {
        $opened = true;
        $time_last = (integer) get_post_time();
    }

    $year = (integer) get_post_time('Y');
    if ($year != $year_last) {
        if ($count) {
            $output .= '</ul>';
        }

                        if ($year <> $year_prev) {
            $output .= '<h3 id="' . esc_attr($year) . '" name="' . esc_attr($year) . '" data-yearpost="' . esc_attr($year) . '" class="year_head" data-toggle="collapse" role="button" data-target=".year-'.esc_attr($year).'" aria-expanded="'.(($opened)? 'true' : 'false').'">' . $year . '</h3>';
        }


        $year_last = $year;
        $year_top = 1;
        $output .= '<ul class="year_posts year-'.esc_attr($year).' collapse '.(($opened)? 'in' : '').'" data-yearpost="' . esc_attr($year) . '">';
    }

    $days = ceil(abs($time_current - $time_last) / (60 * 60 * 24));
    $time_last = $time_current;
    $opened = FALSE;

    $add_style = '';
    if ($year_top) {
        $add_class .= ' year_top';
    } else {
        $add_style = ' style="margin-top: ' . $days . 'px;"';
    }

    $size = 'large';
    if (wp_is_mobile()) {
        $size = 'medium';
    }

    if (!empty($post->post_excerpt)) {
        $content = $post->post_excerpt;
    } else {
        $pieces = get_extended($post->post_content);
        //var_dump($pieces); // debug
        $content = apply_filters('the_content', $pieces['main']);
    }
    $subtitle = get_post_meta($post->ID, 'timeline_subtitle', true);

    $output .= '<li id="post-' . $post->ID . '" name="post-' . $post->ID . '" class="item' . $add_class . '"' . $add_style . '>';
    $output .= '<div class="item-content">';
    $output .= '<a href="' . get_permalink() . '">';
    $output .= get_the_post_thumbnail($post->ID, $size);
//				$output .= '<div class="title">' .get_post_time( get_option( 'date_format' ) ) .'<br>' .$title .'</div>';
    $output .= '<h4 class="title">' . $title . '</h4>';
    $output .= (!empty($subtitle)) ? '<h5 class="subtitle">' . $subtitle . '</h5>' : '';
    $output .= $content;
    $output .= '</a>';
    $output .= '</div>';
    $output .= '</li>';

    $count++;
    $year_top = 0;
}

// output
if ($count) {
    $rewrite_url = ( $wp_rewrite->using_permalinks() ) ? '<div class="rewrite_url">' : '';
    $url = add_query_arg(array('timeline_next' => ( $timeline_next + 1 )));
    $output = '<div id="infinite_timeline">' . $output . '</div><div class="pagenation"><a href="' . $url . '">' . __('More', 'bootstrap-post-timeline') . '</a><img src="' . plugins_url(dirname('/' . plugin_basename(__FILE__))) . '/images/loading.gif" alt="" class="loading">' . $rewrite_url . '</div></div>';
}
?>