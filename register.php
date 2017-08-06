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
$log = new KLogger('./register_logs/', KLogger::DEBUG);

class Register
{

    public static function run()
    {
        global $log;

        $error_status = 500;
        $mysqli = null;
        $success_msg = "OK";

        try
        {
            $mode = self::filter_input_safe(INPUT_GET, 'mode');
            $name = self::filter_input_safe(INPUT_GET, 'name');
            $key = self::filter_input_safe(INPUT_GET, 'key');
            $password = self::filter_input_safe(INPUT_GET, 'password');
            $ip = $_SERVER['REMOTE_ADDR'];

            if ($mode !== false)
            {
                $mode = strtolower($mode);
            }

            if ($name !== false)
            {
                $name = strtolower($name);
            }

            $log->logDebug("Opened connection from $ip - Mode = $mode");

            if (!$mode)
            {
                $error_status = 400;
                throw new Exception("Invalid mode - Mode = $mode");
            }

            if (self::strequal($mode, 'add') || self::strequal($mode, 'delete') || self::strequal($mode, 'purge'))
            {
                if (!self::strequal($password, GeneralSettings::REGISTER_PASSWORD) || !self::strequal($ip, GeneralSettings::ALLOWED_REMOTE_SERVER))
                {
                    $error_status = 403;
                    throw new Exception("Bad Authentication - IP = $ip");
                }

                $log->logDebug('Authentication OK');
            }

            try
            {
                $mysqli = Common::getSQLConnection();
            }
            catch (Exception $ex)
            {
                $error_status = 500;
                throw new Exception('MySQL Connection Error');
            }

            $verified = false;

            if (self::strequal($mode, 'purge'))
            {
                $mysqli->query("TRUNCATE TABLE logs_users");
                $success_msg = 'Logviewer users purged';
            }
            else
            {
                if (self::strequal($mode, 'add') || self::strequal($mode, 'verify'))
                {
                    if (!$key || strlen($key) < 10)
                    {
                        $error_status = 400;
                        throw new Exception("Key must be at least 10 characters - Key = $key");
                    }
                }

                if (!$name || strlen($name) < 4)
                {
                    $error_status = 400;
                    throw new Exception("Name must be at least 4 characters - Name = $name");
                }

                if (self::strequal($mode, 'add'))
                {
                    $stmt = $mysqli->prepare('DELETE FROM logs_users WHERE name = ?');
                    $stmt->bind_param('s', $name);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $mysqli->prepare('INSERT INTO logs_users (name, reg_key) VALUES (?, ?)');
                    $stmt->bind_param('ss', $name, $key);
                    $stmt->execute();
                    $stmt->close();

                    $success_msg = "User entry added - Name = $name, Key = $key";
                }
                else if (self::strequal($mode, 'delete'))
                {
                    $stmt = $mysqli->prepare('DELETE FROM logs_users WHERE name = ?');
                    $stmt->bind_param('s', $name);
                    $stmt->execute();
                    $stmt->close();

                    $success_msg = "User entries deleted - Name = $name";
                }
                else if (self::strequal($mode, 'verify'))
                {
                    $row_id = NULL;

                    $stmt = $mysqli->prepare('SELECT id FROM logs_users WHERE ip IS NULL AND name = ? AND reg_key = ? AND reg_key IS NOT NULL LIMIT 1');
                    $stmt->bind_param('ss', $name, $key);
                    $stmt->execute();
                    $stmt->bind_result($row_id);
                    $stmt->fetch();
                    $stmt->close();

                    if (is_null($row_id))
                    {
                        $error_status = 403;
                        throw new Exception('Invalid verification parameters.');
                    }

                    $stmt = $mysqli->prepare('UPDATE logs_users SET ip = ? WHERE id = ?');
                    $stmt->bind_param('ss', $ip, $row_id);
                    $stmt->execute();
                    $stmt->close();

                    $success_msg = "User verified - Name = $name, IP = $ip";

                    $verified = true;
                }
                else
                {
                    $error_status = 400;
                    throw new Exception("Invalid mode - Mode = $mode");
                }
            }

            if (isset($mysqli) && !is_null($mysqli))
            {
                $mysqli->close();
                unset($mysqli);
            }

            if ($verified)
            {
                $log->logDebug("(Status: 200) $success_msg");
                http_response_code(200);
                readfile('verified.html');
                die();
            }
            else
            {
                self::logAndDie($log, $success_msg, 200, false);
            }
        }
        catch (Exception $ex)
        {
            if (isset($mysqli) && !is_null($mysqli))
            {
                $mysqli->close();
                unset($mysqli);
            }

            self::logAndDie($log, $ex->getMessage(), $error_status, true);
        }
    }

    private static function logAndDie($log, $message, $code, $fatal = true)
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Thu, 01 Jan 1970 00:00:01 GMT');
        header('Content-Type: text/plain');

        global $log;
        http_response_code($code);
        if ($fatal)
        {
            $log->logFatal("(Status: $code) $message");
        }
        else
        {
            $log->logDebug("(Status: $code) $message");
        }
        die($message);
    }

    private static function strequal($str1, $str2)
    {
        if ($str1 === false || $str2 === false)
        {
            return false;
        }
        return (strcmp($str1, $str2) === 0);
    }

    private static function filter_input_safe($type, $variable_name)
    {
        $value = filter_input($type, $variable_name);
        if (!isset($value) || is_null($value) || $value === false)
        {
            return false;
        }
        return $value;
    }
}

Register::run();
