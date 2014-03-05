/*
$(document).ready(function() { 
	// bind 'myForm' and provide a simple callback function 
	$('#myForm').ajaxForm(function() { 
		alert("Thank you for your comment!"); 
	}); 
});
*/
$("#nav-login").submit(function(e){
	var postData = $(this).serializeArray();
	var formURL = $(this).attr("action");
	$.ajax(
	{
		url : formURL,
		type: "POST",
		data : postData,
		success:function(data, textStatus, jqXHR)
		{
			var responseObj = jQuery.parseJSON(jqXHR.responseText);
			if (responseObj.status == "error")
				alert(responseObj.message);
			else {
				window.location.href = '/user/dashboard';
			}
		},
		error: function(jqXHR, textStatus, errorThrown)
		{
			alert(errorThrown);
		}
	});
	e.preventDefault(); //STOP default action
});
