<div class="panel-heading">
    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-8">
            <h4><?php echo $info['caption'];?></h4>
        </div>
        <div class="col-xs-6 col-md-4">
            <div class="btn-group pull-right">
                <?php
                foreach ($info['generalActions'] as $actionType => $action) {
                ?>
                    <?php echo $action['html'];?>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>