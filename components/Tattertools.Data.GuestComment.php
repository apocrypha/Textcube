<?
class GuestComment {
	function GuestComment() {
		$this->reset();
	}

	function reset() {
		$this->error =
		$this->id =
		$this->parent =
		$this->commenter =
		$this->name =
		$this->homepage =
		$this->ip =
		$this->password =
		$this->secret =
		$this->content =
		$this->written =
			null;
	}
	
	function open($filter = '', $fields = '*', $sort = 'id') {
		global $database, $owner;
		if (is_numeric($filter))
			$filter = 'AND id = ' . $filter;
		else if (!empty($filter))
			$filter = 'AND ' . $filter;
		if (!empty($sort))
			$sort = 'ORDER BY ' . $sort;
		$this->close();
		$this->_result = mysql_query("SELECT $fields FROM {$database['prefix']}Comments WHERE owner = $owner AND entry = 0 $filter $sort");
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
					case 'replier':
						$name = 'commenter';
						break;
					case 'comment':
						$name = 'content';
						break;
				}
				$this->$name = $value;
			}
			return true;
		}
		return false;
	}
	
	function add() {
		global $database, $owner;
		if (!isset($this->commenter) && !isset($this->name))
			return $this->_error('commenter');
		if (!isset($this->content))
			return $this->_error('content');
		if (!isset($this->ip))
			$this->ip = $_SERVER['REMOTE_ADDR'];
		
		if (!$query = $this->_buildQuery())
			return false;
		if (!$query->hasAttribute('written'))
			$query->setAttribute('written', 'UNIX_TIMESTAMP()');
		
		if (!$query->insert())
			return $this->_error('insert');
		$this->id = $query->id;
		return true;
	}
	
	function getCount() {
		return (isset($this->_count) ? $this->_count : 0);
	}
	
	function getChildren() {
		if (!Validator::number($this->id, 1))
			return null;
		$comment = new Comment();
		if ($comment->open('parent = ' . $this->id))
			return $comment;
	}
	
	function _buildQuery() {
		global $database, $owner;
		$query = new TableQuery($database['prefix'] . 'Comments');
		$query->setQualifier('owner', $owner);
		$query->setQualifier('entry', 0);
		if (isset($this->id)) {
			if (!Validator::number($this->id, 1))
				return $this->_error('id');
			$query->setQualifier('id', $this->id);
		}
		if (isset($this->parent)) {
			if (!Validator::number($this->parent, 1))
				return $this->_error('parent');
		}
		$query->setAttribute('parent', $this->parent);
		if (isset($this->commenter)) {
			if (!Validator::number($this->commenter, 1))
				return $this->_error('commenter');
			$query->setAttribute('replier', $this->commenter);
		}
		if (isset($this->name)) {
			$this->name = trim($this->name);
			if (empty($this->name))
				return $this->_error('name');
			$query->setAttribute('name', $this->name, true);
		}
		if (isset($this->homepage)) {
			$this->homepage = trim($this->homepage);
			if (empty($this->homepage))
				return $this->_error('homepage');
			$query->setAttribute('homepage', $this->homepage, true);
		}
		if (isset($this->ip)) {
			if (!Validator::ip($this->ip))
				return $this->_error('ip');
			$query->setAttribute('ip', $this->ip, true);
		}
		if (isset($this->secret))
			$query->setAttribute('secret', Validator::getBit($this->secret));
		if (isset($this->content)) {
			$this->content = trim($this->content);
			if (empty($this->content))
				return $this->_error('content');
			$query->setAttribute('comment', $this->content, true);
		}
		if (isset($this->written)) {
			if (!Validator::timestamp($this->written))
				return $this->_error('written');
			$query->setAttribute('written', $this->written);
		}
		return $query;
	}

	function _error($error) {
		$this->error = $error;
		return false;
	}
}
?>