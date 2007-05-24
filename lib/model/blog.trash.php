<?php
/// Copyright (c) 2004-2007, Tatter & Company / Tatter & Friends.
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

//require 'common.correctTT.php';

function getTrashTrackbackWithPagingForOwner($owner, $category, $site, $ip, $search, $page, $count) {
	global $database;
	
	$postfix = '';
	$sql = "SELECT t.*, c.name categoryName FROM {$database['prefix']}Trackbacks t LEFT JOIN {$database['prefix']}Entries e ON t.owner = e.owner AND t.entry = e.id AND e.draft = 0 LEFT JOIN {$database['prefix']}Categories c ON t.owner = c.owner AND e.category = c.id WHERE t.owner = $owner AND t.isFiltered > 0";
	if ($category > 0) {
		$categories = DBQuery::queryColumn("SELECT id FROM {$database['prefix']}Categories WHERE owner = $owner AND parent = $category");
		array_push($categories, $category);
		$sql .= ' AND e.category IN (' . implode(', ', $categories) . ')';
		$postfix .= '&category=' . rawurlencode($category);
	} else
		$sql .= ' AND e.category >= 0';
	if (!empty($site)) {
		$sql .= ' AND t.site = \'' . mysql_tt_escape_string($site) . '\'';
		$postfix .= '&site=' . rawurlencode($site);
	}
	if (!empty($ip)) {
		$sql .= ' AND t.ip = \'' . mysql_tt_escape_string($ip) . '\'';
		$postfix .= '&ip=' . rawurlencode($ip);
	}
	if (!empty($search)) {
		$search = escapeMysqlSearchString($search);
		$sql .= " AND (t.site LIKE '%$search%' OR t.subject LIKE '%$search%' OR t.excerpt LIKE '%$search%')";
		$postfix .= '&search=' . rawurlencode($search);
	}
	$sql .= ' ORDER BY t.written DESC';
	list($trackbacks, $paging) =  fetchWithPaging($sql, $page, $count);
	if (strlen($postfix) > 0) {
		$paging['postfix'] .= $postfix . '&withSearch=on';
	}
	return array($trackbacks, $paging);
}


function getTrashCommentsWithPagingForOwner($owner, $category, $name, $ip, $search, $page, $count) {
	global $database;
	$sql = "SELECT c.*, e.title, c2.name parentName FROM {$database['prefix']}Comments c LEFT JOIN {$database['prefix']}Entries e ON c.owner = e.owner AND c.entry = e.id AND e.draft = 0 LEFT JOIN {$database['prefix']}Comments c2 ON c.parent = c2.id AND c.owner = c2.owner WHERE c.owner = $owner AND c.isFiltered > 0";

	$postfix = '';	
	if ($category > 0) {
		$categories = DBQuery::queryColumn("SELECT id FROM {$database['prefix']}Categories WHERE parent = $category");
		array_push($categories, $category);
		$sql .= ' AND e.category IN (' . implode(', ', $categories) . ')';
		$postfix .= '&category=' . rawurlencode($category);
	} else
		$sql .= ' AND e.category >= 0';
	if (!empty($name)) {
		$sql .= ' AND c.name = \'' . mysql_tt_escape_string($name) . '\'';
		$postfix .= '&name=' . rawurlencode($name);
	}
	if (!empty($ip)) {
		$sql .= ' AND t.ip = \'' . mysql_tt_escape_string($ip) . '\'';
		$postfix .= '&ip=' . rawurlencode($ip);
	}
	if (!empty($search)) {
		$search = escapeMysqlSearchString($search);
		$sql .= " AND (c.name LIKE '%$search%' OR c.homepage LIKE '%$search%' OR c.comment LIKE '%$search%')";
		$postfix .= '&search=' . rawurlencode($search);
	}
	$sql .= ' ORDER BY c.written DESC';
	list($comments, $paging) =  fetchWithPaging($sql, $page, $count);
	if (strlen($postfix) > 0) {
		$paging['postfix'] .= $postfix . '&withSearch=on';
	}
	return array($comments, $paging);
}

function getTrackbackTrash($entry) {
	global $database, $owner;
	$trackbacks = array();
	$result = DBQuery::query("select * from {$database['prefix']}Trackbacks where owner = $owner AND entry = $entry order by written");
	while ($trackback = mysql_fetch_array($result))
		array_push($trackbacks, $trackback);
	return $trackbacks;
}

function getRecentTrackbackTrash($owner) {
	global $database;
	global $skinSetting;
	$trackbacks = array();
	$sql = doesHaveOwnership() ? "SELECT * FROM {$database['prefix']}Trackbacks WHERE owner = $owner ORDER BY written DESC LIMIT {$skinSetting['trackbacksOnRecent']}" : "SELECT t.* FROM {$database['prefix']}Trackbacks t, {$database['prefix']}Entries e WHERE t.owner = $owner AND t.owner = e.owner AND t.entry = e.id AND e.draft = 0 AND e.visibility >= 2 ORDER BY t.written DESC LIMIT {$skinSetting['trackbacksOnRecent']}";
	if ($result = DBQuery::query($sql)) {
		while ($trackback = mysql_fetch_array($result))
			array_push($trackbacks, $trackback);
	}
	return $trackbacks;
}

function deleteTrackbackTrash($owner, $id) {
	global $database;
	$entry = DBQuery::queryCell("SELECT entry FROM {$database['prefix']}Trackbacks WHERE owner = $owner AND id = $id");
	if ($entry === null)
		return false;
	if (!DBQuery::execute("DELETE FROM {$database['prefix']}Trackbacks WHERE owner = $owner AND id = $id"))
		return false;
	if (updateTrackbacksOfEntry($owner, $entry))
		return $entry;
	return false;
}

function restoreTrackbackTrash($owner, $id) {
   	global $database;
	$entry = DBQuery::queryCell("SELECT entry FROM {$database['prefix']}Trackbacks WHERE owner = $owner AND id = $id");
	if ($entry === null)
		return false;
	if (!DBQuery::execute("UPDATE {$database['prefix']}Trackbacks SET isFiltered = 0 WHERE owner = $owner AND id = $id"))
		return false;
	if (updateTrackbacksOfEntry($owner, $entry))
		return $entry;
	return false;
}

function trashVan() {
   	global $database;
	requireComponent('Eolin.PHP.Core');
	if(Timestamp::getUNIXtime() - getUserSetting('lastTrashSweep',0) > 86400) {
		DBQuery::execute("DELETE FROM {$database['prefix']}Comments where isFiltered < UNIX_TIMESTAMP() - 1296000 AND isFiltered > 0");
		DBQuery::execute("DELETE FROM {$database['prefix']}Trackbacks where isFiltered < UNIX_TIMESTAMP() - 1296000 AND isFiltered > 0");
		setUserSetting('lastTrashSweep',Timestamp::getUNIXtime());
	}
}

function emptyTrash($comment = true)
{
   	global $database;
	if ($comment == true) {
		DBQuery::execute("DELETE FROM {$database['prefix']}Comments where isFiltered > 0");
	} else {
		DBQuery::execute("DELETE FROM {$database['prefix']}Trackbacks where isFiltered > 0");
	}
}

?>
