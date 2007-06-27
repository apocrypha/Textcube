<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

define( 'OPENID_PLUGIN_VERSION', 1.0 ); 
define( 'OPENID_PASSWORD', "-OPENID-" );

global $hostURL, $service;
global $openid_pluginbase;
$openid_pluginbase = $hostURL . $service['path'] . "/plugins/" . basename(dirname( __FILE__ ));

require_once  "openid_session.php";
requireComponent( "Eolin.PHP.Core" );

openid_session_read();

function openid_login()
{
	global $hostURL, $blogURL, $service;
	global $openid_session;
	global $openid_session_id;

	$requestURI = $_GET['requestURI'];

	require "affiliate.php";

	$img_url = $hostURL . $service['path'] . "/plugins/" . basename(dirname( __file__ )) . "/login-bg.gif";

	if( !empty($_COOKIE['openid']) ) {
		$openid_remember_check = "checked";
		$cookie_openid = $_COOKIE['openid'];
	} else {
		$openid_remember_check = "";
		$cookie_openid = '';
	}

	if( strlen($openid_session_id) >= 32 ) {
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html><head>
<title>텍스트큐브 오픈아이디 인증</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="' . $service['path'] . '/style/admin/default/basic.css" />
<link rel="stylesheet" type="text/css" href="' . $service['path'] . '/style/admin/default/login.css" />
<style>
.login-button
{
	position						 : static;
	background-color                 : #FFFFFF;
	background-position              : left top;
	display                          : inline;
	font-weight                      : bold;
	height                           : 3em;
	line-height                      : 3em;
	width                            : 6em;
	border                           : 1px solid #777777;
	cursor                           : pointer;
	margin-right					 : 5px;
}

#logo-box
{
	text-align                       : center;
}

dl
{
	margin-left                      : 70px;
}
dd
{
	margin-top						 : 1em;
	margin-left                      : 0;
}
dd .input-text
{
	border                           : 1px solid #999999;
	font-family                      : "Lucida Grande", Tahoma, Arial, Verdana, sans-serif;
	font-size                        : 1.3em;
	padding                          : 3px 0 3px 5px;
	width                            : 208px;
}
#data-outbox {
	width:650px;
} 

#rember_login {
	padding-top: 10px;
}

#openid_identifier {
	padding-left: 30px; 
	background: url(' . $img_url . ') no-repeat; 
	height: 1.5em; 
	width:400px;
	font-size: 1.5em;
	font-weight: bold;
	font-family: arial;
}
</style>
</head>
<body id="body-login" onload="document.getElementById(\'openid_identifier\').focus()">
	<div id="temp-wrap">
		<div id="all-wrap">
			<form method="get" name="openid_form" action="' . $blogURL . '/plugin/openid/try_auth">
				<div id="data-outbox">
					<div id="login-box">
						<div id="logo-box">
							<img src="' . $service['path'] . '/style/admin/default/image/logo_textcube.png" alt="텍스트큐브 로고" />
			            	<p><b>텍스트큐브 오픈아이디 로그인</b></p>
			            </div>
			            
			            <div id="field-box">
			            	<dl id="email-line">
			            		<dt><label for="loginid">' . _text('오픈아이디') . '</label></dt>

			            		<dd><input type="text" class="input-text" id="openid_identifier" name="openid_identifier" value="' . $cookie_openid . '" maxlength="256" tabindex="1" /></dd>
			            		<dd><input type="checkbox" class="checkbox" id="openid_remember" name="openid_remember" ' . $openid_remember_check. ' /><label for="openid_auto">' . _text('오픈아이디 기억') . '</label></dd>
			            		<dd><input type="submit" class="login-button" name="openid_login" value="로그인" /><input type="submit" class="login-button" name="openid_cancel" value="취소" /></dd>
			            		<dd><a href="' . $openid_help_link . '">' . _text('오픈아이디란?') . '</a> | <a href="' . $openid_signup_link . '">' . _text('오픈아이디 발급하기') . '</a></dd>
							</dl>
						</div>
					</div>
				</div>
        		<input type="hidden" name="requestURI" value="' . $requestURI . '" />
			</form>
		</div>
	</div>
