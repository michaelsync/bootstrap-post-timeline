<?php
//$rewrite_url = ( $wp_rewrite->using_permalinks() ) ? '<div class="rewrite_url">' : '';
//$url = add_query_arg(array('timeline_next' => ( $timeline_next + 1 )));

//  h3 attr
// removed name="<?php echo esc_attr($year) "
// removed  name="<?php echo $listedYear "
//

$year = substr($this->post_from_year, 0, 4);
?>
<div id="timeline" class="timeline <?php echo ($this->show_year_list) ? 'yearlist' : ''; ?>">
    <?php echo $this->yearList; ?>
    <h3 id="<?php echo esc_attr($year) ?>" data-yearhead="<?php echo esc_attr($year) ?>" class="year_head" data-toggle="collapse" data-target=".year-<?php echo esc_attr($year) ?>" aria-expanded="true"><span><?php echo $year ?></span></h3>
    <?php echo $this->posts; ?>
    <a class="item item-anchor" data-yearpost="<?php echo esc_attr($year) ?>" id="post-<?php echo implode('"></a><a class="item item-anchor" data-yearpost="' . esc_attr($year) . '" id="post-', $this->getPostIdsByYear($year)) ?>" ></a>

    <?php foreach ($this->moreYears as $listedYear => $postIds) { ?>
        <h3 id="<?php echo $listedYear ?>" data-yearhead="<?php echo $listedYear ?>" class="year_head" data-toggle="collapse" data-target=".year-<?php echo $listedYear ?>" aria-expanded="false"><span><?php echo $listedYear ?></span></h3>
        <ul class="year_posts year-<?php echo $listedYear ?> collapse" data-yearposts="<?php echo $listedYear ?>">
        </ul>
        <a class="item item-anchor" data-yearpost="<?php echo esc_attr($listedYear) ?>" id="post-<?php echo implode('"></a><a class="item item-anchor" data-yearpost="' . esc_attr($listedYear) . '" id="post-', $this->getPostIdsByYear($listedYear)) ?>" ></a>
    <?php } ?>
</div>
