var ugl_panel_initialized = false;

$(document).ready(function(){
	
	$('.dropdown-toggle').dropdown();
	
	var init = true, state = window.history.pushState !== undefined;
	var cur_value;
	var cur_event_value = window.location.pathname.substring(window.location.pathname.lastIndexOf('/') + 1, window.location.pathname.length);
	
	$.address.state('/my').init(function(event) {
		$('.nav-sidebar a').address();
		cur_value = $.address.state().replace(/^\/$/, '') + event.value;
		cur_event_value = event.value.replace("/", "");
	}).change(function(event) {
		cur_value = $.address.state().replace(/^\/$/, '') + event.value;
		cur_event_value = event.value.replace("/", "");
		
		if (state && init) {
			init = false;
		} else {
			$('.nav-sidebar .active').removeClass('active');
			$('.' + cur_event_value + '-li').addClass('active');
			
			var body = $("body");
			body.append("<div class=\"ajax-loading\"><div class=\"ajax-loading-icon\"></div></div>");
			body.addClass("loading");
			
			// Loads and populates the page data
			$.post(cur_value, function(data){
				$("#main").html(data);
				var init_func = window['init_' + cur_event_value];
				
				if (typeof init_func == 'function')
					init_func();
			}).done(function(){
				//alert( "second success" );
			}).fail(function(data){
				$("#main").html(data);
			}).always(function(){
				body.removeClass("loading");
				$(".ajax-loading").remove();
			});
		}
	});
	
	if (!ugl_panel_initialized && typeof window['init_' + cur_event_value] == 'function'){
		window['init_' + cur_event_value]();
	}
	
});

function logOut(){$.post("/api/logout");}

function init_dashboard(){
	$('.ajax-popup-link').magnificPopup({
		type: 'ajax',
		removalDelay: 300,
		mainClass: 'my-mfp-zoom-in',
		overflowY: 'hidden',
		closeOnBgClick: true,
		ajax: {
			settings: {type: "POST", async: true}, // Ajax settings object that will extend default one - http://api.jquery.com/jQuery.ajax/#jQuery-ajax-settings
			// For example:
			// settings: {cache:false, async:false}
			cursor: 'mfp-ajax-cur', // CSS class that will be added to body during the loading (adds "progress" cursor)
			tError: '<a href="%url%">The content</a> could not be loaded.' //  Error message, can contain %curr% and %total% tags if gallery is enabled
		},
		callbacks: {
			beforeOpen: function() {
			},
			parseAjax: function(mfpResponse) {
				// mfpResponse.data is a "data" object from ajax "success" callback
				// for simple HTML file, it will be just String
				// You may modify it to change contents of the popup
				// For example, to show just #some-element:
				// mfpResponse.data = $(mfpResponse.data).find('#some-element');
				
				// mfpResponse.data must be a String or a DOM (jQuery) element
				
				//console.log('Ajax content loaded:', mfpResponse);
			},
			ajaxContentAdded: function() {
				// Ajax content is loaded and appended to DOM
				//console.log(this.content);
			},
			beforeClose: function() {
				// Callback available since v0.9.0
				//$("body").removeClass("avgrund-ready");
			},
		}
	});
	document.title = "Dashboard | Ugl";
	
	ugl_panel_initialized = true;
}

function init_groups(){
	document.title = "Groups | Ugl";
	
	$("#create_group_form").submit(function(e){
		var prompt_dom = $("#create_group_prompt");
		prompt_dom.html("<img src=\"assets/img/loader.gif\" />").removeClass("hidden");
		$.post(
			"/api/group/create", 
			$("#create_group_form").serialize(),
			function(data){
				if (data.status == "success"){
					if ($('#no_group_alert'))
						$('#no_group_alert').remove();
					console.log(data.groups);
					renderGroupListItem("group-list", data.data.group_data);
					$("#create_group_modal").modal('hide');
					$("#group_count").text(parseInt($("#group_count").text()) + 1);
					$('#group_' + data.data.group_data.id).addClass('animated flash');
					prompt_dom.html("");
				} else {
					prompt_dom.html("<span class=\"alert alert-warning\">" + data.message + "</span>");
				}
			}).fail(function(xhr, textStatus, errorThrown) {
				prompt_dom.html("<span class=\"alert alert-warning\">" + xhr.responseText + "</span>");
		});
		e.preventDefault(); //STOP default action
	});
	
	ugl_panel_initialized = true;
}

function renderGroupListItem(listId, groupObject, hide){
	var listDom = $("#" + listId);
	console.log(groupObject);
	
	if (typeof groupObject == "string")
		groupObject = jQuery.parseJSON(groupObject);
	
	if (typeof groupObject != "object")
		return false;
	
	if (hide == 1) hide = " hidden";
	else hide = "";
	
	if (!groupObject.avatar_url) groupObject.avatar_url = "assets/img/default-avatar-group.png";
	
	listDom.append("<div class=\"col-sm-6 col-md-4" + hide + "\" id=\"group_" + groupObject.id + "\" data-visibility=\"" + groupObject.visibility + "\" data-creator=\"" + groupObject.creator_user_id + "\">" +
		"<div class=\"thumbnail\"><img class=\"avatar\" src=\"" + groupObject.avatar_url + "\" />" +
		"<div class=\"caption text-center\">" +
		"<h3 class=\"group_alias\">" + groupObject.alias + "</h3>" +
		"<p class=\"group_desc\">" +groupObject.description + "</p>" +
		"<div><a class=\"btn btn-primary\" role=\"button\">Enter</a> " +
		"<a class=\"btn btn-default\" role=\"button\">Profile</a> <a class=\"btn btn-warning\" role=\"button\">Leave</a></div>" +
		"</div></div></div>");
}

function init_boards(){
	document.title = "Boards | Ugl";
	ugl_panel_initialized = true;
}

function init_items(){
	document.title = "Items | Ugl";
	ugl_panel_initialized = true;
}

function init_wallet(){
	document.title = "Wallet | Ugl";
	
	ugl_panel_initialized = true;
}

function init_settings(){
	document.title = "Settings | Ugl";
	$('#settingsTab a:first').tab('show');
	ugl_panel_initialized = true;
}

function limitTextArea(elId, counterId, max){
	// update counter
	el = $("#" + elId);
	ct = $("#" + counterId);
	ct.text(max - $('<div/>').text(el.val()).html().length);
	if (parseInt(ct.text()) < 0){
		el.val(function (i, t) {return t.slice(0, -1); });
		ct.text(max - $('<div/>').text(el.val()).html().length);
	}
}