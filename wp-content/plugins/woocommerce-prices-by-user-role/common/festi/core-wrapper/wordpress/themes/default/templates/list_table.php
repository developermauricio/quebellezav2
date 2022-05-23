<div class="e-db-list-table table table-striped">
    <?php
    if ($info['grouped']) {
        echo $this->fetch('list_table_grouped.php');
    }
    ?>

    <?php 
        echo $this->fetch('list_table_head.php'); 
    ?>
    
    <?php
    if ($info['filter'] != 'top' && !empty($filters)) {
        echo $this->fetch('list_table_filters.php');
    }
    ?>
    
    <?php
    if ($info['fastAdd']) {
        echo $this->fetch('list_add_form.php');
    }
    ?>
    
    <?php 
        echo $this->fetch('list_table_rows.php'); 
    ?>
    
    <?php
    if ($info['grouped']) {
        echo $this->fetch('list_grouped.php');
    }
    ?>
</div>

<?php
if (!$data) {
?>
    <div class="e-table-empty">
        <div class="alert alert-info"><?php echo $info['emptyMessage'];?></div>
    </div>
<?php
}
?>