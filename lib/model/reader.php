<?

function getReaderSetting($owner) {
	global $database;
	return fetchQueryRow("SELECT * FROM {$database['prefix']}FeedSettings WHERE owner = $owner");
}

function setReaderSetting($owner, $setting) {
	global $database;
	$sql = "UPDATE {$database['prefix']}FeedSettings SET ";
	if (getUserId() == 1) {
		if (isset($setting['updateCycle']))
			mysql_query("UPDATE {$database['prefix']}FeedSettings SET updateCycle = {$setting['updateCycle']}");
		if (isset($setting['feedLife']))
			mysql_query("UPDATE {$database['prefix']}FeedSettings SET feedLife = {$setting['feedLife']}");
	}
	if (!empty($setting['loadImage']))
		$sql .= "loadImage = {$setting['loadImage']}, ";
	if (!empty($setting['allowScript']))
		$sql .= "allowScript = {$setting['allowScript']}, ";
	if (!empty($setting['newWindow']))
		$sql .= "newWindow = {$setting['newWindow']}, ";
	return executeQuery("$sql owner = owner WHERE owner = $owner");
}

function markAsUnread($owner, $id) {
	global $database;
	return executeQuery("DELETE FROM {$database['prefix']}FeedReads WHERE owner = $owner AND item = $id");
}

function markAsStar($owner, $id, $flag) {
	global $database;
	if (fetchQueryCell("SELECT i.id FROM {$database['prefix']}FeedGroups g, {$database['prefix']}FeedGroupRelations gr, {$database['prefix']}Feeds f, {$database['prefix']}FeedItems i WHERE g.owner = $owner AND gr.feed = f.id AND gr.groupId = g.id AND f.id = i.feed AND i.id = $id")) {
		if ($flag)
			mysql_query("REPLACE INTO {$database['prefix']}FeedStarred VALUES($owner, $id)");
		else
			mysql_query("DELETE FROM {$database['prefix']}FeedStarred WHERE owner = $owner AND item = $id");
		return true;
	} else
		return false;
}

function getFeedGroups($owner, $starredOnly = false, $searchKeyword = null) {
	global $database;
	if ($starredOnly !== false) {
		$condition = "AND s.item IS NOT NULL";
	} else if ($searchKeyword !== null) {
		$condition = "AND (i.title LIKE '%{$searchKeyword}%' OR i.description LIKE '%{$searchKeyword}%')";
	} else {
		$condition = '';
	}
	$sql = "SELECT
					g.id, g.title
				FROM
					{$database['prefix']}FeedGroups g
				LEFT JOIN
					{$database['prefix']}FeedGroupRelations r
				ON
					r.owner = $owner AND
					g.id = r.groupId
				LEFT JOIN
					{$database['prefix']}FeedItems i
				ON
					r.feed = i.feed
				LEFT JOIN
					{$database['prefix']}FeedStarred s
				ON
					s.owner = $owner AND
					i.id = s.item
				WHERE
					g.owner = $owner
					$condition
				GROUP BY g.id
				ORDER BY g.title";
	return fetchQueryAll($sql);
}

function getFeeds($owner, $group = 0, $starredOnly = false, $searchKeyword = null) {
	global $database;
	if ($starredOnly !== false) {
		$condition = "AND s.item IS NOT NULL";
	} else if ($searchKeyword !== null) {
		$condition = "AND (i.title LIKE '%{$searchKeyword}%' OR i.description LIKE '%{$searchKeyword}%')";
	} else {
		$condition = '';
	}
	$condition .= ($group == 0) ? '' : " AND g.id = $group";
	$sql = "SELECT
					f.id, f.xmlURL, f.blogURL, f.title, f.description, f.modified
				FROM
					{$database['prefix']}FeedGroups g,
					{$database['prefix']}FeedGroupRelations r,
					{$database['prefix']}Feeds f
				LEFT JOIN
					{$database['prefix']}FeedItems i
				ON
					f.id = i.feed
				LEFT JOIN
					{$database['prefix']}FeedStarred s
				ON
					s.owner = $owner AND
					i.id = s.item
				WHERE
					r.owner = $owner AND
					g.owner = $owner AND
					g.id = r.groupId AND
					r.feed = f.id
					$condition
				GROUP BY f.id
				ORDER BY f.title";
	return fetchQueryAll($sql);
}

