<?php
$filterAdditionalQuery = parse_url($info['url'], PHP_URL_QUERY);
parse_str($filterAdditionalQuery, $additionalParams);
?>
<form action="<?php echo $info['url']; ?>" method="get" class="e-db-table-row e-filters">
	<?php
    $sessionToken = Controller::getInstance()->getSessionToken();
    if ($sessionToken) {
    ?>
        <input type="hidden" name="<?php echo Controller::getInstance()->getOption('session_token_key');?>" value="<?php echo $sessionToken; ?>" />
    <?php
    }
    ?>
    <?php
    foreach ($additionalParams as $key => $value) {
        if (!is_string($value)) {
            continue;
        }
    ?>
        <input type="hidden" name="<?php echo $key; ?>" value="<?php echo $value; ?>" />
    <?php
    }
    ?>
    <?php
    if ($info['grouped']) {
    ?>
        <div class="e-db-table-cell">&nbsp;</div>
    <?php
    }
    ?>
    <?php
    foreach ($filters as $item) {
    ?>
        <div class="e-db-table-cell e-filter"><?php echo ($item ? $item: '&nbsp;')?></div>
    <?php
    }
    ?>
    <div class="e-db-table-cell">
        <input type="hidden" name="<?php echo $store->getIdent();?>[filter_wtd]" id="<?php echo $store->getIdent();?>-filter-wtd" value="apply" />
        <button type="submit" class="btn btn-primary e-db-button e-db-action"><?php echo __l('Search')?></button>
    </div>
</form>
