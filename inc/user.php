<?php

function do_logout(){
	destroy_cookie( AUTH_COOKIE );
	redirect( admin_siteurl() );
}

function do_login( $username, $pass, $remember = false ) {
	// TODO: Autologin
	if( _conf('multiuser') ) {
		return new Error('Not working');
			// Multiuser mode
			// TODO
//		if ( ! compare_hashed_password($pass, $user->pass) )
//			return new eError('username', sprintf(__('Invalid username or password. <a href="%s" title="Password Lost and Found">Lost your password</a>?'), siteurl('restore') ));
//	if( $user->active == 0 )
//			return new Error('general', __('<strong>ERROR</strong>: Your account is not active.'));
//	if( $user->active == 2 )
//			return new Error('general', __('<strong>ERROR</strong>: Your account has been suspended.'));
		$ro = sqlq("select * from users where ulogin='" . addsl($_POST['ulogin']) . "' and upass='" . addsl($_POST['upass']) . "'");
		if (mysql_num_rows($ro) > 0) {
			$r = mysql_fetch_assoc($ro);
			$_SESSION['auid'] = $r['id'];
			$_SESSION['userlevel'] = $r['ulevel'];
			header("Location: " . ($GLOBALS['_KSITE']['login_url'] ? $GLOBALS['_KSITE']['login_url'] : "index.php"));
			die();
		} else
			$GLOBALS['err'] = $GLOBALS['_LANG']['kobj_err_login'] ? $GLOBALS['_LANG']['kobj_err_login'] : "Wrong login or password";
	}
	else{
		// Singleuser mode
		if( $username !== _conf('username') || $pass !== _conf('password') ){
			return new Error('Wrong username or password');
		}
	}
	
	if ( $remember ) {
		$expiration = $expire = time() + ( 3600 * 24 * 7 );
	} else {
		$expiration = time() + ( 3600 * 24 );
		$expire = 0;
	}

	$auth_cookie_name = AUTH_COOKIE;
	$user = $username;
	$pass_frag = substr($password, 8, 4);
	$key = hash_hmac('md5', $username . $pass_frag . '|' . $expiration, AUTH_SALT);
	$hash = hash_hmac('md5', $username . '|' . $expiration, $key);
	$auth_cookie = $username . '|' . $expiration . '|' . $hash;
	setcookie($auth_cookie_name, $auth_cookie, $expiration);
	
	return $user;
}

function try_login(){
	$username	= _post('username');
	$pass	= _post('password');
		
	$errors = array();
	
	// Check the username
	if( (string)$username === '' ) {
		$errors['username'] = 'Enter your username.';
	}
	// Check the password
	if( (string)$pass === '' ) {
		$errors['password'] = 'Please type your password.';
	}
	
	if( $errors ){
		json_error( $errors );
	}
	
	$user = do_login( $username, $pass );

	if($user instanceof Error) {
		json_error('Wrong username or password.');
	}
	
	$res['success'] = 1;
	$res['redirect_url'] = admin_siteurl();
	json($res);
}

function user_logged(){
	global $current_user;
	if( $current_user )
			return true;
	else
			return false;
}
	
function CheckEmail($email) {
	if ( eregi("[a-zA-Z0-9_-]+@[a-z0-9_-]+\.[a-z]{2,5}", $email) ) return true;
	else return false;
}

function CheckUser($table) {
	if( $_SESSION['user_name'] ) {
		$query=sqlr("SELECT COUNT(*)FROM `".addslashes($table)."` WHERE md5(ulogin) = '".addslashes($_SESSION['user_name'])."' AND md5(upass) = '".addslashes($_SESSION['user_pass'])."' AND id = '".addslashes($_SESSION['user_id'])."' AND md5(uid) = '".addslashes($_SESSION['user_uid'])."' ");
		if( $query == 1 ) return true;
		else false;
		}
	else return false;
}

function set_current_user(){
	global $current_user;
	$cookie = $_COOKIE[ AUTH_COOKIE ];
	if( empty($cookie) )
			return false;

	$cookie_elements = explode('|', $cookie);
	if ( count($cookie_elements) != 3 )
			return false;

	list($username, $expiration, $hmac) = $cookie_elements;

	// Quick check to see if an honest cookie has expired
	if ( $expiration < time() ) {
		destroy_cookie( AUTH_COOKIE );
		return false;
	}

	if( _conf('multiuser') ) {
	} else {
		if( $username === _conf('username') ){
			$real_username = $username;
			$real_password = _conf('password');
			$user_id = $username;
		}
	}
		
	if( ! $real_username ) {
		destroy_cookie( AUTH_COOKIE );
		return false;
	}

	$pass_frag = substr($real_password, 8, 4);
	$key = hash_hmac('md5', $real_username . $pass_frag . '|' . $expiration, AUTH_SALT);
	$hash = hash_hmac('md5', $real_username . '|' . $expiration, $key);

	if ( $hmac != $hash ) {
		destroy_cookie( AUTH_COOKIE );
		return false;
	}

	$current_user = $user_id;
	return true;
}
