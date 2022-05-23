<?php 
    $key = $field->getFilterKey(); 
?>
<input type="text" name="filter[<?php echo $key; ?>]" id="filter[<?php echo $key; ?>]" value="<?php echo htmlspecialchars($field->filterValue); ?>" size="10" class="db-filed-filter db-filed-filter-<?php echo $field->getType()?>" />
<input type="reset" value=" ... " class="db-filed-datetime-btn" id="filter_<?php echo $field->getName(); ?>_cal" name="<?php echo $field->getName(); ?>_cal" />
<script type="text/javascript">
    Calendar.setup({
        inputField     :    "filter[<?php echo $key; ?>]",
        ifFormat       :    "<?php echo $field->getFormat()?>",
        showsTime      :    false,
        button         :    "filter_<?php echo $field->getName(); ?>_cal",
        step           :    1
    });
</script>