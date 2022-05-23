<div class="modal fade" id="store-dialog-form" tabindex="-1" role="dialog" aria-labelledby="store-dialog-formLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                    &times;
                </button>
                <h4 class="modal-title">
                    <?php echo $info['caption']; ?>
                </h4>
            </div>
            <div class="modal-body no-padding">

                 <form role="form" class="form-horizontal f-db-form" method="post" target="submiter" enctype="multipart/form-data" action="<?php echo $action['link']?>">
                    <?php
                    $index = 0;
                    foreach ($items as $item) {
                        if (!empty($item['readonly']) && $what == "insert") {
                            continue;
                        }
                    ?>
                        <div class="form-group e-item e-item-<?php echo ($index % 2 == 0 ? 'even' : 'odd')?>">
                            <label for="<?php echo $item['name']?>" class="col-sm-3 control-label <?php if (!empty($item['required']) && $what != "remove") {?>label-required<?php }?>">
                                <span><?php echo $item['caption']; ?></span>
                            </label>
                            <div class="col-sm-7 e-input">
                                <?php echo $item['input']; ?>
                                <?php
                                if ($item['disclaimer']) {
                                ?>
                                    <p class="note"><?php echo $item['disclaimer']; ?></p>
                                <?php
                                }
                                ?>
                             </div>
                        </div>
                    <?php
                        $index++;
                    } // end foreach
                    ?>
                           <div class="form-group e-buttons">
                                <div class="col-sm-offset-3 col-sm-9">
                                    
                                    <?php
                                    if (!empty($info['actionbutton'])) {
                                    ?>
                                        <button type="submit" class="btn btn-primary hui-btn e-add-btn" title="<?php echo $info['actionbutton']?>"><?php echo $info['actionbutton']?></button>
                                    <?php
                                    }
                                    ?>
                                    
                                    <?php
                                    if ($action['mode'] == "new") {
                                    ?>
                                        <a href="<?php echo $info['url']?>" class="btn btn-default hui-btn e-cancel-btn"><?php echo __l('Cancel')?></a>
                                    <?php
                                    } else {
                                    ?>
                                        <button type="button" class="btn btn-default hui-btn e-cancel-btn" data-dismiss="modal" title="<?php echo __l('Cancel')?>"><?php echo __l('Cancel')?></button>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <?php
                            if (!empty($info['token'])) {
                            ?>
                                <input type="hidden" name="<?php echo $store->createRequestKey(Store::REQUEST_KEY_INSERT_TOKEN); ?>" value="<?php echo $info['token']?>" />
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

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script type="text/javascript">
jQuery(document).ready(function() {
     jQuery('#store-dialog-form').modal({
         show:true
     });
});
</script>