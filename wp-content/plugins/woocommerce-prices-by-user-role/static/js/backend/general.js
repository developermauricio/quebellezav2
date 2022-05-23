jQuery(document).ready(function()
{
    hideOrShowBlockTextForUnregisteredUsers();
    hideOrShowBlockTextForRegisteredUsers();
    hideOrShowBlockTextInstedOfAddToCartButton();
    hideOrShowBlockTextInstedOfEmptyPrice();
    hideOrShowBlockTextForGuestUsers();

    var getUrlParameter = function getUrlParameter(sParam) {
        var sPageURL = decodeURIComponent(window.location.search.substring(1)),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;

        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');

            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : sParameterName[1];
            }
        }
    };

    function hideOrShowBlockTextInstedOfAddToCartButton()
    {
        var selector = '.festi-case-text-instead-button-for-non-registered-users';

        if (jQuery('.festi-case-hide-add-to-cart-button input').is(":checked")) {
            jQuery(selector).show();
        } else {
            jQuery(selector).hide();
        }
    } // end hideOrShowBlockTextInstedOfAddToCartButton
    
    function hideOrShowBlockTextInstedOfEmptyPrice()
    {
        var selector = '.festi-case-text-instead-empty-price ';

        if (jQuery('.festi-case-hide-empty-price input').is(":checked")) {
            jQuery(selector).show();
        } else {
            jQuery(selector).hide();
        }
    } // end hideOrShowBlockTextInstedOfEmptyPrice
    
    jQuery( '.festi-setting-form input, .festi-setting-form textarea, .festi-setting-form select, .festi-setting-form checkbox' ).change( function() {
        window.onbeforeunload = function (event) {
            var message = 'Important: Please click on \'Save changes\' button to leave this page.';
            if (typeof event == 'undefined') {
                event = window.event;
            }
            if (event) {
                event.returnValue = message;
            }
            return message;
        };
    });

    jQuery(function () {
        var saveButton = ".festi-user-role-prices-save-button";
        var deleteRoleButton = "a.festi-user-role-prices-delete-role";
        jQuery(saveButton + ',' + deleteRoleButton).click(function () {
            window.onbeforeunload = null;
        });
    });

    function hideOrShowBlockTextForUnregisteredUsers()
    {
        var selector = '.festi-case-text-for-unregistered-users';

        if (jQuery('.festi-case-only-registered-users input').is(":checked")) {
            jQuery(selector).show();
        } else {
            jQuery(selector).hide();
        }
    } // end hideOrShowBlockTextForUnregisteredUsers

    function hideOrShowBlockTextForGuestUsers()
    {
        var selector = '.custom-guest-user-text';

        if (jQuery('#guestUserStatus').is(":checked")) {
            jQuery(selector).show();
        } else {
            jQuery(selector).hide();
        }
    } // end hideOrShowBlockTextForGuestUsers

    function hideOrShowBlockTextForRegisteredUsers()
    {
        var selector = '.festi-case-text-for-registered-users';

        if (jQuery('.festi-case-hide-price-for-user-roles input').is(":checked")) {
            jQuery(selector).show();
        } else {
            jQuery(selector).hide();
        }
    } // end hideOrShowBlockTextForRegisteredUsers

    jQuery('input[data-event=visible]').change(function() {

        var className = jQuery(this).prop("name") + '-' + jQuery(this).data('event');

        if(jQuery(this).prop("checked")){

            jQuery('.'+className).fadeIn();
        } else {
            jQuery('.'+className).fadeOut(100);
        }
    });

    jQuery('select[data-event=visible]').change(function() {
        var className = jQuery(this).prop("name") + '-' + jQuery(this).data('event');

        if(jQuery(this).val() == 'disable'){
            jQuery('.'+className).fadeOut(100);
        } else {
            jQuery('.'+className).fadeIn();
        }
    });

    jQuery('.festi-user-role-prices-delete-role').click(function()
    {
        if (!confirm('Are you sure to delete')) {
            return false;
        }
    });

    let inputSelector = '#festi-user-role-prices-discount-roles td input';

    jQuery(document).on('keypress', inputSelector, function(e) {
        if (e.which!=8 && e.which!=0 && e.which!=46 && (e.which<48 || e.which>57)) {
            return false;
        }
    });

    jQuery('.festi-user-role-prices-help-tip').poshytip({
        className: 'tip-twitter',
        showTimeout:100,
        alignTo: 'target',
        alignX: 'center',
        alignY: 'bottom',
        offsetY: 5,
        allowTipHover: false,
        fade: true,
        slide: false
    });


    jQuery('body').on('change', 'input[name="onlyRegisteredUsers"]', function() {
        hideOrShowBlockTextForUnregisteredUsers();
    });

    jQuery('body').on('change', 'input[name^="hidePriceForUserRoles"]', function() {
        hideOrShowBlockTextForRegisteredUsers();
    });

    jQuery('body').on('change', 'input[name="hideAddToCartButton"]', function() {
        hideOrShowBlockTextInstedOfAddToCartButton();
    });
    
    jQuery('body').on('change', 'input[name^="hideEmptyPrice"]', function() {
        hideOrShowBlockTextInstedOfEmptyPrice();
    });

    jQuery('body').on('change', 'input[name^="guestUserStatus"]', function() {
        hideOrShowBlockTextForGuestUsers();
    });

    jQuery('input#taxOptions').click(function() {
        className = '.festi-tax-table';

        if (jQuery(this).prop("checked")) {
            jQuery(className).fadeIn(200);
        } else {
            jQuery(className).fadeOut(200);
        }
    });

    jQuery('input#quantity-discount-add-row').click(function() {
        jQuery('.quantity-discount-row-hidden').each(function() {
            var row = jQuery(this);
            row.removeClass('quantity-discount-row-hidden');
            row.addClass('quantity-discount-row-visible');
            var hiddenRowValue = 1;
            row.find('.hidden').val(hiddenRowValue);
            return false
        });
    });

    jQuery('.delete-row-quantity-discount').click(function(event) {
        event.preventDefault();
        row = jQuery(this).parent().parent();
        row.removeClass('quantity-discount-row-visible');
        row.addClass('quantity-discount-row-hidden');
        var visibleRowValue = 0;
        row.find('.hidden').val(visibleRowValue);
    });

});