function getFeedEntriesTotalCount($owner, $group = 0, $feed = 0, $unreadOnly = false, $starredOnly = false, $searchKeyword = null) {
	global $database;
	if ($starredOnly !== false) {
		$condition = 'AND s.item IS NOT NULL';
	} else if ($searchKeyword !== null) {
		$condition = "AND (i.title LIKE '%{$searchKeyword}%' OR i.description LIKE '%{$searchKeyword}%')";
	} else {
		$condition = '';
	}
	$condition .= ($group == 0) ? '' : " AND g.id = $group";
	$condition .= ($feed == 0) ? '' : " AND f.id = $feed";
	$condition .= ($unreadOnly == false) ? '' : ' AND rd.item IS NULL';
	$sql = "SELECT
					COUNT(i.id)
				FROM
					{$database['prefix']}FeedGroups g,
					{$database['prefix']}FeedGroupRelations r,
					{$database['prefix']}Feeds f
				LEFT JOIN
					{$database['prefix']}FeedItems i
				ON
					f.id = i.feed
				LEFT JOIN					
					{$database['prefix']}FeedStarred s
				ON
					s.owner = $owner AND
					i.id = s.item
				LEFT JOIN					
					{$database['prefix']}FeedReads rd
				ON
					rd.owner = $owner AND
					i.id = rd.item
				WHERE
					r.owner = $owner AND
					g.owner = $owner AND
					g.id = r.groupId AND
					r.feed = f.id
					$condition";
	return fetchQueryCell($sql);
}

function getFeedEntries($owner, $group = 0, $feed = 0, $unreadOnly = false, $starredOnly = false, $searchKeyword = null, $offset = 0) {
	global $database;
	if ($starredOnly !== false) {
		$condition = 'AND s.item IS NOT NULL';
	} else if ($searchKeyword !== null) {
		$condition = "AND (i.title LIKE '%{$searchKeyword}%' OR i.description LIKE '%{$searchKeyword}%')";
	} else {
		$condition = '';
	}
	$condition .= ($group == 0) ? '' : " AND g.id = $group";
	$condition .= ($feed == 0) ? '' : " AND f.id = $feed";
	$condition .= ($unreadOnly == false) ? '' : ' AND rd.item IS NULL';
	$sql = "SELECT
					s.item, i.id, i.title entry_title, i.enclosure, f.title blog_title, i.written, i.tags, i.author, rd.item wasread
				FROM
					{$database['prefix']}FeedGroups g,
					{$database['prefix']}FeedGroupRelations r,
					{$database['prefix']}Feeds f,
					{$database['prefix']}FeedItems i
				LEFT JOIN					
					{$database['prefix']}FeedStarred s
				ON
					s.owner = $owner AND
					i.id = s.item
				LEFT JOIN					
					{$database['prefix']}FeedReads rd
				ON
					rd.owner = $owner AND
					i.id = rd.item
				WHERE
					r.owner = $owner AND
					g.owner = $owner AND
					g.id = r.groupId AND
					r.feed = f.id AND
					f.id = i.feed
					$condition
				GROUP BY i.id
				ORDER BY i.written DESC, i.id DESC";
	$sql .= " LIMIT $offset, " . ($offset == 0 ? 100 : $offset);
	return fetchQueryAll($sql);
}

