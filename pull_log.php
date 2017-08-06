<?php

/*
    Copyright 2012-2017 Steven Lawson

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
*/

require_once('common.php');

require_once('KLogger.php');
$log = new KLogger('./access_logs/', KLogger::INFO);

class PullLog
{

    private static function isSuperAdmin()
    {
        global $log;

        try
        {
            $mysqli = Common::getSQLConnection();
        }
        catch (Exception $ex)
        {
            die($ex);
        }

        $admin_name = NULL;

        $remote_addr = $mysqli->real_escape_string($_SERVER['REMOTE_ADDR']);
        $result = $mysqli->query("SELECT * FROM `logs_users` WHERE `ip` = '$remote_addr'");
        if (!$result)
        {
            die('MySQL Query Failed');
        }
        while ($row = $result->fetch_assoc())
        {
            $admin_name = $row['name'];
            break;
        }

        $success = false;

        $result->free();

        if (!is_null($admin_name))
        {
            $log->logInfo("Logs user table resolves ip '$remote_addr' to user '$admin_name'.");

            $json = self::getPlayerList();
            $admins = array_merge($json['superadmins'], $json['telnetadmins'], $json['senioradmins']);

            foreach ($admins as $needle)
            {
                if (strcasecmp($admin_name, $needle) === 0)
                {
                    $success = true;
                    break;
                }
            }

            if (!$success)
            {
                $log->logInfo("User '$admin_name' is not an active admin. Removing from table and denying access.");

                $admin_name = $mysqli->real_escape_string($admin_name);
                $result = $mysqli->query("DELETE FROM `logs_users` WHERE `name` LIKE '$admin_name'");
            }
        }
        else
        {
            $log->logInfo("No logs table record for '$remote_addr' - denying access.");
        }

        return $success;
    }

    private static function getPlayerList()
    {
        $usingCachedData = false;

        $ch = curl_init();
        $playersFileHandle = fopen(GeneralSettings::PLAYERS_FILE, 'w');
        curl_setopt_array($ch, array(
            CURLOPT_URL => sprintf("http://%s/players", GeneralSettings::HTTPD_ADDRESS),
            CURLOPT_TIMEOUT => 5.0,
            CURLOPT_FILE => $playersFileHandle
        ));
        $response = @curl_exec($ch);
        fclose($playersFileHandle);
        curl_close($ch);

        if ($response === false)
        {
            $usingCachedData = true;
        }

        $rawData = @file_get_contents(GeneralSettings::PLAYERS_FILE);

        $decoded = @json_decode($rawData, true);
        if ($usingCachedData || $decoded === NULL)
        {
            $rawData = @file_get_contents(GeneralSettings::PLAYERS_FILE_VERIFIED);
            $decoded = @json_decode($rawData, true);
            $usingCachedData = true;
        }
        else
        {
            @file_put_contents(GeneralSettings::PLAYERS_FILE_VERIFIED, $rawData);
        }

        return $decoded;
    }

