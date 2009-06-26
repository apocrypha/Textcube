<?php
/// Copyright (c) 2004-2009, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

global $__locale, $__text, $__skinText;

final class Locale {
	// Requires $__locale as global variable. (language resource information)
	static function get() {
		global $__locale;
		return $__locale['locale'];
	}

	static function set($locale, $scope = 'owner') {
		global $__locale, $__text;
		list($common) = explode('-', $locale, 2);
//		Locale::refreshLocaleResource($locale);
		if (file_exists($__locale['directory'] . '/' . $locale . '/'.$scope.'.php')) {
			include_once($__locale['directory'] . '/' . $locale . '/'.$scope.'.php');
			$__locale['locale'] = $locale;
			return true;
		} else if (($common != $locale) && file_exists($__locale['directory'] . '/' . $common . '/'.$scope.'.php')) {
			include_once($__locale['directory'] . '/' . $common . '/'.$scope.'.php');
			$__locale['locale'] = $common;
			return true;
		}
		return false;
	}

	static function setSkinLocale($locale) {
		global $__locale, $__skinText;
		list($common) = explode('-', $locale, 2);
//		Locale::refreshLocaleResource($locale);
		if (file_exists($__locale['directory'] . '/' . $locale . '.php')) {
			$__skinText = Locale::includeLocaleFile($__locale['directory'] . '/' . $locale . '/blog.php');
			return true;
		} else if (($common != $locale) && file_exists($__locale['directory'] . '/' . $common . '/blog.php')) {
			$__skinText = Locale::includeLocaleFile($__locale['directory'] . '/' . $common . '/blog.php');
			return true;
		}
		return false;
	}

	static function includeLocaleFile($languageFile) {
		include($languageFile);
		return $__text;
	}

	static function refreshLocaleResource($locale) {
		global $__locale;
		// po파일과 php파일의 auto convert 지원을 위한 루틴.
		$lang_php = $__locale['directory'] . '/' . $locale . ".php";
		$lang_po = $__locale['directory'] . '/po/' . $locale . ".po";
		// 두 파일 중 최근에 갱신된 것을 찾는다.
		$time_po = filemtime( $lang_po );
		$time_php = filemtime( $lang_php );
		// po파일이 더 최근에 갱신되었으면 php파일을 갱신한다.
		if($time_po && $time_po > $time_php ) {
			requireComponent('Needlworks.Core.Locale');
			$langConvert = new Po2php;
			$langConvert->open($lang_po);
			$langConvert->save($lang_php);
		}
		return false;
	}

	static function setDirectory($directory) {
		global $__locale;
		if (!is_dir($directory))
			return false;
		$__locale['directory'] = $directory;
		return true;
	}

	static function setDomain($domain) {
		global $__locale;
		$__locale['domain'] = $domain;
		return true;
	}

	static function match($locale) {
		global $__locale;
		if (strcasecmp($locale, $__locale['locale']) == 0)
			return 3;
		else if (strncasecmp($locale, $__locale['locale'], 2) == 0)
			return 2;
		else if (strncasecmp($locale, 'en', 2) == 0)
			return 1;
		return 0;
	}

	static function getSupportedLocales() {
		global $__locale;
		$locales = array();
		if ($dir = dir($__locale['directory'])) {
			while (($entry = $dir->read()) !== false) {
				if (!is_dir($__locale['directory'] . '/' . $entry))
					continue;
//				$locale = substr($entry, 0, strpos($entry, '.'));
				$locale = $entry;
				if (empty($locale) || $locale == 'messages' || $locale == 'po')
					continue;
				if ($fp = fopen($__locale['directory'] . '/' . $entry . '/description.php', 'r')) {
					$desc = fgets($fp);
					if (preg_match('/<\?(php)?\s*\/\/\s*(.+)/', $desc, $matches))
						$locales[$locale] = _t(trim($matches[2]));
					else
						$locales[$locale] = $locale;
					fclose($fp);
				}
			}
			$dir->close();
		}
		return $locales;
	}
}

final class Po2php
{
	var $msgs;
	var $nomsgs;
	var $comments;

