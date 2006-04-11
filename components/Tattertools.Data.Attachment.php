<?
class Attachment {
	function Attachment() {
		$this->reset();
	}

	function reset() {
		$this->error =
		$this->name =
		$this->parent =
		$this->label =
		$this->mime =
		$this->size =
		$this->width =
		$this->height =
		$this->downloads =
		$this->enclosure =
		$this->attached =
			null;
	}
	
	function open($filter = '', $fields = '*', $sort = 'attached') {
		global $database, $owner;
		if (!empty($filter))
			$filter = 'AND ' . $filter;
		if (!empty($sort))
			$sort = 'ORDER BY ' . $sort;
		$this->close();
		$this->_result = mysql_query("SELECT $fields FROM {$database['prefix']}Attachments WHERE owner = $owner $filter $sort");
		if ($this->_result) {
			if ($this->_count = mysql_num_rows($this->_result))
				return $this->shift();
			else
				mysql_free_result($this->_result);
		}
		unset($this->_result);
		return false;
	}
	
	function close() {
		if (isset($this->_result)) {
			mysql_free_result($this->_result);
			unset($this->_result);
		}
		$this->_count = 0;
		$this->reset();
	}
	
	function shift() {
		$this->reset();
		if ($this->_result && ($row = mysql_fetch_assoc($this->_result))) {
			foreach ($row as $name => $value) {
				if ($name == 'owner')
					continue;
				switch ($name) {
					case 'enclosure':
						$value = $value ? true : false;
						break;
				}
				$this->$name = $value;
			}
			return true;
		}
		return false;
	}
	
	function add() {
		if (!$this->_generateName())
			return false;
	
		if (!$query = $this->_buildQuery())
			return false;
		if (!isset($this->attached))
			$query->setAttribute('attached', 'UNIX_TIMESTAMP()');

		if (!$query->insert())
			return $this->_error('insert');
		return true;
	}
	
	function update() {
		if (!$query = $this->_buildQuery())
			return false;

		if (!$query->update())
			return $this->_error('update');
		return true;
	}
	
	function getCount() {
		return (isset($this->_count) ? $this->_count : 0);
	}
	
	/*@static@*/
	function doesExist($name) {
		global $database, $owner;
		if (!Validator::filename($name))
			return null;
		return DBQuery::queryExistence("SELECT parent FROM {$database['prefix']}Attachments WHERE owner = $owner AND name = '$name'");
	}
	
	/*@static@*/
	function getParent($name) {
		global $database, $owner;
		if (!Validator::filename($name))
			return null;
		return DBQuery::queryCell("SELECT parent FROM {$database['prefix']}Attachments WHERE owner = $owner AND name = '$name'");
	}
	
	/*@static@*/
	function adjustPermission($filename) {
		global $service;
		if (isset($service['umask'])) {
			if (is_dir($filename))
				chmod($filename, 0777);
			else
				chmod($filename, 0666);
		}
	}
	
	/*@static@*/
	function confirmFolder() {
		global $owner, $service;
		$path = ROOT . "/attach/$owner";
		if (!file_exists($path)) {
			mkdir($path);
			if (isset($service['umask']))
				@chmod($path, 0777 - $service['umask']);
		}
		if (!is_dir($path)) {
			//TODO:critical error
			exit;
		}
	}
	
	function _generateName() {
		global $owner;
		if (isset($this->name)) {
			if (!Validator::filename($this->name, false))
				return $this->_error('name');
			switch (Path::getExtension($this->name)) {
				case '.php':
				case '.exe':
				case '.com':
				case '.sh':
				case '.bat':
					$ext = '.xxx';
					$this->name = rand(1000000000, 9999999999) . $ext;
					break;
				default:
					$ext = Path::getExtension2($this->name);
					break;
			}
		} else {
			$ext = '';
		}
		$this->confirmFolder();
		while (Attachment::doesExist($this->name))
			$this->name = rand(1000000000, 9999999999) . $ext;
		return true;
	}
	
	function _buildQuery() {
		if (!Validator::filename($this->name))
			return $this->_error('name');

		global $database, $owner;
		$query = new TableQuery($database['prefix'] . 'Attachments');
		$query->setQualifier('owner', $owner);
		$query->setQualifier('name', $this->name, false);
		if (isset($this->parent)) {
			if (!Validator::number($this->parent, -1))
				return $this->_error('parent');
			$query->setAttribute('parent', $this->parent);
		}
		if (isset($this->label)) {
			$this->label = trim($this->label);
			if (empty($this->label))
				return $this->_error('label');
			$query->setAttribute('label', $this->label, true);
		}
		if (isset($this->mime)) {
			$this->mime = trim($this->mime);
			$query->setAttribute('mime', $this->mime, true);
		}
		if (isset($this->size)) {
			if (!Validator::number($this->size, 0))
				return $this->_error('size');
			$query->setAttribute('size', $this->size);
		}
		if (isset($this->width)) {
			if (!Validator::number($this->width, 0))
				return $this->_error('width');
			$query->setAttribute('width', $this->width);
		}
		if (isset($this->height)) {
			if (!Validator::number($this->height, 0))
				return $this->_error('height');
			$query->setAttribute('height', $this->height);
		}
		if (isset($this->downloads)) {
			if (!Validator::number($this->downloads, 0))
				return $this->_error('downloads');
			$query->setAttribute('downloads', $this->downloads);
		}
		if (isset($this->enclosure))
			$query->setAttribute('enclosure', Validator::getBit($this->enclosure));
		if (isset($this->attached)) {
			if (!Validator::number($this->attached, 1))
				return $this->_error('attached');
			$query->setAttribute('attached', $this->attached);
		}
		return $query;
	}

	function _error($error) {
		$this->error = $error;
		return false;
	}
}
?>