    public static function run()
    {
        global $log;

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Thu, 01 Jan 1970 00:00:01 GMT');

        $remote_addr = $_SERVER['REMOTE_ADDR'];
        $log->logInfo("----------");
        $log->logInfo("Opened connection from ip '$remote_addr'.");

        if (Common::isLoggedIn() || self::isSuperAdmin())
        {
            $request_size = 64;
            if (isset($_REQUEST['size']))
            {
                $request_size = (int) $_REQUEST['size'];
                if ($request_size < 2)
                {
                    $request_size = 2;
                }
                else if ($request_size > 1024)
                {
                    $request_size = 1024;
                }
            }

            $max_length = $request_size * 1024;

            //Get Log - BukkitHTTPD

            $ch = curl_init();
            $server_log_handle = fopen(GeneralSettings::LOGFILE_NAME, 'w');

            curl_setopt_array($ch, array(
                CURLOPT_URL => sprintf("http://%s/logs?password=%s&range=tail=%d", GeneralSettings::HTTPD_ADDRESS, GeneralSettings::HTTPD_PASSWORD, $max_length),
                CURLOPT_TIMEOUT => 5.0,
                CURLOPT_FILE => $server_log_handle
            ));
            $response = curl_exec($ch);

            fclose($server_log_handle);
            curl_close($ch);

            if ($response === false)
            {
                print("Can't connect to server. Showing last log downloaded.\n");
            }

            //Display server.log:
            
            if (isset($_REQUEST['text_filter']))
            {
                $text_filter = $_REQUEST['text_filter'];
            }
            if (!isset($text_filter) || $text_filter === '')
            {
                $text_filter = NULL;
            }
            else
            {
                $text_filter = strtolower($text_filter);
            }

            $server_log_handle = fopen(GeneralSettings::LOGFILE_NAME, 'r');
            while (($buffer = fgets($server_log_handle)) !== false)
            {
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
                    $msg_class = 'alert_command log_message';
                }
                else if (preg_match('/: \/gadmin/', $buffer))
                {
                    $msg_class = 'alert_command log_message';
                }
                else if (preg_match('/: \/gtfo/', $buffer))
                {
                    $msg_class = 'alert_command log_message';
                }
                else if (preg_match('/: \/tempban/', $buffer))
                {
                    $msg_class = 'alert_command log_message';
                }
                else if (preg_match('/: \/smite/', $buffer))
                {
                    $msg_class = 'alert_command log_message';
                }
                else if (preg_match('/\/INFO\]: \[PLAYER_COMMAND\]/', $buffer))
                {
                    $msg_class = 'normal_command log_message';
                }
                else if (preg_match('/\/INFO\]: \[CONSOLE_COMMAND\]/', $buffer))
                {
                    $msg_class = 'normal_command log_message';
                }
                if (preg_match('/\]: .+? issued server command: /', $buffer))
                {
                    $msg_class = 'normal_command log_message';
                }
                else if (preg_match('/\/INFO\]: \[PREPROCESS_COMMAND\]/', $buffer))
                {
                    $msg_class = 'preprocess_command log_message';
                }
                else if (preg_match('/\/INFO\]: WorldEdit:/', $buffer))
                {
                    $msg_class = 'we_command log_message';
                }
                else if (preg_match('/\[Async Chat Thread - #\d+?\/INFO\]: </', $buffer))
                {
                    $msg_class = 'chat_message log_message';
                }
                else if (preg_match('/\/INFO\]: \[Server:/', $buffer))
                {
                    $msg_class = 'chat_message log_message';
                }
                else if (preg_match('/\/INFO\]: \[CONSOLE\]</', $buffer))
                {
                    $msg_class = 'chat_message log_message';
                }
                else if (preg_match('/\/INFO\]: \[TotalFreedomMod\] \[ADMIN\] /', $buffer))
                {
                    $msg_class = 'chat_message log_message';
                }
                else if (preg_match('/\/INFO\]: \[JOIN\] /', $buffer, $matches))
                {
                    $msg_class = 'login_message log_message';
                }
                else if (preg_match('/\/INFO\]: .+?\[\/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}:\d+\] logged in with entity id/', $buffer, $matches))
                {
                    $msg_class = 'login_message log_message';
                }
                else if (preg_match('/\/WARN\]:/', $buffer))
                {
                    $msg_class = 'error_message log_message';
                }
                else if (preg_match('/\/ERROR\]:/', $buffer))
                {
                    $msg_class = 'error_message log_message';
                }
                else if (preg_match('/\/SEVERE\]:/', $buffer))
                {
                    $msg_class = 'error_message log_message';
                }
                else if (preg_match('/^(at )?(com|net|org|me)\./', $buffer))
                {
                    $msg_class = 'error_message log_message';
                }
                else
                {
                    $msg_class = 'other_message log_message';
                }

                printf('<p class="%s">%s</p>' . PHP_EOL, $msg_class, trim(htmlspecialchars($buffer)));
            }
            fclose($server_log_handle);
        }
        else
        {
            readfile('pull_log_not_logged_in.html');
        }
    }

}

PullLog::run();
