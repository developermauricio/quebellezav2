<div style="display:none;" id="store-form">
    <form class="f-db-form" method="post" target="submiter" enctype="multipart/form-data" action="<?php echo $action['link']?>">
        <div class="e-caption">
            <h2><?php echo $info['caption']?></h2>
        </div>
        <div class="e-items">
            <?php
            $index = 0;
            $infoText = "";
            foreach ($items as $item) {
                if (!empty($item['readonly']) && $what == "insert") {
                    continue;
                }
                
                $text = strip_tags($item['input']);
                if (!empty($text)) {
                    $infoText .= '<span class=\'txt-color-orangeDark\'>'.$item['caption'].':</span> '.$text.'<br />';
                }
            ?>
                <div class="e-item e-item-<?php echo ($index % 2 == 0 ? 'even' : 'odd')?>">
                    <div class="e-label"><label for="<?php echo $item['name']?>"><?php echo $item['caption']?></label></div>
                    <div class="e-input">
                        <?php echo $item['input']?>
                        <?php
                        if (!empty($item['required']) && $what != "remove") {
                        ?>
                            <img src="<?php echo $info['base_http_icon']?>required.gif" />
                        <?php
                        }
                        ?>
                     </div>
                </div>
            <?php
                $index++;
            } // end foreach
            ?>
        </div>
        <div class="e-buttons">
            <?php
            if (!empty($info['actionbutton'])) {
            ?>
                <button type="submit" class="hui-btn e-add-btn" title="<?php echo $info['actionbutton']?>"><?php echo $info['actionbutton']?></button>
            <?php
            }
            ?>
            
            <?php
            if ($action['mode'] == "new") {
            ?>
                <a href="<?php echo $info['url']?>" class="hui-btn e-cancel-btn"><?php echo __('Cancel')?></a>
            <?php
            } else {
            ?>
                <button type="button" class="hui-btn e-cancel-btn" onclick="window.close();" title="<?php echo __('Cancel')?>"><?php echo __('Cancel')?></button>
            <?php
            }
            ?>
        </div>

        <?php
        if (!empty($info['token'])) {
        ?>
            <input type="hidden" name="__token" value="<?php echo $info['token']?>" />
        <?php
        }
        ?>
        <?php
        if (!empty($info['primaryKeyValue'])) {
        ?>
            <input type="hidden" name="<?php echo $store->createRequestKey(Store::PRIMARY_KEY_IN_REQUEST); ?>" value="<?php echo $info['primaryKeyValue']?>" />
        <?php
        }
        ?>
        

        <input type="hidden" name="<?php echo $store->createRequestKey('performPost'); ?>" value="<?php echo $info['action']?>" />
    </form>
    <iframe class="db-submiter" name="submiter" style="display:none" id="submiter"></iframe>
</div>

<script>
jQuery.SmartMessageBox({
    title : "<?php echo __l('Are you sure you want to delete?'); ?>",
    content : "<?php echo $infoText; ?>",
    buttons : '[No][Yes]'
}, function(ButtonPressed) {
    if (ButtonPressed === "Yes") {
        jQuery('.f-db-form').submit();
    } else {
        jQuery('.f-db-form').remove();
    }
});
</script>