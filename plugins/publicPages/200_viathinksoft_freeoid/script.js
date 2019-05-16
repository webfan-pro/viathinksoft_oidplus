/*
 * OIDplus 2.0
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

function freeOIDFormOnSubmit() {
	$.ajax({
		url: "ajax.php",
		type: "POST",
		data: {
			action: "com.viathinksoft.freeoid.request_freeoid",
			email: $("#email").val(),
			captcha: document.getElementsByClassName('g-recaptcha').length > 0 ? grecaptcha.getResponse() : null
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert("Error: " + errorThrown);
			if (document.getElementsByClassName('g-recaptcha').length > 0) grecaptcha.reset();
		},
		success: function(data) {
			if ("error" in data) {
				alert("Error: " + data.error);
				if (document.getElementsByClassName('g-recaptcha').length > 0) grecaptcha.reset();
			} else if (data.status == 0) {
				alert("Instructions have been sent via email.");
				document.location = '?goto=oidplus:system';
				//reloadContent();
			} else {
				alert("Error: " + data);
				if (document.getElementsByClassName('g-recaptcha').length > 0) grecaptcha.reset();
			}
		}
	});
	return false;
}

function activateFreeOIDFormOnSubmit() {
	$.ajax({
		url: "ajax.php",
		type: "POST",
		data: {
			action: "com.viathinksoft.freeoid.activate_freeoid",
			email: $("#email").val(),
			ra_name: $("#ra_name").val(),
			title: $("#title").val(),
			url: $("#url").val(),
			auth: $("#auth").val(),
			password1: $("#password1").val(),
			password2: $("#password2").val(),
			timestamp: $("#timestamp").val()
		},
		error:function(jqXHR, textStatus, errorThrown) {
			alert("Error: " + errorThrown);
		},
		success: function(data) {
			if ("error" in data) {
				alert("Error: " + data.error);
			} else if (data.status == 0) {
				alert("Registration successful! You can now log in.");
				document.location = '?goto=oidplus:login';
				//reloadContent();
			} else {
				alert("Error: " + data);
			}
		}
	});
	return false;
}

