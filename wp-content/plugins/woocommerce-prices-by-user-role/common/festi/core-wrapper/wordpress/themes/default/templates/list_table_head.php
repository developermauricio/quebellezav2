<div class="e-db-table-row">
    <?php
    if ($info['grouped']) {
    ?>
        <div class="e-db-table-cell e-table-head e-grouped-head-cell"></div>
    <?php
    }
    ?>
    
    <?php
    foreach ($info['fields'] as $field) { // [1]
    ?>
        <div class="e-db-table-cell e-table-head e-table-head-<?php echo $field['name']?>" style="width: <?php echo $field['width']?>;">
            <?php
            if (!$field['sorting']) {
                echo $field['caption'];
            } else {
            ?>
                <a href="<?php echo $field['sorting']['url']; ?>" class="e-db-action"><?php echo $field['caption']?></a>
                <?php
                if ($field['sorting']['current']) {
                    $imagePostfix = ($field['sorting']['direction'] == 'ASC') ? 'az' : 'za';
                ?>
                    <img src="<?php echo $info['base_http_icon']?>dbadmin_sort_<?php echo $imagePostfix?>.gif" />
                <?php
                }
                ?>
            <?php
            }
            ?>
        </div>
    <?php
    } // end foreach [1]
    ?>
    
    <div class="e-db-table-cell e-table-head"></div>
</div>