function getFeedEntry($owner, $group = 0, $feed = 0, $entry = 0, $unreadOnly = false, $starredOnly = false, $searchKeyword = null, $position = 'current', $markAsRead = 'read') {
	global $database;
	$setting = getReaderSetting($owner);
	if ($entry == 0 || $position != 'current') {
		if ($starredOnly !== false) {
			$condition = 'AND s.item IS NOT NULL';
		} else if ($searchKeyword !== null) {
			$condition = "AND (i.title LIKE '%{$searchKeyword}%' OR i.description LIKE '%{$searchKeyword}%')";
		} else {
			$condition = '';
		}
		$condition .= ($group == 0) ? '' : " AND g.id = $group";
		$condition .= ($feed == 0) ? '' : " AND f.id = $feed";
		$sql = "SELECT
						i.id, i.title entry_title, i.description, f.title blog_title, i.author, i.written, i.tags, i.permalink, rd.item wasread, f.language, enclosure
					FROM
						{$database['prefix']}FeedGroups g,
						{$database['prefix']}FeedGroupRelations r,
						{$database['prefix']}Feeds f,
						{$database['prefix']}FeedItems i
					LEFT JOIN					
						{$database['prefix']}FeedStarred s
					ON
						s.owner = $owner AND
						i.id = s.item
					LEFT JOIN					
						{$database['prefix']}FeedReads rd
					ON
						rd.owner = $owner AND
						i.id = rd.item
					WHERE
						r.owner = $owner AND
						g.owner = $owner AND
						g.id = r.groupId AND
						r.feed = f.id AND
						f.id = i.feed
						$condition
					GROUP BY i.id
					ORDER BY i.written DESC, i.id DESC";
		if ($position == 'current') {
			if ($row = fetchQueryRow("$sql LIMIT 1")) {
				$row['description'] = adjustRelativePathImage($row['description'], $row['permalink']);
				$row['description'] = filterJavaScript($row['description'], ($setting['allowScript'] == 1 ? false : true));
			}
			return $row;
		} else {
			$result = mysql_query($sql);
			$prevRow = null;
			while ($row = mysql_fetch_array($result)) {
				if ($row['id'] == $entry) {
					if ($position == 'before') {
						while ($row = mysql_fetch_array($result)) {
							if ($unreadOnly == false || !$row['wasread'])
								break;
						}
						if ($markAsRead == 'read')
							mysql_query("REPLACE INTO {$database['prefix']}FeedReads VALUES($owner, {$row['id']})");
						if ($row) {
							$row['description'] = adjustRelativePathImage($row['description'], $row['permalink']);
							$row['description'] = filterJavaScript($row['description'], ($setting['allowScript'] == 1 ? false : true));
						}
						return $row;
					} else if ($position == 'after') {
						if ($markAsRead == 'read')
							mysql_query("REPLACE INTO {$database['prefix']}FeedReads VALUES($owner, {$prevRow['id']})");
						if ($prevRow) {
							$prevRow['description'] = adjustRelativePathImage($prevRow['description'], $row['permalink']);
							$prevRow['description'] = filterJavaScript($prevRow['description'], ($setting['allowScript'] == 1 ? false : true));
						}
						return $prevRow;
					}
				}
				if ($unreadOnly == false || !$row['wasread'])
					$prevRow = $row;
			}
			return;
		}
	} else {
		mysql_query("REPLACE INTO {$database['prefix']}FeedReads VALUES($owner, $entry)");
		$sql = "SELECT
						i.id, i.title entry_title, i.description, f.title blog_title, i.author, i.written, i.tags, i.permalink, f.language, enclosure
					FROM
						{$database['prefix']}FeedGroups g,
						{$database['prefix']}FeedGroupRelations r,
						{$database['prefix']}Feeds f,
						{$database['prefix']}FeedItems i
					WHERE
						g.owner = $owner AND
						r.feed = f.id AND
						r.groupId = g.id AND
						i.id = $entry AND
						f.id = i.feed";
		if ($row = fetchQueryRow($sql)) {
			$row['description'] = adjustRelativePathImage($row['description'], $row['permalink']);
			$row['description'] = filterJavaScript($row['description'], ($setting['allowScript'] == 1 ? false : true));
		}
		return $row;
	}
}

