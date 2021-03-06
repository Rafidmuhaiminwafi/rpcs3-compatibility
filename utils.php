<?php
/*
    RPCS3.net Compatibility List (https://github.com/AniLeo/rpcs3-compatibility)
    Copyright (C) 2017 AniLeo
    https://github.com/AniLeo or ani-leo@outlook.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

if(!@include_once("config.php")) throw new Exception("Compat: config.php is missing. Failed to include config.php");
if(!@include_once("functions.php")) throw new Exception("Compat: functions.php is missing. Failed to include functions.php");


/* Utilities for the main website */

function getLatestWindowsBuild() {
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	$query = mysqli_query($db, "SELECT * FROM builds_windows WHERE type = 'branch' ORDER BY merge_datetime DESC LIMIT 1;");
	$row = mysqli_fetch_object($query);

	mysqli_close($db);
	
	$win = array($row->appveyor, date_format(date_create($row->merge_datetime), "Y-m-d"));
	
	$commit = substr($row->commit, 0, 7);
	$ver = "v{$win[0]} ({$commit}) Alpha [{$win[1]}]";
	$url = "https://ci.appveyor.com/api/buildjobs/{$row->buildjob}/artifacts/{$row->filename}";
	
	$size = ((int)$row->size)/1024/1024;
	$size = round($size, 1);
	
	// 0 - URL, 1 - Version Name, 2 - Author, 3 - PR, 4 - Checksum, 5 - File Size (MB)
	if (!is_null($row->checksum)) {
		return array($url, $ver, $row->author, $row->pr, $row->checksum, $size);
	}
	return array($url, $ver, $row->author, $row->pr, 'None', $size);
}


/* Note: Linux builds scripts aren't open-sourced */
function getLatestLinuxBuild() {
	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');
	
	$query = mysqli_query($db, "SELECT * FROM builds_linux ORDER BY datetime DESC LIMIT 1;");
	$row = mysqli_fetch_object($query);

	mysqli_close($db);
	
	$date = date_format(date_create($row->datetime), "Y-m-d");
	$name = $row->buildname;
	$url = "https://rpcs3.net/cdn/builds/{$name}"; // Direct link to AppImage
	
	// 0 - URL, 1 - Filename, 2 - Date
	return array($url, $name, $date);
}


function cacheRoadmap() {
	global $c_github;
	
	$content = file_get_contents("{$c_github}/wiki/Roadmap");

	if ($content) { 
		$start = "<div id=\"wiki-body\" class=\"wiki-body gollum-markdown-content instapaper_body\">";
		$end = "</div>";

		$roadmap = explode($end, explode($start, $content)[1])[0]; 
		
		$path = realpath(__DIR__)."/../../cache/";
		$file = fopen($path."roadmap_cached.php", "w");
		fwrite($file, "<!-- This file is automatically generated every 15 minutes -->\n{$roadmap}");
		fclose($file);
	}
}
