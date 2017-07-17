<?php
//  This file is part of MostActive sourcemod plugin.
//
//  Copyright (C) 2017 MostActive Dev Team 
//  https://github.com/Franc1sco/MostActive/graphs/contributors
//
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, per version 3 of the License.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program. If not, see <http://www.gnu.org/licenses/>.
//
//  This file is based off work(s) covered by the following copyright(s):
//
//   Stamm 2.28 Final
//   Copyright (C) 2012-2014 David <popoklopsi> Ordnung
//   Licensed under GNU GPL version 3, or later.
//   Page: https://github.com/popoklopsi/Stamm-Webinterface &
//         https://forums.alliedmods.net/showthread.php?p=1338942
//
// *************************************************************************


// Calculate communityid
function calculateSteamid64($steamID)
{
	// Valid?
	if (preg_match('/^STEAM_[0-9]:[0-9]:[0-9]{1,}/i', $steamID))
	{
		// Convert
		$steamID = str_replace("_", ":", $steamID);
		list($part_one, $part_two, $part_three, $part_four) = explode(':', $steamID);
		
		$result = bcadd('76561197960265728', $part_four * 2);
		
		return bcadd($result, $part_three);
	}
	else 
	{
		return false;
	}
}


// Steamid out of communityid
function calculateSteamid($vars)
{
	$commid = $vars;
	
	if (substr($commid, -1) % 2 == 0) 
	{
		$server = 0; 
	}
	else
	{ 
		$server = 1;
	}
	
	$auth = bcsub($commid, '76561197960265728');
	
	if (bccomp($auth, '0') != 1)
	{
		return "";
	}
	
	$auth = bcsub($auth, $server);
	$auth = bcdiv($auth, 2);
	
	return 'STEAM_0:'.$server.':'.$auth;
}
// Steamid out of communityid
function calculateSteamid2($vars)
{
	$commid = $vars;
	
	if (substr($commid, -1) % 2 == 0) 
	{
		$server = 0; 
	}
	else
	{ 
		$server = 1;
	}
	
	$auth = bcsub($commid, '76561197960265728');
	
	if (bccomp($auth, '0') != 1)
	{
		return "";
	}
	
	$auth = bcsub($auth, $server);
	$auth = bcdiv($auth, 2);
	
	return 'STEAM_1:'.$server.':'.$auth;
}


function secondsToTime($seconds, $time_format) {
	date_default_timezone_set('America/Los_Angeles');

	$dtF = new DateTime("@0");
	$dtT = new DateTime("@$seconds");
	$diff = $dtF->diff($dtT);

	$out = array();
	if ($diff->format('%a') > 0) $out[] = $diff->format('%a<font size="1">').' '.$time_format[0];
	if ($diff->format('%h') > 0) $out[] = $diff->format('%h<font size="1">').' '.$time_format[1];
	if ($diff->format('%i') > 0) $out[] = $diff->format('%i<font size="1">').' '.$time_format[2];
	if ($diff->format('%s') > 0 || sizeof($out) == 0) {
		$out[] = $diff->format('%s<font size="1">').' '.$time_format[3];
	}
	return implode(', </font>', $out);
}


class SQL
{
	// link identifier
	private $db = NULL;
	
	// Constructor
	function __construct($host, $user, $pass, $stamm_dbName)
	{
		// Connect to MySQL
		$this->db = mysql_connect($host, $user, $pass) or die("Couldn't make connection.");
		mysql_select_db($stamm_dbName, $this->db) or die("Couldn't select database");
	}
	
	// Escapes a string
	public function escape($string)
	{
		return mysql_escape_string($string);
	}
	
	// Do a query
	public function query($query)
	{
		$result = mysql_query($query, $this->db) or die(mysql_error($this->db));
		
		return $result;
	}
	
	// Check if we found Data
	public function foundData($result)
	{
		return mysql_num_rows($result);
	}
	
	// Get Rows
	public function getRows($result)
	{
		return mysql_fetch_row($result);
	}
	
	// Get Array
	public function getArray($result)
	{
		return mysql_fetch_assoc($result);
	}
}