<?php
/*
  To change this license header, choose License Headers in Project Properties.
  To change this template file, choose Tools | Templates
  and open the template in the editor.
 */
$time_last = 0;
$year_top = true;
$count = 0;
$year = substr($this->post_from_year, 0, 4);

$opened = true;
?>
<ul class="year_posts year-<?php echo esc_attr($year) ?> collapse <?php echo (($opened) ? 'in' : '') ?>" data-yearposts="<?php echo esc_attr($year) ?>">
    <?php
    while ($the_query->have_posts()) {
        $the_query->the_post();
        $post = $the_query->post;
        setup_postdata($post);

        $opened = false;
        // days gone by
        $time_current = (integer) get_post_time();
        if (!$time_last) {
            $opened = true;
            $time_last = (integer) get_post_time();
        }

        $days = ceil(abs($time_current - $time_last) / (60 * 60 * 24));
        $time_last = $time_current;

        $add_class = '';
        $add_style = '';
        if ($year_top) {
            $add_class .= ' year_top';
        } else {
            $add_style = ' style="margin-top: ' . $days . 'px;"';
        }

        if ($count % 2) {
            $add_class .= ' right';
        } else {
            $add_class .= ' left';
        }

        $title = get_the_title();
        $subtitle = get_post_meta($post->ID, 'timeline_subtitle', true);

        if (!empty($post->post_excerpt)) {
            $content = $post->post_excerpt;
        } else {
            $pieces = get_extended($post->post_content);
            //var_dump($pieces); // debug
            $content = apply_filters('the_content', $pieces['main']);
        }
        ?>
        <li id="post-<?php echo $post->ID ?>" name="post-<?php echo $post->ID; ?>" data-type="timeline_post" data-yearpost="<?php echo esc_attr($year) ?>" data-date="<?php echo get_post_time(get_option('date_format')); ?>" class="item<?php echo $add_class ?>" <?php echo $add_style ?>>
            <div class="item-content">
                <a href="<?php echo get_permalink(); ?>">
                    <?php echo get_the_post_thumbnail($post->ID, (wp_is_mobile() ? 'medium' : 'large')); ?>
                    <h4 class="title"><?php echo $title ?></h4>
                    <?php echo (!empty($subtitle)) ? '<h5 class="subtitle">' . $subtitle . '</h5>' : ''; ?>
                    <h6><?php echo get_post_time(get_option('date_format')); ?></h6>
                    <?php echo $content ?>
                </a>
            </div>
        </li>
        <?php
        $count++;
        $year_top = 0;
    }
    ?>
</ul><?php if ($this->pagination) { ?>
    <a class="loadmore" title="Load more post from this Year" href="<?php echo $this->pagination; ?>"><-- Load more --></a>
<?php } ?>
