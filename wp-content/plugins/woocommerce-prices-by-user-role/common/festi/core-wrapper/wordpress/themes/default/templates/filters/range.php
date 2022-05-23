<div class="b-db-filter-range">
    <div class="e-db-filter-range-from">
        <input type="text" name="filter[<?php echo $field->getFilterKey(); ?>][0]" value="<?php echo htmlspecialchars($field->filterValue[0]); ?>" class="db-filed-filter db-filed-filter-range db-filed-filter-<?php echo $field->getType()?>" placeholder="<?php echo __('From'); ?>" />
    </div>
    <div class="e-db-filter-range-to">
        <input type="text" name="filter[<?php echo $field->getFilterKey(); ?>][1]" value="<?php echo htmlspecialchars($field->filterValue[1]); ?>" class="db-filed-filter db-filed-filter-range db-filed-filter-<?php echo $field->getType()?>" placeholder="<?php echo __('To'); ?>"/>
    </div>
</div>