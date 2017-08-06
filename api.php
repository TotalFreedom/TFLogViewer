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

class Api
{

    public static function dumpUsers()
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Thu, 01 Jan 1970 00:00:01 GMT');
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        try
        {
            if (!isset($_REQUEST['password']) || strcmp($_REQUEST['password'], GeneralSettings::API_PASSWORD) !== 0)
            {
                throw new Exception('INVALID_PASSWORD');
            }

            $mysqli = Common::getSQLConnection();

            $output = array();

            $query = sprintf('SELECT * FROM logs_users');
            $result = $mysqli->query($query);
            if (!$result)
            {
                throw new Exception('QUERY_ERROR');
            }
            while ($row = $result->fetch_array(MYSQLI_ASSOC))
            {
                $output['users'][$row['name']] = array('ip' => $row['ip']);
            }

            $output['status'] = 'OK';
            echo json_encode($output, JSON_PRETTY_PRINT);
        }
        catch (Exception $ex)
        {
            echo json_encode(array('status', $ex->getMessage()), JSON_PRETTY_PRINT);
        }
    }

}

Api::dumpUsers();
