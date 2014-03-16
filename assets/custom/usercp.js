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
	/*
	// load group list
	$.post("/api/listGroupsOf/me", function(data){
		if (data.status == "success"){
			var numOfGroups = 0;
			$.each(data.data, function(k, v){
				++numOfGroups;
				var group_avatar = v.avatar_url;
				var group_identity = "group_" + v.id;
				if (!group_avatar) 
					group_avatar = "assets/img/default-avatar-group.png";
				$("#group-list").append("<div class=\"col-xs-6 col-sm-4 col-md-3\">" +
										"<div class=\"thumbnail\"><img class=\"avatar\" id=\"" + group_identity + "_avatar\" src=\"" + group_avatar + "\" />" +
										"<div class=\"caption text-center\">" + 
										"<h3 id=\"" + group_identity + "_alias\" data-visibility=\"" + v.visibility + "\" data-creator=\"" + v.creator_user_id + "\">" + v.alias + "</h3>" +
										"<p id=\"" + group_identity + "_desc\">" + v.description + "</p>" +
										"<div><a class=\"btn btn-primary\" role=\"button\">Enter</a>" +
										"<a class=\"btn btn-default\" role=\"button\">Profile</a></div>" +
										"<div id=\"" + group_identity + "_members\" class=\"hidden\">" + v.users + "</div>" + 
										"<div id=\"" + group_identity + "_tags\" class=\"hidden\">" + v.tags + "</div>" + 
										"</div></div></div>");
			});
			if (numOfGroups == 0){
				$("#group-loader").html("<span class=\"alert alert-info\">Oops, you don't have any groups yet...</span>");
			} else {
				$("#group-loader").remove();
			}
		} else {
			$("#group-loader").html("<span class=\"alert alert-warning\">Oops, error happened loading your groups...</span>");
		}
	}).fail(function(xhr, textStatus, errorThrown) {
		$("#group-loader").html("<span class=\"alert alert-warning\">" + xhr.responseText + "</span>");
    });
	*/
	ugl_panel_initialized = true;
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

function init_profile(){
	document.title = "Settings | Ugl";
	ugl_panel_initialized = true;
}