jQuery(document).ready(function($) {
	var home_url= jQuery('#url').val();
	jQuery(document).on('click','#pricedrop_alert_submit',function(){
		// Get Email address
	jQuery('.wpda_msg').remove();
		if($(this).hasClass('guest')){
			var email= $(this).data('email');
		}else{
			var email= $('#pricedrop_alert_email').val(); // Get email
		}
		var product_id= $(this).data('product');
		var price= $(this).data('price');
		jQuery.ajax({
			url: home_url,
			type: 'POST',
			dataType: 'json',
			data: {data: email,action: 'wpda_GetuserDetail',product: product_id,price: price},
			success: function(e){
				if(e.FLAG == true){
					alert(e.MSG);
				}else{
					alert(e.MSG);
				}
				$('#pricedrop_alert_email').val('');
				jQuery('#pricedrop_alert_submit').after('<p class="wpda_msg">'+e.MSG+'</p>');
			},
		});
		

	});
});