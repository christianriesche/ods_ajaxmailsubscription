function ods_ajaxmailsubscription(obj){
	jQuery('#tx_odsajaxmailsubscription_pi1_indication').css('display','block');
//	jQuery('#tx_odsajaxmailsubscription_pi1').load(obj.action,function() {
//		jQuery('#tx_odsajaxmailsubscription_pi1_indication').css('display','none');
//	});
	jQuery.ajax({  
		type: 'POST',  
		url: obj.form.action,  
		data: jQuery.param(jQuery(obj.form).serializeArray())+'&'+obj.name+'=1&tx_odsajaxmailsubscription_pi1[ajax]=jquery',  
		success: function(data) {
			jQuery('#tx_odsajaxmailsubscription_pi1').html(data);
			jQuery('#tx_odsajaxmailsubscription_pi1_indication').css('display','none');
		}  
	});  
	return false;
}