function addFeedGroup($owner, $title) {
	global $database;
	$title = trim(mysql_escape_string($title));
	if (empty($title))
		return 1;
	if (fetchQueryCell("SELECT id FROM {$database['prefix']}FeedGroups WHERE owner = $owner AND title = '$title'") !== null) {
		return 2;
	}
	$id = fetchQueryCell("SELECT MAX(id) FROM {$database['prefix']}FeedGroups WHERE owner = $owner") + 1;
	mysql_query("INSERT INTO {$database['prefix']}FeedGroups VALUES($owner, $id, '$title')");
	if (mysql_affected_rows() != 1)
		return - 1;
	return 0;
}

function editFeedGroup($owner, $id, $title) {
	global $database;
	$title = trim(mysql_escape_string($title));
	if (empty($title))
		return 1;
	$prevTitle = fetchQueryCell("SELECT title FROM {$database['prefix']}FeedGroups WHERE owner = $owner AND id = $id");
	if ($prevTitle == $title)
		return 0;
	if ($prevTitle === null)
		return - 1;
	mysql_query("UPDATE {$database['prefix']}FeedGroups SET title = '$title' WHERE id = $id");
	if (mysql_affected_rows() != 1)
		return - 1;
	return 0;
}

function deleteFeedGroup($owner, $id) {
	global $database;
	if ($id == 0)
		return - 1;
	mysql_query("UPDATE {$database['prefix']}FeedGroupRelations SET groupId = 0 WHERE owner = $owner AND groupId = $id");
	mysql_query("DELETE FROM {$database['prefix']}FeedGroups WHERE id = $id");
	if (mysql_affected_rows() != 1)
		return 1;
	return 0;
}

function addFeed($owner, $group = 0, $url, $getEntireFeed = true, $htmlURL = '', $blogTitle = '', $blogDescription = '') {
	global $database;
	$blogTitle = str_trans_rev($blogTitle);
	$blogDescription = str_trans_rev($blogDescription);
	$url = mysql_escape_string($url);
	if (fetchQueryCell("SELECT id FROM {$database['prefix']}Feeds f, {$database['prefix']}FeedGroups g, {$database['prefix']}FeedGroupRelations r WHERE g.owner = $owner AND r.feed = f.id AND r.groupId = g.id AND f.xmlURL = '$url'")) {
		return 1;
	}
	if ($id = fetchQueryCell("SELECT id FROM {$database['prefix']}Feeds WHERE xmlURL = '$url'")) {
		mysql_query("INSERT INTO {$database['prefix']}FeedGroupRelations VALUES($owner, $id, $group)");
		return 0;
	}
	if ($getEntireFeed) {
		list($status, $feed, $xml) = getRemoteFeed($url);
		if ($status > 0)
			return $status;
		mysql_query("INSERT INTO {$database['prefix']}Feeds VALUES(null, '{$feed['xmlURL']}', '{$feed['blogURL']}', '{$feed['title']}', '{$feed['description']}', '{$feed['language']}', {$feed['modified']})");
		$id = mysql_insert_id();
		mysql_query("INSERT INTO {$database['prefix']}FeedGroupRelations VALUES($owner, $id, $group)");
		saveFeedItems($id, $xml);
	} else {
		$htmlURL = mysql_escape_string($htmlURL);
		$blogTitle = mysql_escape_string($blogTitle);
		$blogDescription = mysql_escape_string($blogDescription);
		mysql_query("INSERT INTO {$database['prefix']}Feeds VALUES(null, '$url', '$htmlURL', '$blogTitle', '$blogDescription', 'en-US', 0)");
		$id = mysql_insert_id();
		mysql_query("INSERT INTO {$database['prefix']}FeedGroupRelations VALUES($owner, $id, $group)");
	}
	return 0;
}

