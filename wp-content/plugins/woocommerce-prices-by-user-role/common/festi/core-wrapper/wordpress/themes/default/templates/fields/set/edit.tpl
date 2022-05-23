<div class="jf-field-set-edit">
{foreach from=$valuesList item=caption key=key}
<div><label><input type="checkbox" name="{$name}[]" value="{$key}" {if in_array($key, $currentValues)}checked{/if} />{$caption}</label></div>
{/foreach}
</div>