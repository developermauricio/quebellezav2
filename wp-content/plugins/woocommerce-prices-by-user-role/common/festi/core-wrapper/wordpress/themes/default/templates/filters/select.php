<select name="filter[<?php echo $field->getFilterKey(); ?>]" class="db-filed-filter db-filed-filter-<?php echo $field->getType()?>">
    <option value="">...</option>
    <?php
    foreach ($field->filterValues as $key => $value) {
        $selected = ($field->filterValue == $key) ? 'selected="selected"' : '';
    ?>
        <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($value); ?></option>
    <?php
    }
    ?>
</select>