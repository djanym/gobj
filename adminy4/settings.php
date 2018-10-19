<?php
/**
 * This file initiates settings for admin part.
 * Difference can be in error display seetings, php options, etc.
 */

session_cache_limiter(false);
session_start();
set_time_limit(30);
error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );

define( 'IS_ADMIN', true );
define( 'DEBUG_MODE', true );
// TODO: ERROR_DISPLAY, LOG ERRORS
define( 'ERROR_DISPLAY', true );
define( 'LOG_ERRORS', false );
define( 'ABSPATH', dirname( __FILE__ ) . '/' );
define( 'INC_FOLDER', 'gobj.inc.8837' );
define( 'INC_ABSPATH', ABSPATH . '../' . INC_FOLDER . '/' );

require INC_ABSPATH.'config.php';

require INC_ABSPATH.'core.functions.php';
// TODO: include required files for enabled modules from config
require INC_ABSPATH.'dummy.functions.php';
require INC_ABSPATH.'db.php';
// Create main DB connection object
$db = new db( DB_USER, DB_PASS, DB_NAME, DB_HOST );

require INC_ABSPATH.'functions.php';
require INC_ABSPATH.'templates.php';
//require ABSPATH.'/inc/patch.php';
require INC_ABSPATH.'user.php';

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
init_current_user();
