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

session_start();

require_once('config.php');

class Common
{

    public static function getSQLConnection()
    {
        $mysqli = new mysqli(MySQLSettings::HOSTNAME, MySQLSettings::USERNAME, MySQLSettings::PASSWORD, MySQLSettings::DATABASE);
        if ($mysqli->connect_error)
        {
            throw new Exception("DATABASE_CONNECTION_ERROR,$mysqli->connect_errno,$mysqli->connect_error");
        }
        return $mysqli;
    }

    public static function isLoggedIn()
    {
        if (!isset($_SESSION['application']) || !isset($_SESSION['ip_address']) || !isset($_SESSION['browserhash']))
        {
            return false;
        }

        if (strcmp($_SESSION['application'], 'logviewer') !== 0)
        {
            return false;
        }

        if (strcmp($_SESSION['ip_address'], $_SERVER['REMOTE_ADDR']) !== 0)
        {
            return false;
        }

        if (strcmp($_SESSION['browserhash'], sha1($_SERVER['HTTP_USER_AGENT'])) !== 0)
        {
            return false;
        }
        
        return true;
    }

    public static function logout()
    {
        $_SESSION = array();
        session_destroy();
    }

    public static function login()
    {
        $_SESSION['application'] = 'logviewer';
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['browserhash'] = sha1($_SERVER['HTTP_USER_AGENT']);
    }

    public static function getLoginStatus()
    {
        $status = "";

        if (isset($_REQUEST['mode']))
        {
            if (strcmp($_REQUEST['mode'], 'doLogin') === 0)
            {
                if (isset($_REQUEST['password']) && (strcmp($_REQUEST['password'], GeneralSettings::LOGS_PASSWORD) === 0))
                {
                    self::login();
                }
                else
                {
                    $status = '<p class=\'warning_label\'>The specified password is incorrect!</p>';
                }
            }
            else if (strcmp($_REQUEST['mode'], 'doLogout') === 0)
            {
                self::logout();
                $status = '<p class=\'warning_label\'>Successfully logged out!</p>';
            }
        }

        return $status;
    }

}
