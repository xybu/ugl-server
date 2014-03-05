/*
$(document).ready(function() { 
	// bind 'myForm' and provide a simple callback function 
	$('#myForm').ajaxForm(function() { 
		alert("Thank you for your comment!"); 
	}); 
});
*/

$("input").on('click', function(e){
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
					$('#nav-login-btn').tooltip({'title': responseObj.message});
					$('#nav-login-btn').tooltip('show');
					$('#nav-login-btn').focusout(function() {
						$(this).tooltip('hide');
					});
				} else {
					window.location.href = '/user/dashboard';
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

function isEmail(email){
	return /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*$/.test( email );
}

function isPassword(str){
	return (str && str.length > 5);
}