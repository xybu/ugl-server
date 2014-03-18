$(document).ready(function() {
	$("input").on('focus focusout', function(e){
		$(this).tooltip('hide');
	});
	
	$("#nav-login").submit(function(e){
		
		if (!isEmail($('#nav-login-email').val())){
			$('#nav-login-email').tooltip('show');
			e.preventDefault(); //STOP default action
		} else if (!isPassword($('#nav-login-pass').val())){
			$('#nav-login-pass').tooltip('show');
			e.preventDefault(); //STOP default action
		} else {
			var postData = $(this).serializeArray();
			for (var item in postData){
				var itemName = postData[item].name;
				if (itemName == 'password' || itemName == 'confirm_pass'){
					postData[item].value = hass_password(postData[item].value);
					break;
				}
			}
		
			var formURL = $(this).attr("action");
			$.ajax(
			{
				url : formURL,
				type: "POST",
				data : postData,
				beforeSend:function(){
					$('#nav-login-btn').tooltip('destroy');
					$('#nav-login-btn').tooltip({'title': "Authenticating..."});
					$('#nav-login-btn').tooltip('show');
				},
				success:function(data, textStatus, jqXHR)
				{
					var responseObj = jQuery.parseJSON(jqXHR.responseText);
					if (responseObj.status == "error"){
						$('#nav-login-btn').tooltip('destroy');
						$('#nav-login-btn').tooltip({'title': "<h5>" + responseObj.message + "</h5>", html: true});
						$('#nav-login-btn').tooltip('show');
						$('#nav-login-btn').focusout(function() {
							$(this).tooltip('hide');
						});
					} else {
						$('#nav-login-btn').tooltip('destroy');
						$('#nav-login-btn').tooltip({'title': "<h5>Thanks. Now redirecting...</h5>", html: true});
						$('#nav-login-btn').tooltip('show');
						window.location.href = '/my/dashboard';
					}
				},
				error: function(jqXHR, textStatus, errorThrown)
				{
					alert(errorThrown);
				}
			});
			e.preventDefault(); //STOP default action
		}
	});
	
	$("#register-form").submit(function(e){
		if ($('#reg_first_name').val() === ""){
			$('#reg_first_name').tooltip('show');
			return false;
		} else if ($('#reg_last_name').val() === ""){
			$('#reg_last_name').tooltip('show');
			return false;
		} else if (!isEmail($('#reg_email').val())){
			$('#reg_email').tooltip('show');
			return false;
		} else if (!isPassword($('#reg_password').val())){
			$('#reg_password').tooltip('show');
			return false;
		} else if ($('#reg_confirm_pass').val() != $('#reg_password').val()){
			$('#reg_confirm_pass').tooltip('show');
			return false;
		} else if (!$('#reg_agree').is(':checked')){
			$('#reg_agree').tooltip('show');
			return false;
		} else {
			var postData = $(this).serializeArray();
			for (var item in postData){
				var itemName = postData[item].name;
				if (itemName == 'password' || itemName == 'confirm_pass'){
					postData[item].value = hass_password(postData[item].value);
				}
			}
		
			var formURL = $(this).attr("action");
			$.ajax(
			{
				url : formURL,
				type: "POST",
				data : postData,
				beforeSend:function(){
					$('#reg_submit').tooltip('destroy');
					$('#reg_submit').tooltip({'title': "Submitting..."});
					$('#reg_submit').tooltip('show');
				},
				success:function(data, textStatus, jqXHR)
				{
					var responseObj = jQuery.parseJSON(jqXHR.responseText);
					if (responseObj.status == "error"){
						$('#reg_submit').tooltip('destroy');
						$('#reg_submit').tooltip({'title': responseObj.message});
						$('#reg_submit').tooltip('show');
						$('#reg_submit').focusout(function() {
							$(this).tooltip('hide');
						});
					} else {
						window.location.href = '/my/dashboard';
					}
				},
				error: function(jqXHR, textStatus, errorThrown)
				{
					alert(errorThrown);
				}
			});
			e.preventDefault(); //STOP default action
		}
	});
	
	$("#forgot_password_modal").submit(function(e){
		var rst_result_div = $("#rst_result");
		if (!isEmail($('#rst_email').val())){
			$('#rst_email').tooltip('show');
			return false;
		} else {
			rst_result_div.text("Submitting...");
			rst_result_div.show();
			$.post("/api/resetPassword", {email: $('#rst_email').val(), from: "web_form"}, function(data) {
				if (data.status === "success"){
					rst_result_div.html("<strong>" + data.data.message + "</strong>");
				} else 
					rst_result_div.html("<em>Oops! " + data.message + ".</em>");
			}).done(function() {
			}).fail(function(data) {
				rst_result_div.text("An internal error occurred. Please contact admin.");
			});
		}
		e.preventDefault(); //STOP default action
	});
	
	if (typeof show_notif_modal != 'undefined' && show_notif_modal){
		var strVar="";
		strVar += "<div class=\"modal fade\" id=\"notif_modal\">";
		strVar += "  <div class=\"modal-dialog\">";
		strVar += "    <div class=\"modal-content\">";
		strVar += "      <div class=\"modal-header\">";
		strVar += "        <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-hidden=\"true\">&times;<\/button>";
		strVar += "        <h4 class=\"modal-title\"><span class=\"label label-"+ notif_modal_type +"\">"+ notif_modal_type +"</span> "+ notif_modal_title +"<\/h4>";
		strVar += "      <\/div>";
		strVar += "      <div class=\"modal-body\">";
		strVar += "         " + notif_modal_msg + "";
		strVar += "      <\/div>";
		strVar += "    <\/div><!-- \/.modal-content -->";
		strVar += "  <\/div><!-- \/.modal-dialog -->";
		strVar += "<\/div><!-- \/.modal -->";
		strVar += "";
		$("body").append(strVar);
		$('#notif_modal').modal('show');
	}
});

function isEmail(email){
	return /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*$/.test( email );
}

function isPassword(str){
	return (str && str.length > 5);
}