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
header("Content-Type: text/plain");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

function logAndDie($log, $message, $code)
{
	http_response_code($code);
	$log->logFatal($message);
	die($message);
}

require_once 'KLogger.php';

$log = new KLogger('.', KLogger::INFO);

$log->logDebug('Opened connection from ' . $_SERVER['REMOTE_ADDR']);

$password = '';
$remoteaddr = '';

if ($_REQUEST['password'] != $password || $_SERVER['REMOTE_ADDR'] != $remoteaddr)
{
	logAndDie($log, 'Bad Authentication: ' . $_SERVER['REMOTE_ADDR'], 403);
}

$log->logDebug('Authenticated.');

require_once('mysql_settings.php');
$mysqli = new mysqli(mysqlSettings::HOSTNAME, mysqlSettings::USERNAME, mysqlSettings::PASSWORD, mysqlSettings::DATABASE);
if ($mysqli->connect_error)
{
	logAndDie($log, 'MySQL Connection Error', 500);
}

$log->logDebug('Connected to MySQL - Request mode: ' . $_REQUEST['mode']);

if ($_REQUEST['mode'] === 'update')
{
	$query = sprintf("UPDATE `logs_users` SET `ip` = '%s' WHERE `name` = '%s'",
		$mysqli->real_escape_string($_REQUEST['ip']),
		strtolower($mysqli->real_escape_string($_REQUEST['name']))
	);
	$mysqli->query($query) or logAndDie($log, 'MySQL Query Error: ' . $query, 500);
	
	$log->logDebug('Query: "' . $query . '" - Affected Rows: ' . $mysqli->affected_rows);

	if ($mysqli->affected_rows <= 0)
	{
		$query = sprintf("SELECT * FROM `logs_users` WHERE `name` = '%s'",
			strtolower($mysqli->real_escape_string($_REQUEST['name']))
		);
		$result = $mysqli->query($query) or logAndDie($log, 'MySQL Query Error: ' . $query, 500);
		
		$log->logDebug('Query: "' . $query . '" - # Rows: ' . $result->num_rows);
		
		if ($result->num_rows <= 0)
		{
			$query = sprintf("INSERT INTO `logs_users` (`name`, `ip`) VALUES ('%s', '%s')",
				strtolower($mysqli->real_escape_string($_REQUEST['name'])),
				$mysqli->real_escape_string($_REQUEST['ip'])
			);
			$mysqli->query($query) or logAndDie($log, 'MySQL Query Error: ' . $query, 500);

			$log->logDebug('Query: "' . $query . '" - Affected Rows: ' . $mysqli->affected_rows);
		}
	}
}
else if ($_REQUEST['mode'] === 'delete')
{
	$query = sprintf("DELETE FROM `logs_users` WHERE `name` = '%s' OR `ip` = '%s'",
		strtolower($mysqli->real_escape_string($_REQUEST['name'])),
		$mysqli->real_escape_string($_REQUEST['ip'])
	);
	$mysqli->query($query) or logAndDie($log, 'MySQL Query Error: ' . $query, 500);
	
	$log->logDebug('Query: "' . $query . '" - Affected Rows: ' . $mysqli->affected_rows);
}

$mysqli->close();

$log->logDebug('MySQL Closed. Finished.');
?>
