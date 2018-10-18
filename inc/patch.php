<?php
function debug_log($s){
	$f=fopen('debug.log','a');
	fwrite($f,$s."\r\n");
	fclose($f);
}

if(get_magic_quotes_gpc()){
	foreach($_GET as $k=>$v) $_GET[$k]=strip($v);
	foreach($_POST as $k=>$v) $_POST[$k]=strip($v);
	foreach($_REQUEST as $k=>$v) $_REQUEST[$k]=strip($v);
	foreach($_COOKIE as $k=>$v) $_COOKIE[$k]=strip($v);
}

if(is_array($_POST)) foreach($_POST as $k=>$v) unset($$k);
if(is_array($_GET)) foreach($_GET as $k=>$v) unset($$k);
if(is_array($_REQUEST)) foreach($_REQUEST as $k=>$v) unset($$k);
if(is_array($_SESSION)) foreach($_SESSION as $k=>$v) unset($$k);
if(is_array($_COOKIE)) foreach($_COOKIE as $k=>$v) unset($$k);

db()->query('SET NAMES UTF8');
db()->query('SET CHARACTER SET UTF8');
db()->query("SET collation_connection='utf8_general_ci'");