</body>
</html>
';
	} else {
	echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html><head>
<title>텍스트큐브 오픈아이디 인증</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="' . $service['path'] . '/style/admin/default/basic.css" />
<link rel="stylesheet" type="text/css" href="' . $service['path'] . '/style/admin/default/login.css" />
</head>
<body id="body-login">
<script>
alert("Session creation error' . $openid_session_id . '");
</script>
</body>
</html>
';
	}
}

function _openid_update_id($openid,$delegatedid,$nickname,$homepage=null,$userid=null)
{
	global $database, $owner;
	global $openid_session;
	$openid = mysql_tt_escape_string($openid);
	$delegatedid = mysql_tt_escape_string($delegatedid);

	$query = "SELECT data FROM {$database['prefix']}OpenIDUsers WHERE openid='{$openid}'";
	$result = DBQuery::queryCell($query);

	if (is_null($result)) {
		$data = serialize( array( 'nickname' => $nickname, 'homepage' => $homepage, 'acl' => '' ) );
		$openid_session['nickname'] = $nickname;
		$openid_session['homepage'] = $homepage;

		/* Owner column is used for reference, all openid records are shared */
		DBQuery::execute("insert into {$database['prefix']}OpenIDUsers (owner,openid,delegatedid,firstLogin,lastLogin,loginCount,data) values ($owner,'{$openid}','{$delegatedid}',UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),1,'{$data}')");
	} else {
		$data = unserialize( $result );

		if( !empty($nickname) ) $data['nickname'] = $nickname;
		if( !empty($homepage) ) $data['homepage'] = $homepage;
		if( $userid !== null ) $data['acl'] = $userid;

		$openid_session['nickname'] = $data['nickname'];
		$openid_session['homepage'] = $data['homepage'];

		if( !isset($data['acl']) ) {
			$data['acl'] = '';
		}

		$data = serialize( $data );
		DBQuery::execute("update {$database['prefix']}OpenIDUsers set data='{$data}', lastLogin = UNIX_TIMESTAMP(), loginCount = loginCount + 1 where openid = '{$openid}'");
	}
	return;
}

function _openid_existed($openid)
{
	global $database, $owner;
	$openid = mysql_tt_escape_string($openid);

	$query = "SELECT openid FROM {$database['prefix']}OpenIDUsers WHERE owner={$owner} and openid='{$openid}'";
	$result = DBQuery::queryCell($query);

	if (is_null($result)) {
		return false;
	}
	return true;
}

