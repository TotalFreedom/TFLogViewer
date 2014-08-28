<?php
/*
 * Copyright (C) 2012-2014 Steven Lawson

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
$login_password = '';

session_start();

function isLoggedIn()
{
	return $_SESSION['application'] == 'logviewer' && $_SESSION['ip_address'] == $_SERVER['REMOTE_ADDR'] && $_SESSION['browserhash'] == sha1($_SERVER['HTTP_USER_AGENT']);
}

function logout()
{
	$_SESSION = array();
	session_destroy();
}

function login()
{
    $_SESSION['application'] = 'logviewer';
	$_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
	$_SESSION['browserhash'] = sha1($_SERVER['HTTP_USER_AGENT']);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Total Freedom Logviewer</title>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
<script type="text/javascript">
/* <![CDATA[ */
var gRefreshWait;
var gRefreshTimerID;
var gCheckAll = true;

<?php
$php_out = "";

if (!isLoggedIn())
{
	if (preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3})\.\d{1,3}$/', $_SERVER['REMOTE_ADDR'], $matches))
	{
		$remoteAddressPartial = $matches[1] . '.%';
		
		require('mysql_settings.php');
		$mysqli = new mysqli(mysqlSettings::HOSTNAME, mysqlSettings::USERNAME, mysqlSettings::PASSWORD, mysqlSettings::DATABASE);
		if ($mysqli->connect_error) die(sprintf('Connect Error (%s) %s', $mysqli->connect_errno, $mysqli->connect_error));
		
		$query = sprintf("SELECT * FROM `logs_users` WHERE `ip` LIKE '%s'", $remoteAddressPartial);
		$result = $mysqli->query($query) or die('MySQL Query Failed^');
		if ($result->num_rows)
		{
			login();
		}
		
		$result->free();
	}
}

if ($_REQUEST['mode'] == 'doLogin')
{
    if ($_REQUEST['frm_password'] == $login_password)
    {
        login();
    }
    else
    {
        $php_out = '<p>Invalid password!</p><p>Password last changed on 7/27/2013.</p><p>Now, only developers will have the password - everyone else must use the <b>/logs</b> command in-game to register their connection with the logviewer.</p>';
    }
}
else if ($_REQUEST['mode'] == 'doLogout')
{
    logout();
    $php_out = '<p>Logged out.</p>';
}

echo "var gPHPOut = \"$php_out\";" . PHP_EOL;
?>

$(document).ready(function()
{	
	if (top.location != location)
	{
		top.location.href = document.location.href;
	}
	
	$("#refresh_form").submit(function()
	{
		if (gRefreshWait <= 0)
		{			
			updateLog();
		}
		
		return false;
	});
	
	$("input[name='message_filter']").change(function()
	{
		updateFilters();
	});
	
	$(window).resize(function()
	{
		$("#log_holder").height(($(window).height() - $("#header").height()) - 40);
	});
	
	$("#log_holder").height(($(window).height() - $("#header").height()) - 40);
	
	$("#check_toggle").click(function()
	{
		gCheckAll = !gCheckAll;
		updateCheckAll();
	});
	
	updateLog();
});

function updateFilters()
{
	$("input[name='message_filter']").each(function()
	{
		if ($(this).is(':checked'))
		{
			some_checked = true;
			
			$('.' + $(this).val()).show();
		}
		else
		{
			$('.' + $(this).val()).hide();
		}
	});
}

function updateLog()
{
	$("#log_body").html('');
	$('#log_spinner').show();
	$("#refresh_btn").prop('disabled', true);
	$("#refresh_btn").val("Refreshing...")
	
	$.ajax(
	{
		url: 'pull_log.php', dataType: 'html', data: $("#refresh_form").serialize(), type: 'POST',
		success: function(data, textStatus, XMLHttpRequest)
		{
			$('#log_spinner').hide();
			$("#log_body").html(gPHPOut + data);
			$("#log_holder").scrollTop($("#log_holder")[0].scrollHeight);
			start_wait_timer();
			updateFilters();
		},
		error: function(XMLHttpRequest, textStatus, errorThrown)
		{
			$('#log_spinner').hide();
			$("#log_body").html(textStatus);
			start_wait_timer();
		}
	});
}

