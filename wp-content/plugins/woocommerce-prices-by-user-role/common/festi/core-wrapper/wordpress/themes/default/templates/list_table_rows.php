<?php
if ($data) {
    foreach ($data as $index => $line) {
?>
    <div class="e-db-table-row e-db-table-row-<?php echo ($index % 2 == 0 ? 'even' : 'odd')?>">
        <?php
        if ($info['grouped']) {
        ?>
            <div class="e-db-table-cell e-db-table-row-item">
                <input type="checkbox" class="i-grouped-item" name="grouped_items[]" value="<?php echo $line['id']?>" />
            </div>
        <?php
        }
        ?>
        
        <?php
        foreach ($line['data'] as $item) {
        ?>
            <div class="e-db-table-cell e-db-table-row-item e-db-table-row-item-<?php echo $item['name']?>">
                <?php echo ($item['value'] ? $item['value'] : '&nbsp;')?>
            </div>
        <?php  
        }
        ?>
        
        <div class="e-db-table-cell e-db-table-row-actions">
            <?php
            if (!empty($line['action_lists'])) {
            ?>
                <select name="i-action-lists i-action-lists-<?php echo $line['id']?>">
                    <option></option>
                    <?php
                    foreach ($line['action_lists'] as $action) {
                    ?>
                        <option value="<?php echo $action['link']?>"><?php echo $action['alt']?></option>
                    <?php
                    }
                    ?>
                </select>
            <?php
            }
            ?>
            <div class="btn-group">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                <?php echo __l('Action'); ?> <span class="caret"></span>
              </button>
              <ul class="dropdown-menu pull-right" role="menu">
                    <?php
                    foreach ($line['actions'] as $action) {
                    ?>
                        <li><?php echo $action['html']; ?> </li>
                    <?php
                    }
                    ?>
              </ul>
            </div>
            
        </div>
    </div>
<?php
    }
}
?>