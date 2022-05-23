<?php
if ($info['src']) {
?>
<a href="<?php echo $info['link']?>" <?php if ($info['js']): ?>onclick="<?php echo $info['js']?>; return false;"<?php endif;?> title="<?php echo $info['caption'];?>" <?php echo $info['addon'];?> mode="<?php echo $info['mode']?>" class="e-db-action e-db-list-row-action"><?php echo $info['caption']; ?></a>
<?php
} else {
?>
    <a href="<?php echo $info['link']?>" <?php if ($info['js']): ?>onclick="<?php echo $info['js']?>; return false;"<?php endif;?> title="<?php echo $info['caption']?>" mode="<?php echo $info['mode']?>" <?php echo $info['addon']?> class="btn btn-default e-db-button e-db-action e-db-action-<?php echo $info['type']?>"><?php echo $info['caption']?></a>
<?php  
}   
?>
