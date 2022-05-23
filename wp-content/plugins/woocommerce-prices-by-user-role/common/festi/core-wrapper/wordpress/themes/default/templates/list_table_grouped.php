<tr>
    <td class="e-table-grouped">
        <input type="checkbox" class="i-select-all" />
    </td>
    <td class="e-table-grouped" colspan="<?php echo (count($info['fields']) + 1);?>">
        <nav class="e-group-actions">
            <ul>
            <?php
                foreach ($info['grouped'] as $actionIdent => $group) {
            ?>
                <li class="e-db-grouped-item e-db-grouped-<?php echo $actionIdent; ?>">
                    <button type="button" class="e-db-button e-db-group-action e-db-group-action-<?php echo $actionIdent; ?>" onclick="<?php
                        if ($group['js']) {
                            echo $group['js'].';';
                        } else {
                            echo "Jimbo.execGroupAction('".$group['link']."', '".$actionIdent."');";
                        }
                        ?>"><?php echo $group['caption']; ?></button>
                </li>
            <?php  
                }
            ?>
            </ul>
        </nav>
    </td>
</tr>