<?php

function _conf($var){
	global $gobj_config;
	return $gobj_config[$var];
}

function _get($var){
	return filter_input(INPUT_GET, $var);
}

function _post($var){
	return filter_input(INPUT_POST, $var);
}

function _postget($var){
	return _post($var) ? _post($var) : _get($var);
}

function _sess($var){
	return $_SESSION($var);
}

function is_action($action){
	return _postget('act') == $action ? true : false;
}

function siteurl(){
    return SITEURL;
}

function admin_siteurl(){
    return ADMIN_SITEURL;
}

function get_sort_field(){
	$iconf = gobj_get_current_item_config();
	if( _get('sort_by') ){
		$order_key = _get('sort_by');
	}
	elseif( is_array($iconf['if_sorting']) && $iconf['if_sorting']['default_field'] ){
		$order_key = $iconf['if_sorting']['default_field'];
	}
	if( $order_key ){
		if( array_key_exists( $order_key, $iconf['fields'] ) && ! $iconf[ $order_key ]['unsortable'] ){
			return $order_key;
		}
	}
	return false;
}

function get_sort_direction(){
	$order_desc = false;
	$iconf = gobj_get_current_item_config();
	if( _get('sort_by') ){
		$order_desc = _get('sort_desc') ? true : false;
	}
	elseif( is_array($iconf['if_sorting']) && $iconf['if_sorting']['default_field_desc'] ){
		$order_desc = true;
	}
	return $order_desc ? 'desc' : 'asc';
}

function db(){
	return $GLOBALS['db'];
}

function esc_sql( $sql ) {
	return db()->escape( $sql );
}

function json( $data ){
    $msg = json_encode( $data );
    header('Content-type: application/json');
    echo $msg;
    die;
}

function json_error( $msg, $params = '' ){
    $data = array('error' => true);
    if (is_array($msg))
        $data['errors'] = $msg;
    else
        $data['msg'] = $msg;
    if ($params)
        $data = array_merge($data, $params);
    json($data);
}