	function open( $source_file )
	{
		$fsource = fopen( $source_file, "r" );
		if( !$fsource )
		{
			return 0;
		}

		$state = 0;
		$this->msgs = array();
		$this->nomsgs = array();
		$this->comments = array();
		$comment = '';
		while( !feof( $fsource ) )
		{
			$line = fgets( $fsource, 4096 );
			$line = rtrim($line);
			if( substr($line,0,1) == "#" )
			{
				$comment .= "$line\r\n";
				continue;
			}

			if( $state == 0 )
			{
				if( preg_match( '/^msgid\s+"(.*)"/', $line, $container ) )
				{
					$state = 1;
					$msgid = $container[1];
					continue;
				}
			}
			else if( $state == 1 )
			{
				if( preg_match( '/^msgstr\s+"(.*)"/', $line, $container ) )
				{
					$msgstr = $container[1];
					$state = 2;
				}
				else
				{
					$line = preg_replace( '/^"|"$/', "", $line );
					$msgid .= $line;
				}
				continue;
			}
			else if( $state == 2 )
			{
				if( preg_match( '/^\s*$/', $line ) )
				{
					if( $msgid != "" )
					{
						if( $msgstr == "" )
						{
							$this->nomsgs[$msgid] = "";
						}
						else
						{
							$this->msgs[$msgid] = $msgstr;
						}
						$this->comments[$msgid] = $comment;
					}
					$comment = "";
					$state = 0;
				}
				else
				{
					$line = preg_replace( '/^"|"$/', "", $line );
					$msgstr .= $line;
				}
			}

		}

		fclose( $fsource );

		return 1;
	}

	function save( $target_file )
	{
		if( !is_writable( $target_file ) ) {
			return 0;
		}
		$ftarget = fopen( $target_file, "w+" );
		if( !$ftarget )
		{
			return 0;
		}

		$msgs = array_merge( $this->msgs, $this->nomsgs );
		ksort( $msgs );
		fwrite( $ftarget, "<?php\r\n" );
		foreach( $msgs as $msgid => $msgstr )
		{
			$comment = $this->comments[$msgid];

			if( $msgstr == "" )
			{
				$pass = "//";
			}
			else
			{
				$pass = "";
			}

			$msgid = str_replace( '\"', '"', $msgid );
			$msgid = str_replace( '\\\\', '\\', $msgid );
			$msgstr = str_replace( '\"', '"', $msgstr );
			$msgstr = str_replace( '\\\\', '\\', $msgstr );

			fwrite( $ftarget, $comment );
			fwrite( $ftarget, $pass . '$' . "__text['$msgid'] = '$msgstr';\r\n" );
		}

		fwrite( $ftarget, "?>\r\n" );

		fclose( $ftarget );

		return 1;
	}

	function saveMsgstrAsMsgid( $target_file )
	{
		$ftarget = fopen( $target_file, "w+" );
		if( !$ftarget )
		{
			return 0;
		}

		$msgs = array_merge( $this->msgs, $this->nomsgs );
		ksort( $msgs );
		fwrite( $ftarget, "#,\r\n" );
		fwrite( $ftarget, "msgid \"\"\r\n" );
		fwrite( $ftarget, "msgstr \"\"\r\n" );
		fwrite( $ftarget, "\"Project-Id-Version: PACKAGE VERSION\\n\"\r\n" );
		fwrite( $ftarget, "\"Report-Msgid-Bugs-To: \\n\"\r\n" );
		fwrite( $ftarget, "\"POT-Creation-Date: " .  strftime( "%Y-%m-%d %H:%M+0000" ) . "\\n\"\r\n" );
		fwrite( $ftarget, "\"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n\"\r\n" );
		fwrite( $ftarget, "\"Last-Translator: TEXTCUBE\\n\"\r\n" );
		fwrite( $ftarget, "\"Language-Team: TEXTCUBE\\n\"\r\n" );
		fwrite( $ftarget, "\"MIME-Version: 1.0\\n\"\r\n" );
		fwrite( $ftarget, "\"Content-Type: text/plain; charset=UTF-8\\n\"\r\n" );
		fwrite( $ftarget, "\"Content-Transfer-Encoding: 8bit\\n\"\r\n" );
		fwrite( $ftarget, "\r\n" );


		foreach( $msgs as $msgid => $msgstr )
		{
			$comment = $this->comments[$msgid];

			fwrite( $ftarget, $comment );
			fwrite( $ftarget, "msgid \"$msgstr\"\r\n" );
			fwrite( $ftarget, "msgstr \"\"\r\n" );
			fwrite( $ftarget, "\r\n" );
		}

		fclose( $ftarget );

		return 1;
	}

	function Convert($source_file, $target_file)
	{
		$this->open($source_file);
		$this->save($target_file);
	}
}
?>