function getRemoteFeed($url) {
	global $service;
	$xml = fireEvent('GetRemoteFeed', null, $url);
	if (empty($xml)) {
		requireComponent('Eolin.PHP.HTTPRequest');
		$request = new HTTPRequest($url);
		if (!$request->send())
			return array(2, null, null);
		$xml = $request->responseText;
	}
	$feed = array('xmlURL' => $url);
	$xmls = new XMLStruct();
	if (!$xmls->open($xml, $service['encoding']))
		return array(3, null, null);
	if ($xmls->getAttribute('/rss', 'version')) {
		$feed['blogURL'] = mysql_escape_string(str_trans_rev($xmls->getValue('/rss/channel/link')));
		$feed['title'] = mysql_escape_string(str_trans_rev($xmls->getValue('/rss/channel/title')));
		$feed['description'] = mysql_escape_string(str_trans_rev($xmls->getValue('/rss/channel/description')));
		if ($xmls->getValue('/rss/channel/language')) {
			$feed['language'] = mysql_escape_string($xmls->getValue('/rss/channel/language'));
		} else if ($xmls->getValue('/rss/channel/dc:language')) {
			$feed['language'] = mysql_escape_string($xmls->getValue('/rss/channel/dc:language'));
		} else
			$feed['language'] = 'en-US';
		$feed['modified'] = time();
	} else if ($xmls->getAttribute('/feed', 'version')) {
		$feed['blogURL'] = mysql_escape_string(str_trans_rev($xmls->getAttribute('/feed/link', 'href')));
		$feed['title'] = mysql_escape_string(str_trans_rev($xmls->getValue('/feed/title')));
		$feed['description'] = mysql_escape_string(str_trans_rev($xmls->getValue('/feed/tagline')));
		$feed['language'] = mysql_escape_string(str_trans_rev($xmls->getAttribute('/feed', 'xml:lang')));
		$feed['modified'] = time();
	} else if ($xmls->getAttribute('/rdf:RDF', 'xmlns')) {
		$feed['blogURL'] = mysql_escape_string(str_trans_rev($xmls->getAttribute('/rdf:RDF/channel/link', 'href')));
		$feed['title'] = mysql_escape_string(str_trans_rev($xmls->getValue('/rdf:RDF/channel/title')));
		$feed['description'] = mysql_escape_string(str_trans_rev($xmls->getValue('/rdf:RDF/channel/description')));
		if (!$feed['language'] = mysql_escape_string(str_trans_rev($xmls->getValue('/rdf:RDF/channel/dc:language'))))
			$feed['language'] = mysql_escape_string(str_trans_rev($xmls->getAttribute('/rdf:RDF', 'xml:lang')));
		$feed['modified'] = time();
	} else
		return array(3, null, null);
	return array(0, $feed, $xml);
}

