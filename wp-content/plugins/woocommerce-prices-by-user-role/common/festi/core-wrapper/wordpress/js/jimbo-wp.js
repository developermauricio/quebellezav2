var JimboWp = {
	init: function () {
		 
		Jimbo.i("JimboWp::init");
		
		Jimbo.addListener(Jimbo.EVENT_BEFORE_LOAD_FORM, function (event, options) {
		    Jimbo.i("JimboWp::EVENT_BEFORE_LOAD_FORM");
		    options.override = true;
			
			var data = {
				action: 'load_form',
				whatever: 1234,
				url: options.url
			};
			
			options.url = ajaxurl;
			options.data = data;
			
			Jimbo.showLoading();
			
			var submitter = jQuery('.db-submiter');
			var iframe = submitter.get(0);
			
			console.log("asddasd", options.data.url);
			
			
			
			iframe.onload = function () {
				Jimbo.hideLoading();
				
				
				
				var dialog = submitter.contents().find("#store-dialog-form");
				if (dialog.length > 0) {
					jQuery('body').append(dialog);
					dialog.modal('hide');
					
					dialog.on('hidden.bs.modal', function (e) {
						 jQuery(this).data('bs.modal', null);
						 jQuery('#store-dialog-form').remove();
						})
					dialog.modal({show:true});
					
				}
				
				dialog = submitter.contents().find("#MsgBoxBack");
				if (dialog.length > 0) {
					jQuery('body').append(dialog);
					
					jQuery('.btn-sm').click(function(el) {
						if (jQuery.trim(jQuery(this).text()) == "Yes") {
							Jimbo.showLoading();
							jQuery('.f-db-form').submit();
						} else {
							jQuery('#store-form').remove();
						}
						jQuery('#MsgBoxBack').remove();
					});
				}
				
				dialog = submitter.contents().find("#store-form");
				if (dialog.length > 0) {
					
					jQuery('body').append(dialog);
				}
			}
			
			submitter.attr('src', options.data.url);
			/*
			jQuery.post(ajaxurl, data, function(response) {
				alert('Got this from the server: ' + response);
			});
			*/
		});
	}
};

jQuery(document).ready(function() { 
	Jimbo.init({
        mode: "jquery",
        debug: true
    });

	JimboWp.init();
});