function _openid_authorizeSession($userid) {
	global $database, $service;
	if (!is_numeric($userid))
		return false;
	$_SESSION['userid'] = $userid;
	if (isSessionAuthorized(session_id()))
		return true;
	for ($i = 0; $i < 100; $i++) {
		$id = dechex(rand(0x10000000, 0x7FFFFFFF)) . dechex(rand(0x10000000, 0x7FFFFFFF)) . dechex(rand(0x10000000, 0x7FFFFFFF)) . dechex(rand(0x10000000, 0x7FFFFFFF));
		$result = mysql_query("INSERT INTO {$database['prefix']}Sessions(id, address, userid, created, updated) VALUES('$id', '{$_SERVER['REMOTE_ADDR']}', $userid, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
		if ($result && (mysql_affected_rows() == 1)) {
			@session_id($id);
			header("Set-Cookie: TSSESSION=$id; path=/; domain={$service['domain']}");
			return true;
		}
	}
	return false;
}

function _openid_set_acl($openid)
{
	global $database;
	$blogid = getBlogId();
	$query = "SELECT * FROM {$database['prefix']}OpenIDUsers WHERE owner={$blogid} and openid='{$openid}'";
	$result = DBQuery::queryRow($query);
	$data = unserialize( $result['data'] );

	if( !isset($data['acl']) ) {
		return;
	}

	$userid = $data['acl'];

	if( empty($userid) || !class_exists( "Acl" ) ) {
		return;
	}

	/* Check Acl class and use Auth class.. this is normal */
	Auth::setBasicAro($userid);
	Auth::setTeamblogAro($userid);

	if( in_array( "group.writers", Acl::getAro() ) ) {
		authorizeSession($blogid, $userid);
	} else {
		authorizeSession($blogid, null);
	}

}

function openid_get_current($target)
{
	global $openid_session;
	if( empty($openid_session['id'] )) {
		return '';
	}
	return $openid_session['id'];
}

function openid_try_auth()
{
	global $hostURL, $blogURL;
	if( isset($_GET['openid_remember']) ) {
		$openid_remember = true;
	} else {
		$openid_remember = false;
	}

	if( !empty($_GET['authenticate_only'])) {
		$authenticate_only = '1';
	} else {
		$authenticate_only = '';
	}

	$openid = $_GET['openid_identifier'];
	$requestURI = $_GET['requestURI'];
	if( empty($requestURI) ) {
		$requestURI = $blogURL;
	}

	if( isset($_GET['openid_cancel']) || isset($_GET['openid_cancel_x']) ) {
		header( "Location: " . $blogURL);
		exit(0);
	}

	if (empty($openid)) {
		openid_setcookie( 'openid_auto', 'n' );
		print "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8' /></head><body><script>alert('" . _text("오픈ID를 입력하세요") . "');";
		print "document.location.href='$blogURL/plugin/openid/login?requestURI=" . urlencode($requestURI) . "';</script></body></html>";
		exit(0);
	}

	return _openid_try_auth( $openid, $requestURI, $openid_remember, $authenticate_only );
}

function openid_fetch( $openid )
{
	require_once  "common.php";
	require_once  "xmlwrapper.php";

	static $xmlparser = null;
	if( !$xmlparser ) $xmlparser = new Services_Textcube_xmlparser();
	Services_Yadis_setDefaultParser( $xmlparser );

	// Begin the OpenID authentication process.
	ob_start();
	$auth_request = $consumer->begin($openid);
	ob_end_clean();

	if (!$auth_request) {
		return "";
	}

	return $auth_request->endpoint->identity_url;
}

function openid_set_userid($openid)
{
	global $owner;
	$userid = $owner;
	if( function_exists( "getUserId" ) ) {
		$userid = getUserId();
	}
	_openid_update_id( $openid, null, null, null, $userid );
	return "";
}

function openid_reset_userid($openid)
{
	_openid_update_id( $openid, null, null, null, "" );
	return "";
}

function _openid_try_auth( $openid, $requestURI, $openid_remember, $authenticate_only )
{
	global $hostURL, $blogURL;
	require_once  "common.php";
	require_once  "xmlwrapper.php";

	static $xmlparser = null;
	if( !$xmlparser ) $xmlparser = new Services_Textcube_xmlparser();

	Services_Yadis_setDefaultParser( $xmlparser );

	$process_url = $hostURL . $blogURL . "/plugin/openid/finish?authenticate_only=$authenticate_only&requestURI=" . urlencode($requestURI);
	$trust_root = $hostURL . $blogURL;

	// Begin the OpenID authentication process.
	ob_start();
	$auth_request = $consumer->begin($openid);
	ob_end_clean();

	unset($_SESSION['verified_openid']);

	// Handle failure status return values.
	if (!$auth_request) {
		openid_setcookie( 'openid_auto', 'n' );
		if( !empty($authenticate_only) ) {
			$requestURI .= (strchr($requestURI,'?')===false ? "?":"&" ) . "authenticated=0";
		}
		print "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8' /></head><body><script>alert('" . _text("인증하지 못하였습니다. 아이디를 확인하세요") . "');document.location.href='" . $requestURI . "';</script></body></html>";
		exit(0);
	}

	if( ! _openid_existed( $auth_request->endpoint->identity_url ) )
	{
		$auth_request->addExtensionArg('sreg', 'optional', 'nickname');
	}

	if( $openid_remember ) {
			openid_setcookie( 'openid', $auth_request->endpoint->identity_url );
	} else {
			openid_clearcookie( 'openid' );
	}

	$redirect_url = $auth_request->redirectURL($trust_root, $process_url);

	header("HTTP/1.0 302 Moved Temporarily");
	header("Location: ".$redirect_url);

	// Hack for avoiding textcube zero-length content
	print( "<html><body>Textcube</body></html>" );
	exit(0);
}

function openid_finish()
{
	global $openid_session;
	// Complete the authentication process using the server's response.
	require_once  "common.php";

	ob_start();

	$response = $consumer->complete($_GET);

	if ($response->status == Auth_OpenID_CANCEL) {
		// This means the authentication was cancelled.
		$msg = '인증이 취소되었습니다.';
	} else if ($response->status == Auth_OpenID_FAILURE) {
		$msg = "오픈아이디 인증이 실패하였습니다: " . $response->message;
	} else if ($response->status == Auth_OpenID_SUCCESS) {
		// This means the authentication succeeded.
		$openid = $response->identity_url;
		$sreg = $response->extensionResponse('sreg');
		if( !isset($sreg['nickname']) ) {
			$sreg['nickname'] = "";
		}

		$_SESSION['verified_openid'] = $openid;
		if( empty($_GET['authenticate_only']) ) {
			$openid_session['id'] = $openid;
			$openid_session['delegatedid'] = $response->endpoint->delegate;
			_openid_update_id( $response->identity_url, $response->endpoint->delegate, $sreg['nickname'] );
			_openid_set_acl( $response->identity_url );
			openid_session_write();
		}
	}

	$requestURI = $_GET['requestURI'];
	if( !empty($_GET['authenticate_only']) && $msg ) {
		$requestURI .= (strchr($requestURI,'?')===false ? "?":"&" ) . "authenticated=0";
	}

	if( $msg )
	{
		ob_end_clean();
		openid_setcookie( 'openid_auto', 'n' );
		header("HTTP/1.0 200 OK");
		header("Content-type: text/html");
		print "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8' /></head><body><script>alert(\"$msg\"); document.location.href=\"$requestURI\";</script></body></html>";
	}
	else
	{
		ob_end_clean();
		openid_setcookie( 'openid_auto', 'y' );
		header("HTTP/1.0 302 Moved Temporarily");
		header("Location: $requestURI");

		// Hack for avoiding textcube zero-length content
		print( "<html><body></body></html>" );
	}
	ob_flush();
}

function openid_logout_session($target)
{
	global $openid_session;
	openid_session_destroy();

	$openid_session['id'] = '';
	$openid_session['nickname'] = '';
	openid_session_write();
	openid_setcookie( 'openid_auto', 'n' );
	return "";
}

function openid_logout()
{
	openid_logout_session();

	header("HTTP/1.0 302 Moved Temporarily");
	header("Location: ".$_GET['requestURI']);

	// Hack for avoiding textcube zero-length content
	print( "<html><body></body></html>" );
	exit;
}

function _openid_additional_script()
{
	global $blogURL;
	return '
			function deleteComment(id) {
				width = 450;
				height = 400;
				if(openWindow != \'\') openWindow.close();
				openWindow = window.open("' . $blogURL . '/plugin/openid/comment/delete?id=" + id, "textcube", "width="+width+",height="+height+",location=0,menubar=0,resizable=0,scrollbars=0,status=0,toolbar=0");
				openWindow.focus();
				alignCenter(openWindow,width,height);
			}
			
			function commentComment(parent) {	
				width = 450;
				height = 380;
				if(openWindow != \'\') openWindow.close();
				openWindow = window.open("' . $blogURL . '/plugin/openid/comment/comment?id=" + parent, "textcube", "width="+width+",height="+height+",location=0,menubar=0,resizable=0,scrollbars=0,status=0,toolbar=0");
				openWindow.focus();
				alignCenter(openWindow,width,height);
			}
			';
}
function openid_hardcore_login($target)
{
	global $openid_session;
	if( !isset($_COOKIE['openid_auto']) || $_COOKIE['openid_auto'] != 'y' ) {
		return $target;
	}
	if( !empty($openid_session['id']) ) {
		return $target;
	}
	_openid_try_auth( $_COOKIE['openid'], $_SERVER["REQUEST_URI"], true, '' );
	/* Never return */
	return $target;
}

function openid_add_controller($target)
{
	global $hostURL, $service, $blogURL;
	global $openid_session;
	global $openid_pluginbase;
	$script_url = $openid_pluginbase . "/openid.js";

	$openid_id = "";
	$openid_nickname = "";

	if( isset($openid_session['id']) )
	{
		$openid_id = $openid_session['id'];
		$openid_nickname = $openid_session['nickname'];
		openid_session_write();
	}
	else
	{
		$openid_loggedin = 0;
	}
	$target .= "<script type='text/javascript'>\n" .
		"var openid_entryurl = \"$hostURL$blogURL/plugin/openid/\";\n" .
		"var openid_pluginbase = \"$openid_pluginbase/\";\n" .
		"var openid_id = '$openid_id';\n" .
		"var openid_nickname = '$openid_nickname';\n" .
		_openid_additional_script() .
		"</script>\n" .
		"<script type=\"text/javascript\" src=\"$script_url\"></script>\n";
	return $target;
}

function openid_add_loginform($target, $requestURI)
{
	global $hostURL, $blogURL, $service;
	global $openid_session;
	global $openid_session_id;

	$img_url = $hostURL . $service['path'] . "/plugins/" . basename(dirname( __file__ )) . "/login-bg.gif";

	require "affiliate.php";

	if( !empty($_COOKIE['openid']) ) {
		$openid_remember_check = "checked";
		$cookie_openid = $_COOKIE['openid'];
	} else {
		$openid_remember_check = "";
		$cookie_openid = '';
	}
	$target .= '
<style type="text/css">
#openid-temp-wrap {width: 230px; margin: 20px -10px 0 340px;}
#openid-line { margin: 0; padding-right: 5px;}
#openid-all-wrap { position:relative; width: 230px; }
#openid-field-box { width: 230px; }
#openid_identifier { font-size: 1.3em; padding-left: 30px; width: 183px; background: url(' . $img_url . ') no-repeat; }
.openid-login-button { display: inline; width: 74px; height: 3em; cursor: pointer; padding: 0pt 5px; font-size: 1em; font-weight: bold; font-family:\'Lucida Grande\',Arial,굴림,Gulim,Tahoma,Verdana,sans-serif; background-color: #fff; border: 1px solid ; vertical-align: middle}
#openid-login-button { float: right; margin: 15px 10px 5px 20px; left: 100px }
#openid-remember { display:block; margin-top: 10px; }
#openid-help { display:block; }
</style>
	<form method="get" name="openid_form" action="' . $blogURL . '/plugin/openid/try_auth">
	<div id="openid-temp-wrap">
		<hr size="1">
		<div id="openid-all-wrap">
			<div id="openid-field-box">
				<dl id="openid-line">
					<dt><label for="loginid">' . _text('오픈아이디') . '</label></dt>

					<dd><input type="text" class="input-text" id="openid_identifier" name="openid_identifier" value="' . $cookie_openid . '" maxlength="256" /></dd>
					<input onfocus="alert(\"hi\");" type="submit" class="openid-login-button" id="openid-login-button" name="openid_login" value="로그인" />
					<dd id="openid-remember"><input type="checkbox" class="checkbox" name="openid_remember" ' . $openid_remember_check. ' /><label for="openid_auto">' . _text('오픈아이디 저장') . '</label></dd>
					<dd id="openid-help"><a href="' . $openid_help_link . '">' . _text('오픈아이디란?') . '</a> </dd>
					<dd><a href="' . $openid_signup_link . '">' . _text('오픈아이디 발급하기') . '</a></dd>
				</dl>
			</div>
			<input type="hidden" name="requestURI" value="' . $requestURI . '" />
		</div>
	</div>
	</form>
	<script type="text/javascript">function focus_openid(){document.getElementById("openid_identifier").focus();}</script>
	';
	return $target;
}

function _openid_set_temp_password( $owner, $id )
{
	global $database;
	$pw = md5( 'seed for hash' . time() . filemtime( ROOT . 'config.php') );
	$pw = substr($pw, 0, 32);
	DBQuery::execute("UPDATE {$database['prefix']}Comments SET password = '" . md5($pw) . "' WHERE owner = $owner and id = $id" );
	return $pw;
}

function _openid_get_auth_id()
{
	global $openid_session;
	if( !isset( $openid_session['id'] ) ) {
		return '';
	}
	return $openid_session['id'];
}

function _openid_has_ownership($trying_openid)
{
	global $openid_session;
	if( empty($trying_openid) ) return false;
	if( !isset($openid_session['id']) ) return false;
	if( $trying_openid == $openid_session['id'] ) return true;
	if( isset($openid_session['delegatedid']) && $trying_openid == $openid_session['delegatedid'] ) return true;
	return false;
}

function _openid_fix_table()
{
	global $database;
	$fix1 = false;
	$fix2 = true;
	$fix3 = false;

	$rows = DBQuery::queryAll("DESC {$database['prefix']}OpenIDUsers");
	foreach( $rows as $row ) {
		if( $row['Field'] == 'blocked' )  { $fix1 = true; }
		if( $row['Field'] == 'data' )     { $fix2 = false; }
		if( $row['Field'] == 'nickname' ) { $fix3 = true; }
	}

	if( $fix1 ) {
		DBQuery::execute("alter table {$database['prefix']}OpenIDUsers drop column blocked");
		DBQuery::execute("alter table {$database['prefix']}OpenIDUsers drop column admin");
		DBQuery::execute("alter table {$database['prefix']}OpenIDUsers drop column member");
		DBQuery::execute("alter table {$database['prefix']}OpenIDUsers drop column comment");
	}

	if( $fix2 ) {
		DBQuery::execute("alter table {$database['prefix']}OpenIDUsers add column data text");
	}

	if( $fix3 ) {
		$rows = DBQuery::queryAll("select owner,openid,nickname from {$database['prefix']}OpenIDUsers");
		foreach( $rows as $row ) {
			$owner = $row["owner"];
			$openid = $row["openid"];
			$data = serialize( array( "nickname" => $row["nickname"], "homepage" => $openid ) );
			DBQuery::execute("update {$database['prefix']}OpenIDUsers set data='{$data}' where owner={$owner} and openid='{$openid}'");
		}
		DBQuery::execute("alter table {$database['prefix']}OpenIDUsers drop column nickname");
	}
}

_openid_fix_table();

function openid_comment_add( $id, $comment )
{
	/* Assert $id is numeric by the caller function in lib/model/comment.php */

	global $openid_session;
	global $database, $owner;

	_openid_fix_table();

	$auth_id = _openid_get_auth_id();
	if( $auth_id )
	{ 
		$result = _openid_getCommentAttributes($owner,$id,"name,homepage");
		_openid_update_id( $openid_session['id'], $openid_session['delegatedid'], $result['name'], $result['homepage']);
		openid_session_write();

		DBQuery::execute("UPDATE {$database['prefix']}Comments SET password = '" . OPENID_PASSWORD . "' WHERE owner = $owner and id = $id" );
		DBQuery::execute("DELETE FROM {$database['prefix']}OpenIDComments WHERE owner = $owner and id = $id" );
		DBQuery::execute("INSERT INTO {$database['prefix']}OpenIDComments (owner,id,openid) values " .
			"( {$owner}, {$id}, '{$auth_id}' )");
	}
}

function openid_view_commenter($name, $item)
{
	global $database, $owner;
	global $hostURL, $service, $blogURL;
	global $openid_pluginbase;

	$openid_pluginbase = $hostURL . $service['path'] . "/plugins/" . basename(dirname( __FILE__ ));

	if( $item['secret'] ) {
		return $name;
	}
	$row = DBQuery::queryAll("SELECT * from {$database['prefix']}OpenIDComments WHERE owner = $owner and id = {$item['id']}" );
	return $name . ($row ? "<img src=\"" . $openid_pluginbase . "/openid16x16.gif\" hspace=\"2\" align=\"absmiddle\" title=\"" .
		sprintf( _text("오픈아이디(%s)로 작성하였습니다"), $row[0]['openid'] ) . "\">" : "");
}

function openid_comment_comment()
{
	global $owner, $defaultURL, $blog, $user, $skinSetting;
	global $service, $adminSkinSetting, $blogURL, $pageTitle, $comment, $suri;
	global $openid_session;
	$entryId = $_GET['id'];
	$suri['id'] = $entryId;

	if( !$openid_session['id'] || doesHaveOwnership() || doesHaveMembership() )
	{
		ob_end_clean();
		header("HTTP/1.0 302 Moved Temporarily");
		header("Location: $hostURL$blogURL/comment/comment/$entryId");
		print( "<html><body></body></html>" );
		exit(0);
	}

	$pageTitle = _text('댓글에 댓글 달기') . ": " . _text("로그인한 오픈아이디") . " (" . $openid_session['id'] . ")";
	$comment = array('name' => '', 'password' => '', 'homepage' => 'http://', 'secret' => 0, 'comment' => '');
	require 'openid_replyedit.php';
}

/* Get and rename from original code */
function _openid_getCommentAttributes($owner, $id, $attributeNames) {
	global $database;
	return DBQuery::queryRow("select $attributeNames from {$database['prefix']}Comments where owner = $owner and id = $id");
}

function _openid_getCommentInfo($owner,$id){
	global $database;

	$sql="select a.*, openid from {$database['prefix']}Comments a left join {$database['prefix']}OpenIDComments b on a.id = b.id where a.owner = $owner and a.id = $id";
	if($result=DBQuery::query($sql))
		return mysql_fetch_array($result);
	return false;
}
/* Get and rename from original code */

function openid_comment_del()
{
	global $owner, $defaultURL, $blog, $user, $skinSetting;
	global $service, $adminSkinSetting, $blogURL, $pageTitle, $comment, $suri;
	global $openid_session;

	$openid_id = $openid_session['id'];

	$entryId = $_GET['id'];
	$suri['id'] = $entryId;

	if( !$openid_session['id'] || doesHaveOwnership() || doesHaveMembership() )
	{
		ob_end_clean();
		header("HTTP/1.0 302 Moved Temporarily");
		header("Location: $hostURL$blogURL/comment/delete/$entryId");
		print( "<html><body></body></html>" );
		exit(0);
	}

	list($replier) = _openid_getCommentAttributes($owner, $suri['id'], 'replier');
	$comment = _openid_getCommentInfo($owner, $suri['id']);
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">
	<head>
		<title><?php echo _text('댓글 삭제') ;?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $service['path'] . $adminSkinSetting['skin'];?>/popup-comment.css" />
		<script type="text/javascript">
			//<![CDATA[
				var servicePath = "<?php echo $service['path'];?>";
				var blogURL = "<?php echo $blogURL;?>";
				var adminSkin = "<?php echo $adminSkinSetting['skin'];?>";
			//]]>
		</script>
		<script type="text/javascript" src="<?php echo $service['path'];?>/script/common2.js"></script>
	</head>
	<body><?php echo $pluginURL ?>
		<form name="deleteComment" method="post" action="<?php echo $blogURL;?>/comment/delete/<?php echo $entryId;?>">
			<div id="comment-box">
				<img src="<?php echo $service['path'] . $adminSkinSetting['skin'];?>/image/img_comment_popup_logo.gif" alt="<?php echo _text('텍스트큐브 로고');?>" />	
				<div id="command-box">
<? 
/*-------------------------------------------------------------------------------------------*/
if( ! _openid_has_ownership($comment['openid']) ) { ?>
					<div class="edit-line">
						<label>로그인된 오픈아이디의 권한으로는 수정/삭제가 불가능합니다.</label>
					</div>
					<div class="password-line">
						<input type="button" class="input-button" name="Submit" value="<?php echo _text('닫기');?>" onclick="window.close()" />				
					</div>
<? 
} else { 
	if (!doesHaveOwnership() && (!doesHaveMembership() || ($replier != getUserId())) )
	{
		if( _openid_has_ownership($comment['openid']) ) {
			$tmp_password = _openid_set_temp_password( $owner, $suri['id'] );
		}
		else
		{
			$tmp_password = "";
		}
	}
/*-------------------------------------------------------------------------------------------*/
?>
					<div class="edit-line">
						<input type="radio" id="edit" class="radio" name="mode" value="edit" checked="checked" /><label for="edit"><?php echo _text('댓글을 수정합니다.');?></label>
					</div>
					<div class="delete-line">			
						<input type="radio" id="delete" class="radio" name="mode" value="delete" /><label for="delete"><?php echo _text('댓글을 삭제합니다.');?></label>
					</div>
					<div class="password-line">
	<?php
	if (!doesHaveOwnership() && (!doesHaveMembership() || ($replier != getUserId())) )
	{
		if( !_openid_has_ownership($comment['openid']) ) {
	?>				  
						<label for="password"><?php echo _text('비밀번호');?><span class="divider"> | </span></label><input type="password" id="password" class="input-text" name="password" />
	<?php
		} else {
	?>
						<input type="hidden" id="password" class="input-text" name="password" value="<? echo $tmp_password ?>"/>
	<?
		}
	}
	?>
						<input type="button" class="input-button" name="Submit" value="<?php echo _text('다음');?>" onclick="document.deleteComment.submit()" />				
					</div>
<? } ?>
				</div>
			</div>
		</form>
	</body>
	</html>
<?php
}

function openid_manage()
{
	global $database, $blogURL, $hostURL;

	$menu_url = $hostURL . $blogURL . "/owner/plugin/adminMenu?name=" . $_GET['name'];
	$menu1 = $menu_url . "&amp;mode=1";
	$menu2 = $menu_url . "&amp;mode=3";
	$menu3 = $menu_url . "&amp;mode=5";
	$menu4 = $menu_url . "&amp;mode=7";
	$order = "order by lastLogin desc";

	$mode = preg_replace( '/.*mode=(.+)/', '\1', $_SERVER["QUERY_STRING"] . "mode=7");
	/* last mode=7 will be default */
	switch( $mode )
	{
	case 2:
		$menu2 = $menu_url . "&amp;mode=3"; $order = "order by delegatedid asc";
		break;
	case 3:
		$menu2 = $menu_url . "&amp;mode=2"; $order = "order by delegatedid desc";
		break;
	case 4:
		$menu3 = $menu_url . "&amp;mode=5"; $order = "order by loginCount asc";
		break;
	case 5:
		$menu3 = $menu_url . "&amp;mode=4"; $order = "order by loginCount desc";
		break;
	case 6:
		$menu4 = $menu_url . "&amp;mode=7"; $order = "order by lastLogin asc";
		break;
	case 7:
		$menu4 = $menu_url . "&amp;mode=6"; $order = "order by lastLogin desc";
		break;
	case 0:
		$menu1 = $menu_url . "&amp;mode=1"; $order = "order by openid asc";
		break;
	case 1:
		$menu1 = $menu_url . "&amp;mode=0"; $order = "order by openid desc";
		break;
	}
?>
	<h2 class="caption"><span class="main-text"><?php echo _text('오픈아이디 사용현황')?></span></h2>
	
	<table class="data-inbox" cellspacing="0" cellpadding="0">
		<thead>
			<tr>
				<th class="site"><span class="text"><a href="<?php echo $menu1?>"><?php echo _text('오픈아이디 주소(이름)')?></a></span></th>
				<th class="site"><span class="text"><a href="<?php echo $menu2?>"><?php echo _text('위임주소')?></a></span></th>
				<th class="site"><span class="text"><a href="<?php echo $menu3?>"><?php echo _text('로그인 회수')?></a></span></th>
				<th class="site"><span class="text"><a href="<?php echo $menu4?>"><?php echo _text('마지막 로그인')?></a></span></th>
			</tr>
		</thead>
		<tbody>
<?php
$sql="select * from {$database['prefix']}OpenIDUsers $order";
$rec = DBQuery::queryAll( $sql );
for ($i=0; $i<count($rec); $i++) {
$record = $rec[$i];
$data = unserialize($record['data']);
$nickname = "({$data['nickname']})";

$className = ($i % 2) == 1 ? 'even-line' : 'odd-line';
$className .= ($i == sizeof($rec) - 1) ? ' last-line' : '';
?>
			<tr class="<?php echo $className;?> inactive-class" onmouseover="rolloverClass(this, 'over')" onmouseout="rolloverClass(this, 'out')">
				<td><?php echo "{$record['openid']} {$nickname}";?></td>
				<td><?php echo $record['delegatedid'];?></td>
				<td><?php echo $record['loginCount'];?></td>
				<td><?php echo Timestamp::format5($record['lastLogin']);?></td>
			</tr>
<?php
}
?>
		</tbody>
	</table>
<?
}

?>
