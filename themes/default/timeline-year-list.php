<div class="timeline-container">
    <ul class="timeline-year-list nav">
        <li class="year-list-item-top"><a href="#timeline"><i class="fa fa-home"></i></a></li>
        <?php
        foreach ($this->yearListItems as $listedYear) {
            ?>
            <li data-yeartarget="<?php echo $listedYear ?>" class="year-list-item"><span></span><a href="#<?php echo $listedYear ?>"><?php echo $listedYear ?></a></li>
<?php } ?>
    </ul>
</div>