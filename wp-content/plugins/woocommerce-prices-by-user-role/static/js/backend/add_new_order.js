jQuery(document).ready(function() 
{
    var inputField = ".woocommerce #order_data #customer_user";
    
    if (jQuery(inputField).length > 0 && isRoleSet()) {
        setUserIDForAjaxAction(jQuery(inputField).val());
    }

    jQuery(inputField).change(function () {
        setUserIDForAjaxAction(jQuery(this).val());
    })
    
    function setUserIDForAjaxAction(idUser)
    {
        var data = {
            action: 'onSetUserIDForAjaxAction',
            idUser: idUser
        };
        
        jQuery.post(fesiWooPriceRole.ajaxurl, data, function(response) {
            if (response.status === false) {
                alert('Woocommerce Price By Role: Error!');
                return false;
            }
            
            return true;
        })
    } // end setUserIDForAjaxAction
    
    function isRoleSet()
    {
        var value = jQuery(inputField).val();
        
        return value !== '';
    } // end isRoleSet
}); 