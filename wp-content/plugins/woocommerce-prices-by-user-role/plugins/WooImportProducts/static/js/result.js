jQuery(document).ready(function(){
    jQuery("#feti-price-role-result-show-log").click(function(){
        jQuery(this).hide();
        jQuery("#feti-price-role-result-log").fadeIn();
    });

    var offset = 0;
    
    doAjaxImport(offset);
    
    function doAjaxImport(offset)
    {
        var data = {
            action: 'importProductData',
            offset: offset,
        };

        jQuery.post(fesiImportOptions.ajaxurl, data, ajaxImportCallBack);
    }
    
    var remainingCount;
    
    var totalCount;
    var errorCount = 0;
    
    var newProcessedCount;
    
    var newProcessedPersent;
    
    function ajaxImportCallBack(response)
    {
        jQuery("#feti-price-role-result-table tbody").append(response.content);
        
        errorCount = errorCount + parseInt(response.errors);
        jQuery("#festi-errors-count").text(errorCount);

        offset = offset + parseInt(fesiImportOptions.limit);
        
        setProgressData(offset);
        
        if (offset >= parseInt(fesiImportOptions.rowsCount)) {
            jQuery('.festi-import-in-progress-ajax-loader').hide();
            jQuery('.festi-import-in-progress-complete').show();
            return false;
        }

        doAjaxImport(offset);
    }
    
    function setProgressData(offset)
    {
        processedCount  = parseInt(
            jQuery("#festi-processed-count").html()
        );
    
        remainingCount  = parseInt(
            jQuery("#festi-remaining-count").html()
        );
    
        totalCount  = parseInt(
            jQuery("#festi-total-count").html()
        );
        
        newRemainingCount = totalCount - parseInt(offset);
        
        newProcessedCount = totalCount - newRemainingCount;
        
        newProcessedPersent = Math.round(newProcessedCount/totalCount * 100);
        
        
        if (newRemainingCount < 0) {
            newProcessedCount = totalCount;
            newRemainingCount = 0;
            newProcessedPersent = 100;
        }
        
        jQuery("#festi-processed-count").html(newProcessedCount);
        
        jQuery("#festi-processed-percent").html(newProcessedPersent);
        
        jQuery("#festi-remaining-count").html(newRemainingCount);
    }
});