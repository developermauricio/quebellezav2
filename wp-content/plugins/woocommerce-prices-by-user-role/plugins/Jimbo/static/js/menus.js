var JimboMenus = {

    onInit: function () {
        Jimbo.addListener(Jimbo.EVENT_OPEN_DIALOG, function (event) {

            jQuery('#id_section').change(function () {

                var permissionContainer = jQuery('.db-filed-many2many').find('input');
                if (jQuery(this).val() == "") {
                    permissionContainer.prop("disabled", false);
                } else {
                    permissionContainer.prop("disabled", true);
                }
            });

            jQuery('#id_section').change();
        });
    } // end onInit

};

jQuery(document).ready(function() {
    JimboMenus.onInit();
});