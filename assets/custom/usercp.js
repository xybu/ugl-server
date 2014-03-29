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
		strVar += "         <p>" + notif_modal_msg + "</p>";
		strVar += "      <\/div>";
		strVar += "    <\/div><!-- \/.modal-content -->";
		strVar += "  <\/div><!-- \/.modal-dialog -->";
		strVar += "<\/div><!-- \/.modal -->";
		strVar += "";
		$("body").append(strVar);
		$('#notif_modal').modal('show');
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
		var group_alias = $("#create_group_form #alias");
		if (!group_alias.val().match("^[a-zA-Z0-9_-]{1,32}$")) {
			group_alias.focus();
			group_alias.tooltip("show");
			return false;
		}
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
	
	$("input").on('focus focusout hover blur click', function(e){
		$(this).tooltip('destroy');
	});
	
	$("#find-form").submit(function(e){
		var keyDom = $("#find-keyword");
		if (keyDom.val() == "") {
			keyDom.tooltip('show');
			return false;
		}
		var prompt_dom = $("#find-result");
		prompt_dom.html("<div class=\"col-sm-12 col-md-12 text-center\"><img src=\"assets/img/loader.gif\" /></div>");
		$.post(
			"/api/group/find", 
			$(this).serialize(),
			function(data){
				if (data.status == "success"){
					prompt_dom.html("<div class=\"col-sm-12 col-md-12 text-center text-success\">Found " + data.data.count + " groups.</div>");
					data.data.groups.forEach(function(entry){
						prompt_dom.append("<div class=\"callout callout-info col-md-12 col-sm-12 callout-narrow animated fadeIn\"><h4><a id=\"find-link\" href=\"/my/group/"+entry.id+"\">"+entry.alias+"</a></h4><p>"+entry.description+"</p><p>Tags: "+entry.tags+"</p></div>");
						//console.log(entry);
					});
					$('#find-link').address();
				} else {
					prompt_dom.html("<div class=\"col-sm-12 col-md-12 text-center text-warning\">" + data.message + "</div>");
				}
			}).fail(function(xhr, textStatus, errorThrown) {
				prompt_dom.html("<div class=\"col-sm-12 col-md-12 text-center alert alert-warning\">" + xhr.responseText + "</div>");
		});
		
		e.preventDefault();
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
	
	var invite_modal = $("#invite_modal");
	if (invite_modal.length){
		var invite_form = $("#invite_form");
		var invite_list = $("#invite_form #email_list");
		var invite_prompt = $("#invite_result");
		invite_form.submit(function (e){
			//console.log(e);
			invite_prompt.html("");
			invite_prompt.removeClass("hidden");
			invite_list.val(invite_list.val().replace("\r", "").replace(",", "\n"));
			var emails = invite_list.val().split("\n");
			var email_to_send = [];
			var skipped_list = "";
			var invalid_list = "";
			emails.forEach(function(entry) {
				if (!entry.length) {}
				else if (!isEmail(entry)) invalid_list = invalid_list + "\"" + entry + "\", ";
				else if (email_to_send.length > 9) skipped_list = skipped_list + "\"" + entry + "\", ";
				else if (email_to_send.indexOf(entry) == -1) email_to_send.push(entry);
			});
			if (invalid_list != ""){
				invalid_list = invalid_list.slice(0, -2);
				invite_prompt.append($("<div class=\"alert alert-warning alert-nomargin\" />").text("Skipped invalid email addresses " + invalid_list + "."));
			}
			if (skipped_list != ""){
				skipped_list = skipped_list.slice(0, -2);
				invite_prompt.append($("<div class=\"alert alert-info alert-nomargin text-center\" />").text("You can invite at most 10 people per time. Skipped " + skipped_list + "."));
			}
			if (!email_to_send.length){
				invite_prompt.append($("<div class=\"alert alert-danger alert-nomargin text-center\" />").text("List is empty. No email will be sent."));
			} else {
				invite_prompt.append($("<div class=\"alert alert-info alert-nomargin text-center\" id=\"invite_loading_prompt\" />").text("Sending invitations..."));
				$.post(
					"/api/group/invite", 
					{'group_id': $("#invite_form #group_id").val(), 'invite': email_to_send.join(",")},
					function(data){
						if (data.status == "success")
							$("#invite_loading_prompt").removeClass("alert-info");
							$("#invite_loading_prompt").addClass("alert-success");
							$("#invite_loading_prompt").text(data.data.message);
							if (data.data.skipped.length)
								invite_prompt.append($("<div class=\"alert alert-info alert-nomargin text-center\" />").text("Did not send to:\n" + data.data.skipped.join(", ")));
						else {
							invite_prompt.append($("<div class=\"alert alert-danger alert-nomargin\" />").text(data.message));
						}
					}).fail(function(xhr, textStatus, errorThrown) {
						invite_prompt.html("<span class=\"alert alert-warning\">" + xhr.responseText + "</span>");
				});
			}
			e.preventDefault();
		});
	}
	
	if ($("#manage_group_modal").length) {
	var man_modal = $("#manage_group_modal");
	man_modal.on('shown.bs.modal', function (e) {
		if (man_modal.attr("data-loaded") == undefined){
			$("#manage_group_modal .modal-body").load($(this).attr("data-href"), function(e){
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
				$("#manage_group_modal .modal-body").removeClass("text-center");
				man_modal.attr("data-loaded", "true");
			});
		}
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
	
	listDom.append("<div class=\"col-sm-6 col-md-4 "+hide+"\" id=\"group_" + groupObject.id + "\" data-status=\"" + groupObject.status + "\" data-creator=\"" + groupObject.creator_user_id + "\">" +
		"<div class=\"thumbnail\"><img class=\"large-avatar\" src=\"" + groupObject.avatar_url + "\" />" +
		"<div class=\"caption text-center\">" +
		"<h3 class=\"group_alias\"><a href=\"/my/group/" + groupObject.id + "\" class=\"enter-group\">" + groupObject.alias + "</a></h3>" +
		"<p class=\"group_desc\">" + groupObject.description + "</p>" +
		"</div></div></div>");
	
	$('a.enter-group').address();
}

function toggleBoard(id){
	var dom = $("#board-" + id + "-discussions");
	var toggle_btn = $("#board-" + id + "-toggle i");
	dom.toggle(function() {
		$(this).data("toggled", !$(this).data("toggled"));
	});
	if (dom.data("toggled")) {
		toggle_btn.removeClass("fa-angle-down");
		toggle_btn.addClass("fa-angle-up");
	} else {
		toggle_btn.removeClass("fa-angle-up");
		toggle_btn.addClass("fa-angle-down");
	}
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
	
	var bar = $('#bar');
	var file_dom = $("#upload_avatar_form #avatar_file");
	var prompt_dom = $("#upload_avatar_form #form_prompt");
	
	file_dom.change(function() {
		if (this.files && this.files[0]) {
			if (this.files[0].size > 102400)　{
				alert("Sorry, file size exceeds 100KiB. Please choose another one.");
				$(this).val("");
				return false;
			}
			var reader = new FileReader();
			reader.onload = function(e) {
				prompt_dom.html("");
				prompt_dom.addClass("hidden");
				$('#previewHolder').removeClass('animated bounceIn');
				$('#previewHolder').error(function() {
					prompt_dom.html("<span class=\"text-danger\">The selected file is not an image.</span>");
					prompt_dom.removeClass("hidden");
					file_dom.val("");
					$("#previewHolder").attr("src", "assets/img/default-avatar.png");
					return false;
				}).attr('src', e.target.result);
				$('#previewHolder').addClass('animated bounceIn');
			}
			reader.readAsDataURL(this.files[0]);
		}
	});
	
	$("#upload_avatar_form").ajaxForm({
		url: "/api/user/upload_avatar",
		dataType: 'json',
		beforeSerialize: function() {
			if (!file_dom.val()) {
				prompt_dom.html("<span class=\"text-danger\">Please select an image to upload.</span>").removeClass("hidden");
				return false;
			}/* else if (file_dom.attr("files")[0].size > 102400){
				prompt_dom.html("<span class=\"text-warning\">File must be an image of size no more than 100KiB.</span>").removeClass("hidden");
				return false;
			}*/
		},
		beforeSend: function() {
			var percentVal = '0%';
			bar.attr("aria-valuenow", 0);
			bar.width(percentVal);
			$("#bar_wrapper").removeClass("hidden");
			prompt_dom.html("");
			prompt_dom.addClass("hidden");
		},
		uploadProgress: function(event, position, total, percentComplete) {
			var percentVal = percentComplete + '%';
			bar.attr("aria-valuenow", percentComplete);
			bar.width(percentVal);
		},
		success: function() {
			var percentVal = '100%';
			bar.attr("aria-valuenow", 100);
			bar.width(percentVal);
		},
		complete: function(xhr) {
			$("#bar_wrapper").addClass("hidden");
			//console.log(xhr);
			//status.html(xhr.responseText);
			var response = xhr.responseJSON;
			if (response.status == "success"){
				$("#avatar_url").val(response.data.avatar_url);
				$("#avatar_preview").attr("src", response.data.avatar_url + "?" + Math.random());
				$("#avatar_url_help").html("<span class=\"text-success\">Upload successful.</span>");
				$("#upload_modal").modal('hide');
				$('#avatar_preview').addClass('animated tada');
				$("#my_avatar").attr("src", response.data.avatar_url + "?" + Math.random());
			} else {
				prompt_dom.html("<span class=\"text-warning\">" + response.message + "</span>");
				prompt_dom.removeClass("hidden");
			}
		}
	});
	
	$("#profile_form").ajaxForm({
		url: "/api/user/edit",
		dataType: 'json',
		beforeSerialize: function() {
			$("#profile_form #form_prompt").html("<img src=\"assets/img/loader.gif\">");
			$("#profile_form #form_prompt").removeClass("hidden");
		},
		complete: function(xhr) {
			var response = xhr.responseJSON;
			console.log(response);
			if (response.status == "success"){
				$("#profile_form #form_prompt").html("<span class=\"text-success\">Update success.</span>");
				$(".navbar-nav .name").text(response.data.first_name + " " + response.data.last_name);
			} else {
				
			}
		}
	});
	
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

function refreshPreviewPic(elId, imgId, helperId){
	if ($("#" + elId).length){	// is a DOM object
		elId = $("#" + elId).val();
	}
	var old_img_src = $("#" + imgId).attr("src");
	$("#" + helperId).html("");
	if (elId != "")
		$("#" + imgId).error(function() {
			if (helperId == "") alert("Error loading image.");
			else $("#" + helperId).html("<span class=\"text-danger\">Failed to load the specified image.</span>");
			$("#" + imgId).attr("src", old_img_src);
		}).attr("src", elId + "?" + Math.random());
	else $("#" + imgId).attr("src", "assets/img/default-avatar.png");
}

function isEmail(email){
	return /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*$/.test( email );
}