function saveFeedItems($feedId, $xml) {
	global $database, $service;
	$xmls = new XMLStruct();
	if (!$xmls->open($xml, $service['encoding']))
		return false;
	if ($xmls->getAttribute('/rss', 'version')) {
		for ($i = 0; $link = $xmls->getValue("/rss/channel/item[$i]/link"); $i++) {
			$item = array('permalink' => mysql_escape_string($link));
			if (!$item['author'] = mysql_escape_string(str_trans_rev($xmls->getValue("/rss/channel/item[$i]/author"))))
				$item['author'] = mysql_escape_string(str_trans_rev($xmls->getValue("/rss/channel/item[$i]/dc:creator")));
			$item['title'] = mysql_escape_string(str_trans_rev($xmls->getValue("/rss/channel/item[$i]/title")));
			if (!$item['description'] = mysql_escape_string(str_trans_rev($xmls->getValue("/rss/channel/item[$i]/content:encoded"))))
				$item['description'] = mysql_escape_string(str_trans_rev($xmls->getValue("/rss/channel/item[$i]/description")));
			$item['tags'] = array();
			for ($j = 0; $tag = $xmls->getValue("/rss/channel/item[$i]/category[$j]"); $j++) {
				array_push($item['tags'], mysql_escape_string(str_trans_rev($tag)));
			}
			for ($j = 0; $tag = $xmls->getValue("/rss/channel/item[$i]/subject[$j]"); $j++) {
				array_push($item['tags'], mysql_escape_string(str_trans_rev($tag)));
			}
			$item['enclosures'] = array();
			for ($j = 0; $url = $xmls->getAttribute("/rss/channel/item[$i]/enclosure[$j]", 'url'); $j++) {
				array_push($item['enclosures'], mysql_escape_string(str_trans_rev($url)));
			}
			if ($xmls->getValue("/rss/channel/item[$i]/pubDate")) {
				$item['written'] = parseDate($xmls->getValue("/rss/channel/item[$i]/pubDate"));
			} else if ($xmls->getValue("/rss/channel/item[$i]/dc:date")) {
				$item['written'] = parseDate($xmls->getValue("/rss/channel/item[$i]/dc:date"));
			} else
				$item['written'] = 0;
			saveFeedItem($feedId, $item);
		}
	} else if ($xmls->getAttribute('/feed', 'version')) {
		for ($i = 0; $link = $xmls->getValue("/feed/entry[$i]/id"); $i++) {
			$item = array('permalink' => mysql_escape_string($link));
			$item['author'] = mysql_escape_string(str_trans_rev($xmls->getValue("/feed/entry[$i]/author/name")));
			$item['title'] = mysql_escape_string(str_trans_rev($xmls->getValue("/feed/entry[$i]/title")));
			$item['description'] = mysql_escape_string(str_trans_rev($xmls->getValue("/feed/entry[$i]/content")));
			$item['tags'] = array();
			for ($j = 0; $tag = $xmls->getValue("/feed/entry[$i]/dc:subject[$j]"); $j++) {
				array_push($item['tags'], mysql_escape_string(str_trans_rev($tag)));
			}
			$item['enclosures'] = array();
			for ($j = 0; $url = $xmls->getAttribute("/feed/entry[$i]/enclosure[$j]", 'url'); $j++) {
				array_push($item['enclosures'], mysql_escape_string(str_trans_rev($url)));
			}
			$item['written'] = parseDate($xmls->getValue("/feed/entry[$i]/issued"));
			saveFeedItem($feedId, $item);
		}
	} else if ($xmls->getAttribute('/rdf:RDF', 'xmlns')) {
		for ($i = 0; $link = $xmls->getValue("/rdf:RDF/item[$i]/link"); $i++) {
			$item = array('permalink' => mysql_escape_string(str_trans_rev($link)));
			$item['author'] = mysql_escape_string(str_trans_rev($xmls->getValue("/rdf:RDF/item[$i]/dc:creator")));
			$item['title'] = mysql_escape_string(str_trans_rev($xmls->getValue("/rdf:RDF/item[$i]/title")));
			if (!$item['description'] = mysql_escape_string(str_trans_rev($xmls->getValue("/rdf:RDF/item[$i]/content:encoded"))))
				$item['description'] = mysql_escape_string(str_trans_rev($xmls->getValue("/rdf:RDF/item[$i]/description")));
			$item['tags'] = array();
			$item['enclosures'] = array();
			$item['written'] = parseDate($xmls->getValue("/rdf:RDF/item[$i]/dc:date"));
			saveFeedItem($feedId, $item);
		}
	} else
		return false;
	return true;
}

function saveFeedItem($feedId, $item) {
	global $database;
	if ($item['written'] > time())
		return false;
	$tagString = implode(', ', $item['tags']);
	$enclosureString = implode('|', $item['enclosures']);
	if ($id = fetchQueryCell("SELECT id FROM {$database['prefix']}FeedItems WHERE permalink='{$item['permalink']}'")) {
		mysql_query("UPDATE {$database['prefix']}FeedItems SET author = '{$item['author']}', title = '{$item['title']}', description = '{$item['description']}', tags = '$tagString', enclosure = '$enclosureString', written = {$item['written']} WHERE id = $id");
		if (mysql_affected_rows() > 0)
			mysql_query("DELETE FROM {$database['prefix']}FeedReads WHERE item = $id");
	} else
		mysql_query("INSERT INTO {$database['prefix']}FeedItems VALUES(null, $feedId, '{$item['author']}', '{$item['permalink']}', '{$item['title']}', '{$item['description']}', '$tagString', '$enclosureString', {$item['written']})");
	return true;
}

function editFeed($owner, $feedId, $oldGroupId, $newGroupId, $url) {
	global $database;
	mysql_query("UPDATE {$database['prefix']}FeedGroupRelations SET groupId = $newGroupId WHERE owner = $owner AND feed = $feedId AND groupId = $oldGroupId");
	return 0;
}

