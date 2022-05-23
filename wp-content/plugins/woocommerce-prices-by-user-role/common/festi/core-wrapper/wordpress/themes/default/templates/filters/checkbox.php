<select name="filter[<?php echo $field->getFilterKey(); ?>]" class="db-filed-filter db-filed-filter-<?php echo $field->getType()?>">
    <option value="">...</option>
    <?php
    $selected = ($field->filterValue == 1) ? 'selected="selected"' : '';
    ?>
    <option value="1" <?php echo $selected; ?>><?php echo __('Checked'); ?></option>
    
    <?php
    $selected = ($field->filterValue === 0 || $field->filterValue === "0") ? 'selected="selected"' : '';
    ?>
    <option value="0" <?php echo $selected; ?>><?php echo __('None'); ?></option>
</select>