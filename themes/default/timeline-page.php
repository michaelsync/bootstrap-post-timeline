<?php
//$rewrite_url = ( $wp_rewrite->using_permalinks() ) ? '<div class="rewrite_url">' : '';
//$url = add_query_arg(array('timeline_next' => ( $timeline_next + 1 )));
?>
<div id="timeline" class="<?php echo ($this->year_list)? 'yearlist' : ''; ?>">
    <?php echo $this->yearList; ?>
    <?php echo $this->posts; ?>

    <?php foreach ($this->moreYears as $listedYear => $postIds) { ?>
        <h3 id="<?php echo $listedYear ?>" name="<?php echo $listedYear ?>" data-yearhead="<?php echo $listedYear ?>" class="year_head" data-toggle="collapse" role="button" data-target=".year-<?php echo $listedYear ?>" aria-expanded="false"><span><?php echo $listedYear ?></span></h3>
        <ul class="year_posts year-<?php echo $listedYear ?> collapse" data-yearpost="<?php echo $listedYear ?>">
            <li class="item" id="post-<?php echo implode('"></li><li class="item" id="post-', $this->getPostIdsByYear($listedYear)) ?>" ></li>
        </ul>
    <?php } ?>
<?php /* 
    <div class="pagenation">
        <a href="<?php echo $url ?>"><?php __('More', 'bootstrap-post-timeline') ?></a>
        <img src="<?php echo plugins_url(dirname('/' . plugin_basename(__FILE__))) . '/images/loading.gif' ?>" alt="" class="loading"><?php echo $rewrite_url ?>
    </div>
*/ ?>
</div>
