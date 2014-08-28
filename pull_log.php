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
session_start();

header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

function isLoggedIn()
{
	return $_SESSION['application'] == 'logviewer' && $_SESSION['ip_address'] == $_SERVER['REMOTE_ADDR'] && $_SESSION['browserhash'] == sha1($_SERVER['HTTP_USER_AGENT']);
}

if (isLoggedIn())
{
    $request_size = 64;
    if (isset($_REQUEST['size']))
    {
        $request_size = (int)$_REQUEST['size'];
        if ($request_size < 2) $request_size = 2;
        else if ($request_size > 1024) $request_size = 1024;
    }

    $logfile_name_remote = 'latest.log';
	$logfile_name = 'server.log';
    $max_length = $request_size * 1024;

    $ftp_url = '';
    $ftp_username = '';
    $ftp_password = '';
    
    $httpd_address = '';
    $httpd_password = '';
    
    //Get Log - BukkitHTTPD

    $ch = curl_init();
    $server_log_handle = fopen($logfile_name, 'w');

    curl_setopt_array($ch, array(
        CURLOPT_URL=>sprintf("http://%s/logs?password=%s&range=tail=%d", $httpd_address, $httpd_password, $max_length),
        CURLOPT_TIMEOUT=>5.0,
        CURLOPT_FILE=>$server_log_handle
    ));
    $response = curl_exec($ch);

    fclose($server_log_handle);
    curl_close($ch);

    if ($response === false)
    {
		printf('<p class="%s">%s</p>' . PHP_EOL, 'other_message', trim(htmlspecialchars("Using FTP to pull log - is the server offline?")));
        
		//Get Log Length - FTP:
        
        $ch = curl_init();
        
        curl_setopt_array($ch, array(
            CURLOPT_URL=>$ftp_url . $logfile_name_remote,
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_TIMEOUT=>20.0,
            CURLOPT_USERPWD=>$ftp_username . ":" . $ftp_password,
            CURLOPT_HEADER=>false,
            CURLOPT_NOBODY=>true
        ));
        
        if (curl_exec($ch) === FALSE)
        {
            die("Can't connect to FTP server. Someone else might be using the logviewer, or the server might be offline.");
        }
        
        $logsize = (int)curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        
        if ($logsize > $max_length)
        {
            curl_setopt($ch, CURLOPT_RANGE, sprintf("-%d", $max_length));
        }
        
        //Get Full Log - FTP:
        
        curl_setopt($ch, CURLOPT_NOBODY, false);
        
        if ($response = curl_exec($ch))
        {
            $server_log_handle = fopen($logfile_name, 'w');
            fwrite($server_log_handle, $response);
            fclose($server_log_handle);
        }
        else
        {
            die("Can't download logfile.");
        }
        
        curl_close($ch);
    }

    //Display server.log:
    
    $text_filter = $_REQUEST['text_filter'];
    if (!isset($text_filter) || $text_filter === '')
    {
        $text_filter = NULL;
    }
    else
    {
        $text_filter = strtolower($text_filter);
    }
    
    $last = null;

    $server_log_handle = fopen($logfile_name, 'r');
    while (($buffer = fgets($server_log_handle)) !== false)
    {
        // $buffer = preg_replace('/\xA7./', '', $buffer);
        // $buffer = preg_replace('/\x1B\[\d\dm/', '', $buffer);
        // $buffer = preg_replace('/\x1B\[0m/', '', $buffer);
        // $buffer = preg_replace('/[^\x20-\x7F]/', '', $buffer);
        $buffer = preg_replace('/\?[0-9a-f]/', '', $buffer);
        
        $buffer = trim($buffer);
        
        if (!is_null($text_filter))
        {
            if (strrpos(strtolower($buffer), $text_filter) === false)
            {
                continue;
            }
        }
        
        if (preg_match('/: \/glist/', $buffer))
        {
            $msg_class = 'alert_command';
        }
        else if (preg_match('/: \/gadmin/', $buffer))
        {
            $msg_class = 'alert_command';
        }
        else if (preg_match('/: \/gtfo/', $buffer))
        {
            $msg_class = 'alert_command';
        }
        else if (preg_match('/: \/tempban/', $buffer))
        {
            $msg_class = 'alert_command';
        }
        else if (preg_match('/\/INFO\]: \[PLAYER_COMMAND\]/', $buffer))
        {
            $msg_class = 'normal_command';
            $last = null;
        }
        else if (preg_match('/\/INFO\]: \[CONSOLE_COMMAND\]/', $buffer))
        {
            $msg_class = 'normal_command';
        }
        else if (preg_match('/\/INFO\]: \[PREPROCESS_COMMAND\]/', $buffer))
        {
            $msg_class = 'preprocess_command';
        }
        else if (preg_match('/\/INFO\]: WorldEdit:/', $buffer))
        {
            $msg_class = 'we_command';
			$last = null;
        }
        else if (preg_match('/\[Netty IO #\d+?\/INFO\]: </', $buffer))
        {
            $msg_class = 'chat_message';
        }
        else if (preg_match('/\/INFO\]: \[Server:/', $buffer))
        {
            $msg_class = 'chat_message';
        }
        else if (preg_match('/\/INFO\]: \[CONSOLE\]</', $buffer))
        {
            $msg_class = 'chat_message';
        }
        else if (preg_match('/\/INFO\]: \[TotalFreedomMod\] \[ADMIN\] /', $buffer))
        {
            $msg_class = 'chat_message';
        }
        else if (preg_match('/\/INFO\]: \[JOIN\] /', $buffer, $matches))
        {
            $msg_class = 'login_message';
        }
        else if (preg_match('/\/INFO\]: .+?\[\/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}:\d+\] logged in with entity id/', $buffer, $matches))
        {
            $msg_class = 'login_message';
        }
        else if (preg_match('/\/ERROR\]:/', $buffer))
        {
            $msg_class = 'error_message';
        }
        else if (preg_match('/\/WARNING\]:/', $buffer))
        {
            $msg_class = 'error_message';
        }
        else if (preg_match('/\/SEVERE\]:/', $buffer))
        {
            $msg_class = 'error_message';
        }
        else
        {
            $msg_class = 'other_message';
        }
        
        if ($msg_class === 'preprocess_command')
        {
            $last = sprintf('<p class="%s">%s</p>' . PHP_EOL, $msg_class, trim(htmlspecialchars($buffer)));
        }
        else
        {
            if (!is_null($last))
            {
                printf($last);
                $last = null;
            }
            printf('<p class="%s">%s</p>' . PHP_EOL, $msg_class, trim(htmlspecialchars($buffer)));
        }
    }
    if (!is_null($last))
    {
        printf($last);
        $last = null;
    }
    fclose($server_log_handle);
}
else
{
?>
<p>You must log in to access the logviewer data.</p>
<form id="frm_login" name="frm_login" action="index.php" method="post">
    <input name="mode" type="hidden" value="doLogin" />
    <table width="300" border="0" cellspacing="2" cellpadding="2">
        <tr>
            <td><label for="frm_password">Password:</label></td>
            <td><input name="frm_password" id="frm_password" type="password" /></td>
        </tr>
    </table>
    <input name="login" type="submit" value="Log In" />
</form>
<?php
}
?>