function deleteFeed($owner, $feedId) {
	global $database;
	mysql_query("DELETE FROM {$database['prefix']}FeedGroupRelations WHERE owner = $owner AND feed = $feedId");
	if (mysql_affected_rows() != 1)
		return - 1;
	if (fetchQueryCell("SELECT COUNT(*) FROM {$database['prefix']}FeedGroupRelations WHERE owner = $owner AND feed = $feedId") == 0) {
		foreach (fetchQueryAll("SELECT item FROM {$database['prefix']}FeedStarred s, {$database['prefix']}FeedItems i WHERE s.item = i.id AND s.owner = $owner AND i.feed = $feedId") as $row) {
			mysql_query("DELETE FROM {$database['prefix']}FeedStarred WHERE owner = $owner AND item = {$row['item']}");
		}
		foreach (fetchQueryAll("SELECT item FROM {$database['prefix']}FeedReads r, {$database['prefix']}FeedItems i WHERE r.item = i.id AND r.owner = $owner AND i.feed = $feedId") as $row) {
			mysql_query("DELETE FROM {$database['prefix']}FeedReads WHERE owner = $owner AND item = {$row['item']}");
		}
		mysql_query("DELETE FROM {$database['prefix']}FeedItems WHERE feed = $feedId");
		mysql_query("DELETE FROM {$database['prefix']}Feeds WHERE id = $feedId");
	}
	return 0;
}

function updateRandomFeed() {
	global $database;
	$updateCycle = fetchQueryCell("SELECT updateCycle FROM {$database['prefix']}FeedSettings");
	if ($feed = fetchQueryRow("SELECT * FROM {$database['prefix']}Feeds WHERE modified < " . (time() - ($updateCycle * 60)) . " ORDER BY RAND() LIMIT 1")) {
		return array(updateFeed($feed), $feed['xmlURL']);
	}
	return array(1, '');
}

function updateFeed($feedRow) {
	global $database;
	list($status, $feed, $xml) = getRemoteFeed($feedRow['xmlURL']);
	if ($status > 0) {
		executeQuery("UPDATE {$database['prefix']}Feeds SET modified = 0 WHERE xmlURL = '{$feedRow['xmlURL']}'");
		return $status;
	} else {
		executeQuery("UPDATE {$database['prefix']}Feeds SET blogURL = '{$feed['blogURL']}', title = '{$feed['title']}', description = '{$feed['description']}', language = '{$feed['language']}', modified = " . time() . " WHERE xmlURL = '{$feedRow['xmlURL']}'");
		return saveFeedItems($feedRow['id'], $xml) ? 0 : 1;
	}
}

function parseDate($str) {
	if (empty($str))
		return 0;
	$gmt = (substr($str, strpos($str, "GMT")) == "GMT") ? 9 : 0;
	$str = str_replace("년 ", "-", $str);
	$str = str_replace("월 ", "-", $str);
	$str = str_replace("일 ", "", $str);
	$str = str_replace("GMT", "", $str);
	$str = str_replace("KST", "+0900", $str);
	if (strpos($str, "T")) {
		list($date, $time) = explode("T", $str);
		list($y, $m, $d) = explode("-", $date);
		list($time) = explode("+", $time);
		@list($h, $i, $s) = explode(":", $time);
	} else if (strpos($str, ":") && strpos($str, "-")) {
		list($str) = explode(".", $str);
		list($date, $time) = explode(" ", $str);
		list($y, $m, $d) = explode("-", $date);
		if ($d > 1900) {
			$t = $y;
			$y = $d;
			$d = $m;
			$m = $t;
		}
		@list($h, $i, $s) = explode(":", $time);
	} else if (strpos($str, ",") && strpos($str, ":")) {
		list($temp, $str) = explode(",", $str);
		$str = trim(str_month_check($str));
		list($d, $m, $y, $time) = explode(" ", $str);
		list($h, $i, $s) = explode(":", $time);
	} else {
		return time();
	}
	if (!$h)
		$h = "00";
	if (!$i)
		$i = "00";
	if (!$s)
		$s = "00";
	$h += $gmt;
	$time = mktime($h, $i, $s, $m, $d, $y);
	if ($time < 315500400)
		return time();
	else
		return $time;
}