function start_wait_timer()
{
	gRefreshWait = 5;
	
	gRefreshTimerID = window.setInterval(function()
	{
		if (--gRefreshWait <= 0)
		{
			$("#refresh_btn").prop('disabled', false);
			$("#refresh_btn").val("Refresh Log");
			clearInterval(gRefreshTimerID);
		}
		else
		{
			$("#refresh_btn").val("Refresh Log (Please Wait " + gRefreshWait + "s)");
		}
	}
	, 1000);
}

function updateCheckAll()
{
	if (gCheckAll)
	{
		$("#message_filters input:checkbox").prop('checked', true);
		$("#check_toggle").val("None");
	}
	else
	{
		$("#message_filters input:checkbox").prop('checked', false);
		$("#check_toggle").val("All");
	}
	
	updateFilters();
}

var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-12595157-6']);
_gaq.push(['_trackPageview']);

(function () {
    var ga = document.createElement('script');
    ga.type = 'text/javascript';
    ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(ga, s);
})();
/* ]]> */
</script>
<style type="text/css">
html, body
{
	font-family: "Courier New", Courier, monospace;
	font-size: 14px;
}
p
{
	margin: .1em 0;
}
.chat_message
{
	color: #B8022F;
}
.normal_command
{
	color: #F0F;
}
.preprocess_command
{
    color: #B0B;
}
.we_command
{
	color: #00F;
}
.alert_command
{
	background-color: #F00;
	color: #CCCCCC;
}
.error_message
{
	color:#B08400;
}
.login_message
{
	font-weight:bold;
}
.other_message
{
}
#log_spinner
{
	background-image:url(ajax-loader.gif);
	width:100px;
	height:100px;
	margin:auto;
	display:none;
}
#log_holder
{
	height:100px;
	overflow:scroll;
	overflow-style:auto;
}
#alert
{
	font-family: Verdana, Arial, Helvetica, sans-serif
	font-size: 16px;
    color: red;
    font-weight: bold;
}
</style>
</head>
<body>

<div id="header">

<strong>Message Filter:</strong>

<input name="check_toggle" type="button" value="None" id="check_toggle" />

<br />

<div id="message_filters">

<table width="100%" border="1" cellspacing="1" cellpadding="1">
  <tr>
    <td width="25%"><input type="checkbox" name="message_filter" value="chat_message" id="chat_message" checked="checked" /><label for="chat_message">Chat Messages</label></td>
    <td width="25%"><input type="checkbox" name="message_filter" value="login_message" id="login_message" checked="checked" /><label for="login_message">Login Messages</label></td>
    <td width="25%"><input type="checkbox" name="message_filter" value="we_command" id="we_command" checked="checked" /><label for="we_command">WorldEdit Commands</label></td>
    <td width="25%"><input type="checkbox" name="message_filter" value="normal_command" id="normal_command" checked="checked" /><label for="normal_command">Misc Commands</label></td>
  </tr>
  <tr>
    <td><input type="checkbox" name="message_filter" value="alert_command" id="alert_command" checked="checked" /><label for="alert_command">Alert Commands (Bans, kicks, etc)</label></td>
    <td><input type="checkbox" name="message_filter" value="error_message" id="error_message" checked="checked" /><label for="error_message">[SEVERE] or [WARNING] errors</label></td>
    <td><input type="checkbox" name="message_filter" value="preprocess_command" id="preprocess_command" checked="checked" /><label for="preprocess_command">Preprocess Commands</label></td>
    <td><input type="checkbox" name="message_filter" value="other_message" id="other_message" checked="checked" /><label for="other_message">All Other Messages</label></td>
  </tr>
</table>

</div>

<hr />

<form action="index.html" id="refresh_form" method="get">
Log Size (kb): <input name="size" type="text" value="64" size="10" maxlength="10" /><br />
Text Filter (Requires Refresh): <input name="text_filter" type="text" id="text_filter" size="50" maxlength="50" /><br />
<input id="refresh_btn" type="submit" value="Refresh Log (Please Wait 5s)" disabled="disabled" />
</form>

<hr />

</div>

<div id="log_holder">
<div id="log_spinner"></div>
<div id="log_body"></div>
</div>

</body>
</html>
