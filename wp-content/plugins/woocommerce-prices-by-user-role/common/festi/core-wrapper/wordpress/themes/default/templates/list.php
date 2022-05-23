<?php

$notifications = Controller::getInstance()->popParam("notifications");
if ($notifications) {
?>
<script>
    jQuery(document).ready(function() {
        <?php
        foreach ($notifications as $msg) {
        ?>
            Jimbo.growlCreate('<?php echo __('Notification'); ?>', '<?php echo $msg; ?>', false);
        <?php
        }
        ?>
    });
    </script>

<?php
}

if ($info['filter'] == 'top' && !empty($filters)) {
    echo $this->fetch('filters.php');
}
?>

<div class="panel panel-default b-db-table b-db-table-<?php echo $info['name'];?>">
    <?php
        echo $this->fetch('list_panel_heading.php');
    ?>
  
    <div class="panel-body">
        <?php
            echo $this->fetch('list_table.php');
        ?>
        
        <?php
        if (!empty($info['pager'])) {
        ?>
            <div class="e-db-pager row">
                <div class="col-md-6"><?php echo $info['pager']?></div> 
                <div class="col-md-6 text-right"><span class="e-total-items pull-right"><?php echo __('Total Items')?>: <?php echo $info['totalRows']?></span></div>
            </div>
        <?php
        }
        ?>
    
        <iframe class="db-submiter" name="submiter"></iframe>
    </div>
</div>