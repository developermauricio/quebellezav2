jQuery(document).ready(function() 
{
    jQuery('img.festi-user-role-prices-tooltip').poshytip({
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
    
    var productData = '#woocommerce-product-data';
    var variationLoadAction = 'woocommerce_variations_loaded';
    
    onInit();
    onRolePriceChange();
    onWpmlCurrencyCheckboxChange();
    onProductBulkEdit();
    doSalePriceByUserRole();
    
    jQuery(productData).on(variationLoadAction, function(event) {
        onInit();
        onRolePriceChange();
        onWpmlCurrencyCheckboxChange();
    });
    
    function getFieldRole(field)
    {
        var role = jQuery(field).attr('data-role');
        
        return role;
    }
    
    function isRoleInputField(field) 
    {
        var id = jQuery(field).attr('id');
        
        return (id.search("festiUserRolePrices") >= 0)
            || (id.search("festiVariableUserRolePrices") >= 0);
    }
    
    function onRolePriceChange()
    {
        jQuery('.form-field').on('focusout', '.wc_input_price', function()
        {
            if (!isRoleInputField(this)) {
                return false;
            }
            
            var role = getFieldRole(this);

            var priceId = '#festiUserRolePrices_'+ role;

            var salePriceId = priceId +'_sale_price';

            var price = jQuery(this).val();

            var salePrice = jQuery(salePriceId).val();

            if (salePrice > 0) {
                price = salePrice;
            }

            if (price == 0) {
                price = jQuery(priceId).val();
            }

            doCalculatePricesWithRate(role, price);
            doChangeDefaultPrices(role, price);
        })
    }
    
    function doCalculatePricesWithRate(role, price)
    {
        var fields = '.auto-calculate-' + role;
        
        jQuery(fields).each(function(index) 
        {
            var rate = jQuery(this).attr('rel');
            
            var newPrice = price * rate;
            
            jQuery(this).val(newPrice);
        })
    }
    
    function doChangeDefaultPrices(role, price)
    {
        var fields = '.auto-calculate-default-' + role;
        
        jQuery(fields).each(function(index) 
        {           
            jQuery(this).val(price);
        })
    }
    
    function onWpmlCurrencyCheckboxChange()
    {
        var element = '.wcml_custom_prices_block';
        var checkbox = '.wcml_custom_prices_input';
        
        jQuery(element).on('change', checkbox, function()
        {
            doSetupWpmlFieldsDisplayState(this);
        })
    }
    
    function onInit()
    {      
        if (!isWpmlPricesBoxExist()) {
            return;
        }
        
        jQuery('.wcml_custom_prices_input:checked').each(function()
        {
            doSetupWpmlFieldsDisplayState(this);
        })  
    }
        
    function isWpmlPricesBoxExist()
    {
        var box = jQuery('.wcml_custom_prices_block');
        
        return box.length > 0;
    }
    
    function doSetupWpmlFieldsDisplayState(field)
    {
        var id = getProductID(field);
        
        var postClass = '.festi-wpml-post-' + id;

        if (isPricesCalculatedManually(field)) {
            jQuery(postClass + ' .festi-price-wpml-manual').show();
            jQuery(postClass + ' .festi-price-wpml-auto').hide();
        } else {
            jQuery(postClass + ' .festi-price-wpml-manual').hide();
            jQuery(postClass + ' .festi-price-wpml-auto').show();
        }
    }
    
    function isPricesCalculatedManually(field)
    {      
        var id = jQuery(field).attr('id');
        
        return (id.search("manually") >= 0);
    }
    
    function getProductID(field)
    {
        var idPost;
        
        var element = jQuery('.wcml_custom_prices_input:checked'); 
        
        var name = jQuery(field).attr('id');
        
        return name.replace(/\D/g, '');
    }
    
    function getProductIDsByBulkEdit($bulkEditRow)
    {
        var postIDs = new Array();
        $bulkEditRow.find('#bulk-titles').children().each( function() {
            postIDs.push(jQuery(this).attr('id').replace(/^(ttle)/i, ''));
        });
        
        return postIDs;
    }
    
    function onProductBulkEdit()
    {
        var bulkEditButtonSelector = '#bulk_edit'; 
        var bulkEditRowSelector = '#bulk-edit';
        var postsFilterSelector = '#posts-filter';
        
        jQuery(bulkEditButtonSelector).on('click', function() {
            var $bulkEditRow = jQuery(bulkEditRowSelector);
            
            var postIDs = getProductIDsByBulkEdit($bulkEditRow);
            
            var bulkEditForm = $bulkEditRow
                               .parents(postsFilterSelector)
                               .serialize();
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                async: false,
                cache: false,
                data: {
                    action: 'onHideProductsByRoleAjaxAction',
                    postIDs: postIDs,
                    form: bulkEditForm
                }
            });
        });
    }
    
    function doSalePriceByUserRole()
    {
        onCheckSalePriceByUserRole('.wc-metaboxes-wrapper');
        onCheckSalePriceByUserRole('.woocommerce_options_panel');
        onShowScheduleByRoleSalePrice();
        onHideScheduleByRoleSalePrice();
        onAppendDatePickerForSchedule();
    }
   
    function isVariableProductTab(element)
    {
        var searchName = 'festiVariableUserRolePrices';
        return element.attr('name').indexOf(searchName) !== -1;
    }

    function onCheckSalePriceByUserRole(wrapper)
    {
        var salePriceSelector = '.festi-role-sale-price';
        
        jQuery(wrapper).on('keyup change', salePriceSelector, function() {
            var $roleSalePrice = jQuery(this);
            var $rolePrice;
            
            $rolePrice = getRolePriceElement($roleSalePrice);
            
            var salePriceFormat;
            var priceFormat;
            
            salePriceFormat = getFormatedPrice($roleSalePrice);
           
            priceFormat = getFormatedPrice($rolePrice);

            if (salePriceFormat >= priceFormat) {
                var message = getErrorMessageForRole($roleSalePrice);
                
                doDisplayErrorSalePrice($roleSalePrice, message);
            } else {
                doRemoveErrorSalePrice($roleSalePrice);
            }
        });
    }
    
    function getRolePriceElement($roleSalePrice)
    {
        if (isVariableProductTab($roleSalePrice)) {
            $rolePrice = $roleSalePrice.parent().prev().find('.wc_input_price');
        } else {
            $rolePrice = $roleSalePrice.parent().parent().prev();
            $rolePrice = $rolePrice.find('.wc_input_price');
        }
        
        return $rolePrice;
    }
    
    function getFormatedPrice(element)
    {
        var formatedPrice;
        formatedPrice = parseFloat(
            window.accounting.unformat(
                element.val(),
                woocommerce_admin.mon_decimal_point
            )
        );
        
        return formatedPrice;
    }
    
    function getErrorMessageForRole(element)
    {
        return element.parent().parent().data('error')
    }
        
    function doDisplayErrorSalePrice(element, message)
    {
        var offset = element.position();

        if (element.parent().find( '.wc_error_tip' ).size() === 0 ) {
            element.after(
                '<div class="wc_error_tip i18_sale_less_than_regular_error">'
                +message+'</div>'
            );

            var position = offset.left + element.width();
            position-= (element.width() / 2);
            position-= (jQuery( '.wc_error_tip' ).width() / 2);
            element.parent().find('.wc_error_tip')
                .css(
                    'left',
                    position
                 )
                .css('top', offset.top + element.height() )
                .fadeIn('100');
        }
    }
    
    function doRemoveErrorSalePrice(element)
    {
        jQuery(document.body).triggerHandler(
            'wc_remove_error_tip',
            [element, 'i18_sale_less_than_regular_error']
        );
    }
    
    function onShowScheduleByRoleSalePrice()
    {
        var buttonSchedule = '.festi-sale-role-schedule';
    
        jQuery(buttonSchedule).on('click', function(event) {
            event.preventDefault();
            jQuery(this).closest('p').next().show();
            jQuery(this).hide();
        });
    }
    
    function onHideScheduleByRoleSalePrice()
    {
        var buttonSchedule = '.festi-sale-role-schedule';
        var buttonScheduleCancel = '.festi-sale-price-schedule-cancel';
    
        jQuery(buttonScheduleCancel).on('click', function(event) {
            event.preventDefault();
            jQuery(this).closest('p').hide();
            jQuery(this).closest('p').prev().find(buttonSchedule).show();
            jQuery(this).closest('p').find('input').val('');
        });
    }
    
    function onAppendDatePickerForSchedule()
    {
        var dateFieldSelector = '.festi-sale-price-dates-fields'
        var dateFromSelector = '.festi-sale-price-date-from';
        jQuery(dateFieldSelector).each( function() {
            
            var dates = jQuery(this).find('input').datepicker({
                defaultDate: '',
                dateFormat: 'yy-mm-dd',
                numberOfMonths: 1,
                showButtonPanel: true,
                onSelect: function( selectedDate ) {
                    var option, instance, date; 
                    var $this = jQuery(this);
                    
                    option   = $this.is(dateFromSelector) ? 'minDate' : 'maxDate';
                    instance = $this.data( 'datepicker' );
                    
                    date = jQuery.datepicker.parseDate(
                        instance.settings.dateFormat
                        || $.datepicker._defaults.dateFormat,
                        selectedDate,
                        instance.settings
                    );
                    
                    dates.not(this).datepicker( 'option', option, date );
                }
            });
        });
    }   
}); 