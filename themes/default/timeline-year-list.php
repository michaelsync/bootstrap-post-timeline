<div class="timeline-container">
    <ul class="timeline-year-list nav">
        <li class="year-list-item-top"><a href="#timeline"><i class="fa fa-home"></i></a></li>' .
        <?php
        foreach ($this->yearListItems as $listedYear) {
            if (!trim($listedYear)) {
              continue;
            }
            preg_match_all('/<a .*?>(.*?)<\/a>/', $listedYear, $matches);
            $listedYear = $matches[1][0];
            ?>
            <li data-yeartarget="<?php echo $listedYear ?>" class="year-list-item"><span></span><a href="#<?php echo $listedYear ?>"><?php echo $listedYear ?></a></li>
<?php } ?>
    </ul>
</div>