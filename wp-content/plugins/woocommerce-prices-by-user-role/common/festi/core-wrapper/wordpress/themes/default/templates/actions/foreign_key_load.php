<script>
var elSel = parent.document.getElementById("<?php echo $info['ajaxChild']; ?>");
var i;

for (i = elSel.length - 1; i >= 0; i--) {
    elSel.remove(i);
}
<?php
foreach ($values as $key => $value) {
?>
    var oOption = document.createElement("OPTION");
    oOption.text = "<?php echo htmlspecialchars($value); ?>";
    oOption.value = "<?php echo $key; ?>";
    elSel.options.add(oOption);
<?php
}
?>
</script>