function str_month_check($str) {
	$str = str_replace("Jan", "01", $str);
	$str = str_replace("Feb", "02", $str);
	$str = str_replace("Mar", "03", $str);
	$str = str_replace("Apr", "04", $str);
	$str = str_replace("May", "05", $str);
	$str = str_replace("Jun", "06", $str);
	$str = str_replace("Jul", "07", $str);
	$str = str_replace("Aug", "08", $str);
	$str = str_replace("Sep", "09", $str);
	$str = str_replace("Oct", "10", $str);
	$str = str_replace("Nov", "11", $str);
	return str_replace("Dec", "12", $str);
}

function importOPMLFromURL($owner, $url) {
	global $database, $service;
	requireComponent('Eolin.PHP.HTTPRequest');
	$request = new HTTPRequest($url);
	if (!$request->send())
		return array('error' => 1);
	$xml = $request->responseText;
	$xmls = new XMLStruct();
	if (!$xmls->open($xml, $service['encoding']))
		return array('error' => 2);
	if ($xmls->getAttribute('/opml', 'version')) {
		$result = array(0, 0);
		for ($i = 0; $link = $xmls->getAttribute("/opml/body/outline[$i]", 'xmlUrl'); $i++) {
			$result[addFeed($owner, $group = 0, $link, false, $xmls->getAttribute("/opml/body/outline[$i]", 'htmlUrl'), $xmls->getAttribute("/opml/body/outline[$i]", 'title'), $xmls->getAttribute("/opml/body/outline[$i]", 'description'))] += 1;
		}
	} else
		return array('error' => 3);
	return array('error' => 0, 'total' => array_sum($result), 'success' => $result[0]);
}

function importOPMLFromFile($owner, $xml) {
	global $database, $service;
	$xmls = new XMLStruct();
	if (!$xmls->open($xml, $service['encoding']))
		return array(1, null);
	if ($xmls->getAttribute('/opml', 'version')) {
		$result = array(0, 0);
		for ($i = 0; $xmls->getAttribute("/opml/body/outline[$i]", 'title'); $i++) {
			$result[addFeed($owner, $group = 0, $xmls->getAttribute("/opml/body/outline[$i]", 'xmlUrl'), false, $xmls->getAttribute("/opml/body/outline[$i]", 'htmlUrl'), $xmls->getAttribute("/opml/body/outline[$i]", 'title'), $xmls->getAttribute("/opml/body/outline[$i]", 'description'))] += 1;
			for ($j = 0; $xmls->getAttribute("/opml/body/outline[$i]/outline[$j]", 'title'); $j++)
				$result[addFeed($owner, $group = 0, $xmls->getAttribute("/opml/body/outline[$i]/outline[$j]", 'xmlUrl'), false, $xmls->getAttribute("/opml/body/outline[$i]", 'htmlUrl'), $xmls->getAttribute("/opml/body/outline[$i]", 'title'), $xmls->getAttribute("/opml/body/outline[$i]", 'description'))] += 1;
		}
	} else
		return array(2, null);
	return array(0, array('total' => array_sum($result), 'success' => $result[0]));
}

function adjustRelativePathImage($str, $permalink) {
	$link = parse_url($permalink);
	if (empty($link['scheme']))
		return $str;
	$port = (empty($link['port']) || $link['port'] == 80) ? '' : ":{$link['port']}";
	$urls = array();
	preg_match_all('/<img[^>]+?src=("|\')?(.*?)("|\')/si', $str, $matches);
	foreach ($matches[2] as $src)
		array_push($urls, $src);
	foreach ($urls as $url) {
		if ($url && !preg_match('/^(http:|ftp:)/i', $url)) {
			$newSrc = ($url{0} == '/') ? $url : "/$url";
			$str = str_replace($url, "{$link['scheme']}://{$link['host']}$port$newSrc", $str);
		}
	}
	return $str;
}
?>