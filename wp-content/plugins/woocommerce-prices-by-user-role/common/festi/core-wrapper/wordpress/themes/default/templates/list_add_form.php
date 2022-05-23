<tr class="e-db-table-row e-fast-add">
    <input type="hidden" name="__token" value="<?php echo $info['token'];?>" />
    <?php
    if ($info['grouped']) {
    ?>
        <td>&nbsp;</td>
    <?php
    }
    ?>
    <?php
    foreach ($info['fieldInputs'] as $item) {
    ?>
        <td><?=$item?></td>
    <?php  
    }
    ?>
    <td>
        <button type="submit" class="e-db-button e-db-action">+</button>
    </td>
</tr>