<?php
session_cache_limiter(false);
session_start();
error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );

define( 'ABSPATH', dirname( __FILE__ ) );
require ABSPATH.'/config.php';
require ABSPATH.'/inc/db.php';
$db = new wpdb( $db_config['user'], $db_config['pass'], $db_config['name'], $db_config['host'] );
require ABSPATH.'/inc/functions.php';
require ABSPATH.'/inc/patch.php';
require ABSPATH.'/inc/user.php';

/*
if($_KSITE['multilanguage']){
	if(!isset($_SESSION['LANG'])||$_SESSION['LANG']==".") $_SESSION['LANG']=$_KSITE['default_language'];
	require($_SESSION['LANG']."/lang.php");
}
*/

//require ABSPATH.'/common.php';
//require("constants.php");
//require("kitems.php");

gobj_setup( $config );
set_current_user();

$mod = preg_replace('/[^a-z0-9]/','',_get('mod'));
if( ! $mod ) $mod = 'gobj';

include ABSPATH.'/modules/'.$mod.'.php';
if( ! user_logged() ) tpl_load("gobj_login.html",true,true);
elseif( is_action('print') ) tpl_load("index_empty.html",true,true);
else tpl_load('gobj_index.html',true,true);

function kobj_ifloggedin(){
	if(isset($_SESSION['auid'])) tpl_block("kobj_ifloggedin",true,true);
	else tpl_block("kobj_not_ifloggedin",true,true);
}

