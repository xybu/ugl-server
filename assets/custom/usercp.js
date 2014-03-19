var ugl_panel_initialized = false;

var serialize = function(obj, re) {
                var result = [];
                $.each(obj, function(i, val) {
                    if ((re && re.test(i)) || !re)
                        result.push(i + ': ' + (typeof val == 'object' ? val.join 
                            ? '\'' + val.join(', ') + '\'' : serialize(val) : '\'' + val + '\''));
                });
                return '{' + result.join(', ') + '}';
            };
			
$(document).ready(function(){
	
	$('.dropdown-toggle').dropdown();
	
	var init = true, state = window.history.pushState !== undefined;
	
	var cur_event_params = window.location.pathname.split("/");
	
	if (cur_event_params[cur_event_params.length - 2] == "my")
		cur_event = cur_event_params[cur_event_params.length - 1];
	else cur_event = cur_event_params[cur_event_params.length - 2];
	
	console.log(cur_event);
	
	$.address.state("/my").init(function(event) {
		console.log('init: ' + serialize({
			value: $.address.value(), 
			path: $.address.path(),
			pathNames: $.address.pathNames(),
			parameterNames: $.address.parameterNames(),
			queryString: $.address.queryString()
		}));
		$('.nav-sidebar a').address();
		$('a.enter-group').address();
	}).change(function(event) {
		
		cur_event = event.pathNames[0];
		
		if (state && init) {
			init = false;
		} else {
			console.log('change: ' + serialize(event, /parameters|parametersNames|path|pathNames|queryString|value/));
			
			if ($('.' + cur_event + '-li').length){
				$('.nav-sidebar .active').removeClass('active');
				$('.' + cur_event + '-li').addClass('active');
			}
			
			var body = $("body");
			body.append("<div class=\"ajax-loading\"><div class=\"ajax-loading-icon\"></div></div>");
			body.addClass("loading");
			
			$.post("/my" + event.path, function(data){
				$("#main").html(data);
				var init_func = window['init_' + cur_event];
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
	
	if (!ugl_panel_initialized && typeof window['init_' + cur_event] == 'function'){
		window['init_' + cur_event]();
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
	$('a.enter-group').address();
	ugl_panel_initialized = true;
}

function init_group(){
	document.title = $("#group-alias").text() + " | Ugl";
	
	$("#leave_group_form").submit(function(e){
		var prompt_dom = $("#leave_group_form #form_prompt");
		prompt_dom.html("<img src=\"assets/img/loader.gif\" />").removeClass("hidden");
		$.post(
			"/api/group/leave", 
			$("#leave_group_form").serialize(),
			function(data){
				if (data.status == "success"){
					$("#leave_group_modal").modal('hide');
					window.location.href = '/my/groups';
				} else {
					prompt_dom.html("<span class=\"alert alert-warning\">" + data.message + "</span>");
				}
			}).fail(function(xhr, textStatus, errorThrown) {
				prompt_dom.html("<span class=\"alert alert-warning\">" + xhr.responseText + "</span>");
			}
		);
		e.preventDefault(); //STOP default action
	});
	
	if ($("#edit_group_form").length){
		$("#edit_group_form").submit(function(e){
			var prompt_dom = $("#edit_group_form #form_prompt");
			prompt_dom.html("<img src=\"assets/img/loader.gif\" />").removeClass("hidden");
			$.post(
				$(this).attr("action"),
				$(this).serialize(),
				function(data){
					if (data.status == "success"){
						location.reload();
					} else {
						prompt_dom.html("<span class=\"alert alert-warning\">" + data.message + "</span>");
					}
				}).fail(function(xhr, textStatus, errorThrown) {
					prompt_dom.html("<span class=\"alert alert-warning\">" + xhr.responseText + "</span>");
			});
			e.preventDefault(); //STOP default action
		});
	}
	
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
		"<div class=\"thumbnail\"><img class=\"group-avatar\" src=\"" + groupObject.avatar_url + "\" />" +
		"<div class=\"caption text-center\">" +
		"<h3 class=\"group_alias\">" + groupObject.alias + "</h3>" +
		"<p class=\"group_desc\">" +groupObject.description + "</p>" +
		"<div><a class=\"btn btn-primary enter-group\" role=\"button\">Enter</a> " +
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