<?php

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

