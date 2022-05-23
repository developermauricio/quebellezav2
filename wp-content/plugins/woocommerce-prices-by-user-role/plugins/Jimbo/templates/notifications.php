<?php
    if ($notifications) {
?>
<script>
    jQuery(document).ready(function() {
        <?php
        foreach ($notifications as $msg) {
        ?>
            Jimbo.growlCreate('<?php echo __('Notification'); ?>', '<?php echo $msg; ?>', false);
        <?php
        }
        ?>
    });
    </script>
<?php
    }
?>