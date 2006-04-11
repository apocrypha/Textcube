<?
class SkinSetting {
	function SkinSetting() {
		$this->reset();
	}

	function reset() {
		$this->error =
		$this->skin =
		$this->entriesOnRecent =
		$this->commentsOnRecent =
		$this->trackbacksOnRecent =
		$this->commentsOnGuestbook =
		$this->tagsOnTagbox =
		$this->alignOnTagbox =
		$this->expandComment =
		$this->expandTrackback =
		$this->recentNoticeLength =
		$this->recentEntryLength =
		$this->recentTrackbackLength =
		$this->linkLength =
		$this->showListOnCategory =
		$this->showListOnArchive =
		$this->tree =
		$this->colorOnTree =
		$this->bgColorOnTree =
		$this->activeColorOnTree =
		$this->activeBgColorOnTree =
		$this->labelLengthOnTree =
		$this->showValueOnTree =
			null;
	}
	
	function load($fields = '*') {
		global $database, $owner;
		$this->reset();
		if ($result = mysql_query("SELECT $fields FROM {$database['prefix']}SkinSettings WHERE owner = $owner")) {
			if ($row = mysql_fetch_assoc($result)) {
				foreach ($row as $name => $value) {
					if ($name == 'owner')
						continue;
					switch ($name) {
						case 'tagboxAlign':
							$name = 'alignOnTagbox';
							break;
					}
					$this->$name = $value;
				}
				mysql_free_result($result);
				return true;
			}
			mysql_free_result($result);
		}
		return false;
	}
	
	function save() {
		global $database, $owner;
		
		$query = new TableQuery($database['prefix'] . 'SkinSettings');
		$query->setQualifier('owner', $owner);
		if (isset($this->skin)) {
			if (!Validator::path($this->skin) || !file_exists(ROOT . '/skin/' . $this->skin))
				return $this->_error('skin');
			$query->setAttribute('skin', $this->skin, false);
		}
		if (isset($this->entriesOnRecent)) {
			if (!Validator::number($this->entriesOnRecent, 1))
				return $this->_error('entriesOnRecent');
			$query->setAttribute('entriesOnRecent', $this->entriesOnRecent);
		}
		if (isset($this->commentsOnRecent)) {
			if (!Validator::number($this->commentsOnRecent, 1))
				return $this->_error('commentsOnRecent');
			$query->setAttribute('commentsOnRecent', $this->commentsOnRecent);
		}
		if (isset($this->trackbacksOnRecent)) {
			if (!Validator::number($this->trackbacksOnRecent, 1))
				return $this->_error('trackbacksOnRecent');
			$query->setAttribute('trackbacksOnRecent', $this->trackbacksOnRecent);
		}
		if (isset($this->commentsOnGuestbook)) {
			if (!Validator::number($this->commentsOnGuestbook, 1))
				return $this->_error('commentsOnGuestbook');
			$query->setAttribute('commentsOnGuestbook', $this->commentsOnGuestbook);
		}
		if (isset($this->tagsOnTagbox)) {
			if (!Validator::number($this->tagsOnTagbox, 1))
				return $this->_error('tagsOnTagbox');
			$query->setAttribute('tagsOnTagbox', $this->tagsOnTagbox);
		}
		if (isset($this->alignOnTagbox)) {
			if (!Validator::number($this->alignOnTagbox, 1, 3))
				return $this->_error('alignOnTagbox');
			$query->setAttribute('tagboxAlign', $this->alignOnTagbox);
		}
		if (isset($this->expandComment))
			$query->setAttribute('expandComment', Validator::getBit($this->expandComment));
		if (isset($this->expandTrackback))
			$query->setAttribute('expandTrackback', Validator::getBit($this->expandTrackback));
		if (isset($this->recentNoticeLength)) {
			if (!Validator::number($this->recentNoticeLength, 0))
				return $this->_error('recentNoticeLength');
			$query->setAttribute('recentNoticeLength', $this->recentNoticeLength);
		}
		if (isset($this->recentTrackbackLength)) {
			if (!Validator::number($this->recentTrackbackLength, 0))
				return $this->_error('recentTrackbackLength');
			$query->setAttribute('recentTrackbackLength', $this->recentTrackbackLength);
		}
		if (isset($this->linkLength)) {
			if (!Validator::number($this->linkLength, 0))
				return $this->_error('linkLength');
			$query->setAttribute('linkLength', $this->linkLength);
		}
		if (isset($this->showListOnCategory))
			$query->setAttribute('showListOnCategory', Validator::getBit($this->showListOnCategory));
		if (isset($this->showListOnArchive))
			$query->setAttribute('showListOnArchive', Validator::getBit($this->showListOnArchive));
		if (isset($this->tree)) {
			if (!Validator::directory($this->tree) || !file_exists(ROOT . '/image/tree/' . $this->tree))
				return $this->_error('tree');
			$query->setAttribute('tree', $this->tree, false);
		}
		if (isset($this->colorOnTree))
			$query->setAttribute('colorOnTree', $this->colorOnTree, true);
		if (isset($this->bgColorOnTree))
			$query->setAttribute('bgColorOnTree', $this->bgColorOnTree, true);
		if (isset($this->activeColorOnTree))
			$query->setAttribute('activeColorOnTree', $this->activeColorOnTree, true);
		if (isset($this->activeBgColorOnTree))
			$query->setAttribute('activeBgColorOnTree', $this->activeBgColorOnTree, true);
		if (isset($this->labelLengthOnTree)) {
			if (!Validator::number($this->labelLengthOnTree, 0))
				return $this->_error('labelLengthOnTree');
			$query->setAttribute('labelLengthOnTree', $this->labelLengthOnTree);
		}
		if (isset($this->showValueOnTree))
			$query->setAttribute('showValueOnTree', Validator::getBit($this->showValueOnTree));
			
		if ($query->update())
			return true;
		return $query->insert();
	}

	function _error($error) {
		$this->error = $error;
		return false;
	}
}
?>