jQuery(document).ready(function(){
    if(jQuery("#send_test_form").length > 0) {
        jQuery.validator.setDefaults({
            ignore: []
        });
        jQuery("#send_test_form").validate({
            ignore : ''
        });
    }
    if(jQuery("#stars-add-new-account").length > 0) {
        jQuery("#stars-add-new-account").validate();
    }
    if(jQuery(".tooltip-toggle").length > 0) {
        jQuery(".tooltip-toggle").tooltip({
            content: function () {
              return jQuery(this).prop('title');
            }
        });        
    }
    // Check server port
    if(jQuery("#smtp_port").length > 0 && jQuery("#smtp_host").length > 0) {
        jQuery( "#smtp_port, #smtp_host" ).focusout(function() {
            var host= jQuery("#smtp_host").val();
            var port= jQuery("#smtp_port").val();
            jQuery(".check_error").text("");
            if(jQuery.trim(host) != "" && jQuery.trim(port) != "") {
                jQuery.ajax({
                    type : 'POST',
                    url : ajaxurl,  
                    data :  {'check_host' : host,'check_port' : port, 'action' : 'stars_smtpm_check_host_server' }, 
                    success : function(response){
                        if(response){
                            var data = jQuery.parseJSON(response);
                            if(data.error){
                                jQuery(".check_error").text(data.error).removeClass('none').css("color","red").css("width","100%");
                            }
                            if(data.valid){
                                jQuery(".check_error").text(data.valid).removeClass('none').css("color","green");
                            }
                        }
                    }
                });
            }
        });
    }
    // check user
    if(jQuery( "#username" ).length > 0) {
        jQuery( "#username" ).focusout(function() {
            var username= jQuery("#username").val();
           	jQuery(".user_error").addClass('none');
           	jQuery('#submit').removeAttr("disabled");
            jQuery.ajax({
                type : 'POST',
                url : ajaxurl,  
                data :  {'uname' : username, 'action' : 'stars_smtpm_check_user', 'id' : getParameterByName("id") }, 
                success : function(response){                       
                    if(response != 0){
                        jQuery(".user_error").text(response).removeClass('none').css("color","red");
                        jQuery('#submit').attr("disabled","disabled");
                    }
                }
            });
        });
    }
    // confirm delete
    jQuery('.confirm-delete').click(function(){        
         if(jQuery("#check_admin").val() == 1){            
             if(jQuery(this).attr('data-value') == "account" && jQuery(".smtp-activation#"+jQuery(this).attr('data-id')).hasClass("deactivate")){
                OpenPopup("Action Restricted","You can not delete activated account!");
                return false;                 
             }
             else{
                if(confirm('Are you sure you want to delete this '+jQuery(this).attr('data-value')+'?')) 
                    return true;
                 else 
                    return false;
             }
         }else{            
            OpenPopup('Access Restricted','This feature is available in PRO version!'); 
            return false;           
         }
    });
    jQuery('form[name="smtp_accounts_list"] .button.action').click(function(){         
        if(jQuery(this).prev().find("option:selected").val() == 'delete' && jQuery(".stars-smtp-account-list").length > 0){
            var stop = 0;                        
            jQuery(".stars-smtp-account-list input[type='checkbox']").each(function(e){
                if(jQuery(this).prop('checked') && jQuery(".smtp-activation#"+jQuery(this).val()).hasClass("deactivate")) stop = 1;
            });
            if(stop == 1){
                OpenPopup("Action Restricted","You can not delete activated account!");
                return false;
            }            
        }        
    });
});
function SetEmailBody() {
    if(jQuery("#email_content").css("display") == "none")
        jQuery("#email_content").val(jQuery("#email_content_ifr").contents().find("body").text());
    return true;
}
function getParameterByName(name, url) {
    if (!url) {
      url = window.location.href;
    }
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}
function OpenPopup(Title,Message){        
    jQuery('<div></div>').appendTo('body')
	.html("<p>"+Message+"</p>")
	.dialog({    
		modal: true,
		title: Title,
		zIndex: 10000,
		autoOpen: true,
		width: '400',
		resizable: false,
		buttons: {
			Close: function () {
				jQuery(this).remove();
			}
		},
		close: function (event, ui) {
			jQuery(this).remove();
            return false;
		}
	});
}