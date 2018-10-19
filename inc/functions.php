<?php

/*** Formatting functions ***/

function strip($var, $html = false) {
	if (is_array($var)){
		foreach( $var as $k => $v ) $var[$k] = strip($v, $html);
	}
	elseif (is_object($var)) {
		$class_vars = get_class_vars(get_class($var));
		foreach ($class_vars as $k => $v)
			$var->$k = strip($v, $html);
	}
	else {
		$var = stripslashes($var);
		if ($html)
			$var = htmlspecialchars($var);
	}
	return $var;
}

/************ DB *************/

function db(){
	return $GLOBALS['db'];
}

function esc_sql( $sql ) {
	return db()->escape( $sql );
}

/************* Formatting **************/

function esc_attr($text){
	$safe_text = check_invalid_utf8($text);
	$safe_text = _wp_specialchars($safe_text, ENT_QUOTES);
	return $safe_text;
}

function _wp_specialchars( $string, $quote_style = ENT_NOQUOTES, $charset = false, $double_encode = false ) {
	$string = (string) $string;

	if ( 0 === strlen( $string ) )
		return '';

	// Don't bother if there are no specialchars - saves some processing
	if ( ! preg_match( '/[&<>"\']/', $string ) )
		return $string;

	// Account for the previous behaviour of the function when the $quote_style is not an accepted value
	if ( empty( $quote_style ) )
		$quote_style = ENT_NOQUOTES;
	elseif ( ! in_array( $quote_style, array( 0, 2, 3, 'single', 'double' ), true ) )
		$quote_style = ENT_QUOTES;

	$charset = 'UTF-8';

	$_quote_style = $quote_style;

	if ( $quote_style === 'double' ) {
		$quote_style = ENT_COMPAT;
		$_quote_style = ENT_COMPAT;
	} elseif ( $quote_style === 'single' ) {
		$quote_style = ENT_NOQUOTES;
	}

	// Handle double encoding ourselves
	if ( $double_encode ) {
		$string = @htmlspecialchars( $string, $quote_style, $charset );
	} else {
		// Decode &amp; into &
		$string = wp_specialchars_decode( $string, $_quote_style );

		// Guarantee every &entity; is valid or re-encode the &
		$string = wp_kses_normalize_entities( $string );

		// Now re-encode everything except &entity;
		$string = preg_split( '/(&#?x?[0-9a-z]+;)/i', $string, -1, PREG_SPLIT_DELIM_CAPTURE );

		for ( $i = 0; $i < count( $string ); $i += 2 )
			$string[$i] = @htmlspecialchars( $string[$i], $quote_style, $charset );

		$string = implode( '', $string );
	}

	// Backwards compatibility
	if ( 'single' === $_quote_style )
		$string = str_replace( "'", '&#039;', $string );

	return $string;
}

function wp_kses_normalize_entities($string) {
	# Disarm all entities by converting & to &amp;

	$string = str_replace('&', '&amp;', $string);

	# Change back the allowed entities in our entity whitelist

	$string = preg_replace_callback('/&amp;([A-Za-z]{2,8});/', 'wp_kses_named_entities', $string);
	$string = preg_replace_callback('/&amp;#(0*[0-9]{1,7});/', 'wp_kses_normalize_entities2', $string);
	$string = preg_replace_callback('/&amp;#[Xx](0*[0-9A-Fa-f]{1,6});/', 'wp_kses_normalize_entities3', $string);

	return $string;
}

/**
 * Callback for wp_kses_normalize_entities() regular expression.
 *
 * This function only accepts valid named entity references, which are finite,
 * case-sensitive, and highly scrutinized by HTML and XML validators.
 *
 * @since 3.0.0
 *
 * @param array $matches preg_replace_callback() matches array
 * @return string Correctly encoded entity
 */
function wp_kses_named_entities($matches) {
	global $allowedentitynames;

	if ( empty($matches[1]) )
		return '';

	$i = $matches[1];
	return ( ( ! in_array($i, $allowedentitynames) ) ? "&amp;$i;" : "&$i;" );
}

/**
 * Callback for wp_kses_normalize_entities() regular expression.
 *
 * This function helps wp_kses_normalize_entities() to only accept 16-bit values
 * and nothing more for &#number; entities.
 *
 * @access private
 * @since 1.0.0
 *
 * @param array $matches preg_replace_callback() matches array
 * @return string Correctly encoded entity
 */
function wp_kses_normalize_entities2($matches) {
	if ( empty($matches[1]) )
		return '';

	$i = $matches[1];
	if (valid_unicode($i)) {
		$i = str_pad(ltrim($i,'0'), 3, '0', STR_PAD_LEFT);
		$i = "&#$i;";
	} else {
		$i = "&amp;#$i;";
	}

	return $i;
}

/**
 * Callback for wp_kses_normalize_entities() for regular expression.
 *
 * This function helps wp_kses_normalize_entities() to only accept valid Unicode
 * numeric entities in hex form.
 *
 * @access private
 *
 * @param array $matches preg_replace_callback() matches array
 * @return string Correctly encoded entity
 */
function wp_kses_normalize_entities3($matches) {
	if ( empty($matches[1]) )
		return '';

	$hexchars = $matches[1];
	return ( ( ! valid_unicode(hexdec($hexchars)) ) ? "&amp;#x$hexchars;" : '&#x'.ltrim($hexchars,'0').';' );
}

/**
 * Helper function to determine if a Unicode value is valid.
 *
 * @param int $i Unicode value
 * @return bool True if the value was a valid Unicode number
 */
function valid_unicode($i) {
	return ( $i == 0x9 || $i == 0xa || $i == 0xd ||
			($i >= 0x20 && $i <= 0xd7ff) ||
			($i >= 0xe000 && $i <= 0xfffd) ||
			($i >= 0x10000 && $i <= 0x10ffff) );
}

/**
 * Convert all entities to their character counterparts.
 *
 * This function decodes numeric HTML entities (&#65; and &#x41;). It doesn't do
 * anything with other entities like &auml;, but we don't need them in the URL
 * protocol whitelisting system anyway.
 *
 * @since 1.0.0
 *
 * @param string $string Content to change entities
 * @return string Content after decoded entities
 */
function wp_kses_decode_entities($string) {
	$string = preg_replace_callback('/&#([0-9]+);/', '_wp_kses_decode_entities_chr', $string);
	$string = preg_replace_callback('/&#[Xx]([0-9A-Fa-f]+);/', '_wp_kses_decode_entities_chr_hexdec', $string);

	return $string;
}

/**
 * Regex callback for wp_kses_decode_entities()
 *
 * @param array $match preg match
 * @return string
 */
function _wp_kses_decode_entities_chr( $match ) {
	return chr( $match[1] );
}

/**
 * Regex callback for wp_kses_decode_entities()
 *
 * @param array $match preg match
 * @return string
 */
function _wp_kses_decode_entities_chr_hexdec( $match ) {
	return chr( hexdec( $match[1] ) );
}

function check_invalid_utf8( $string, $strip = false ) {
	$string = (string) $string;

	if ( 0 === strlen( $string ) ) {
		return '';
	}

	// Check for support for utf8 in the installed PCRE library once and store the result in a static
	static $utf8_pcre;
	if ( !isset( $utf8_pcre ) ) {
		$utf8_pcre = @preg_match( '/^./u', 'a' );
	}
	// We can't demand utf8 in the PCRE installation, so just return the string in those cases
	if ( !$utf8_pcre ) {
		return $string;
	}

	// preg_match fails when it encounters invalid UTF8 in $string
	if ( 1 === @preg_match( '/^./us', $string ) ) {
		return $string;
	}

	// Attempt to strip the bad chars if requested (not recommended)
	if ( $strip && function_exists( 'iconv' ) ) {
		return iconv( 'utf-8', 'utf-8', $string );
	}

	return '';
}


/**
 * Set the mbstring internal encoding to a binary safe encoding when func_overload
 * is enabled.
 *
 * When mbstring.func_overload is in use for multi-byte encodings, the results from
 * strlen() and similar functions respect the utf8 characters, causing binary data
 * to return incorrect lengths.
 *
 * This function overrides the mbstring encoding to a binary-safe encoding, and
 * resets it to the users expected encoding afterwards through the
 * `reset_mbstring_encoding` function.
 *
 * It is safe to recursively call this function, however each
 * `mbstring_binary_safe_encoding()` call must be followed up with an equal number
 * of `reset_mbstring_encoding()` calls.
 *
 * @since 3.7.0
 *
 * @see reset_mbstring_encoding()
 *
 * @staticvar array $encodings
 * @staticvar bool  $overloaded
 *
 * @param bool $reset Optional. Whether to reset the encoding back to a previously-set encoding.
 *                    Default false.
 */
function mbstring_binary_safe_encoding( $reset = false ) {
	static $encodings = array();
	static $overloaded = null;

	if ( is_null( $overloaded ) )
		$overloaded = function_exists( 'mb_internal_encoding' ) && ( ini_get( 'mbstring.func_overload' ) & 2 );

	if ( false === $overloaded )
		return;

	if ( ! $reset ) {
		$encoding = mb_internal_encoding();
		array_push( $encodings, $encoding );
		mb_internal_encoding( 'ISO-8859-1' );
	}

	if ( $reset && $encodings ) {
		$encoding = array_pop( $encodings );
		mb_internal_encoding( $encoding );
	}
}

/**
 * Reset the mbstring internal encoding to a users previously set encoding.
 *
 * @see mbstring_binary_safe_encoding()
 *
 * @since 3.7.0
 */
function reset_mbstring_encoding() {
	mbstring_binary_safe_encoding( true );
}

/********* Helpers ***********/

function is_unique($table_name, $table_field, $value, $current_id = null){
	if( db()->get_var("SELECT COUNT(*)FROM `".esc_sql($table_name)."` "
					. "WHERE `".esc_sql($table_field)."` = '".esc_sql($value)."' "
					. ( $current_id ? " AND `id` != '".(int)$current_id."' " : '') ) ){
		return false;
	}
	return true;
}

function is_error( $thing ) {
	return ( $thing instanceof Error );
}

/*
 * Generate unique key around the table
 */
function generate_unique_key( $len, $table, $field, $args = null ){
	$defaults = array(
			'special_chars' => false,
			'lowercase_only' => false,
			'numbers_only' => false,
			'prefix' => '',
			'suffix' => ''
	);
	if( ! $args )
			$args = '';
	$params = wp_parse_args($args, $defaults);
	extract($params);
	$key = $prefix . generate_string( $len, $special_chars, false, $lowercase_only, $numbers_only ) . $suffix;
	while( db()->get_var("SELECT COUNT(*)FROM `".$table."` WHERE `".$field."` = '".esc_sql($key)."' ") ){
		$key = $prefix . generate_string( $len, $special_chars, false, $lowercase_only, $numbers_only ) . $suffix;
	}
	return $key;
}

function generate_string( $length = 12, $special_chars = true, $extra_special_chars = false, $lowercase_only = false, $numbers_only = false ) {
	$chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
	if ( ! $lowercase_only )
		$chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	if ( $special_chars )
		$chars .= '!@#$%^&*()';
	if ( $extra_special_chars )
		$chars .= '-_ []{}<>~`+=,.;:/?|';
	if( $numbers_only )
		$chars = '0123456789';

	$string = '';
	for ( $i = 0; $i < $length; $i++ ) {
		$string .= substr($chars, randy(0, strlen($chars) - 1), 1);
	}

	// random_password filter was previously in random_password function which was deprecated
	return $string;
}

function randy( $min = 0, $max = 0 ) {
	global $rnd_value;

	if ( strlen($rnd_value) < 8 ) {
		$seed_std = mt_rand();
		$rnd_value = md5( uniqid(microtime() . $seed_std, true ) . $seed_std );
		$rnd_value .= sha1($rnd_value);
		$rnd_value .= sha1($rnd_value . $seed_std);
		$seed = md5($seed . $rnd_value);
	}
	// Take the first 8 digits for our value
	$value = abs(hexdec( substr($rnd_value, 0, 8) ));
	// Strip the first eight, leaving the remainder for the next call to wp_rand().
	$rnd_value = substr($rnd_value, 8);
	// Some misconfigured 32bit environments (Entropy PHP, for example) truncate integers larger than PHP_INT_MAX to PHP_INT_MAX rather than overflowing them to floats.
	$max_random_number = 3000000000 === 2147483647 ? (float) "4294967295" : 4294967295; // 4294967295 = 0xffffffff

	// Reduce the value to be within the min - max range
	if ( $max != 0 )
		$value = $min + ( $max - $min + 1 ) * $value / ( $max_random_number + 1 );

	return abs(intval($value));
}

/************************/



function redirect($url, $status = 302 ){
	header("Location: $url", true, $status);
	die;
}

function get_current_page(){
	return (int)_postget('page') > 1 ? (int)_postget('page') : 1;
}

function get_current_url_params_array(){
	parse_str($_SERVER['QUERY_STRING'], $output);
	return $output;
}

function get_current_url($with_args = true, $use_forwarded_host = false) {
	$ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' );
	$sp = strtolower($_SERVER['SERVER_PROTOCOL']);
	$protocol = substr($sp, 0, strpos($sp, '/')) . ( ( $ssl ) ? 's' : '' );
	$port = $_SERVER['SERVER_PORT'];
	$port = ( (!$ssl && $port == '80' ) || ( $ssl && $port == '443' ) ) ? '' : ':' . $port;
	$host = ( $use_forwarded_host && isset($_SERVER['HTTP_X_FORWARDED_HOST']) ) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : ( isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null );
	// This code will fail if the server is given by IPv6 IP address. To fix that, replace SERVER_NAME with HTTP_HOST
	$host = isset($host) ? $host : $_SERVER['SERVER_NAME'] . $port;
	$url = $protocol . '://' . $host;
	$url .= $_SERVER['REQUEST_URI'];
	if( $with_args && $_SERVER['QUERY_STRING'] ) $url .= '?' . $_SERVER['QUERY_STRING'];
	return $url;
}

function destroy_cookie( $name ){
	$_COOKIE[ $name ] = '';
	unset($_COOKIE[ $name ]);
	setcookie( $name, '', time() - ( 3600*24 ), '/', COOKIE_DOMAIN );
	setcookie( $name, '', time() - ( 3600*24 ), '/' );
	setcookie( $name, '', time() - ( 3600*24 ) );
}

/**************/


function mydateformat($type,$df,$mktime=""){
	if($type=='calendar'){
		if($df=="") return "%d/%m/%Y";
		else return str_replace(array("d","m","Y"),array("%d","%m","%Y"),$df);
	}
	if($type=='mktime'){
		if($df==""||$df=="d/m/Y"){
			$a=explode("/",$mktime);
			$ret=mktime(0,0,0,$a[1],$a[0],$a[2]);
			if($ret<0) $ret=0;
			return $ret;
		}
		if($df=="m/d/Y"){
			$a=explode("/",$mktime);
			$ret=mktime(0,0,0,$a[0],$a[1],$a[2]);
			if($ret<0) $ret=0;
			return $ret;
		}
	}
}

function mydatetimeformat($type,$df,$mktime=""){
	if($type=='calendar'){
		if($df=="") return "%d/%m/%Y %H:%M";
		else return str_replace(array("d","m","Y","H","i"),array("%d","%m","%Y","%H","%M"),$df);
	}
	if($type=='mktime'){
		if($df==""||$df=="d/m/Y H:i"){
			$b=explode(" ",$mktime);
			$a=explode("/",$b[0]);
			$b=explode(":",$b[1]);
			$ret=mktime($b[0],$b[1],0,$a[1],$a[0],$a[2]);
			if($ret<0) $ret=0;
			return $ret;
		}
		if($df=="m/d/Y H:i"){
			$b=explode(" ",$mktime);
			$a=explode("/",$b[0]);
			$b=explode(":",$b[1]);
			$ret=mktime($b[0],$b[1],0,$a[0],$a[1],$a[2]);
			if($ret<0) $ret=0;
			return $ret;
		}
	}
}

function get_option ($var) {
	return sqlr("SELECT `varvalue` FROM `options` WHERE `varname`='".$var."'");
}

function CheckItem($table, $field, $name, $idp = 0, $id = 0) {
	if( $id == 0 ) $id='';
	else $id = " AND id != '".intval($id)."'";
	if( $idp == 0 ) $idp='';
	else $idp = " AND idp = '".intval($idp)."'";
	$q = sqlr("SELECT COUNT(*)FROM `".$table."` WHERE `".$field."` = '".addslashes($name)."' ".$idp." ".$id." ");
	if( $q < 1 ) return false;
	else return true;
}

function GetInfo($table,$field,$id,$column='id') {
	$q=sqla("SELECT*FROM `".$table."` WHERE ".$column."='".addslashes($id)."' ");
	return $q[$field];
}

function GetAllDays($sel=0,$def=0) {
	if( $def != 0 ) $out='<option value=0>select</option>';
	else $out='';
	if( $sel == 0 && $def == 0 ) $sel = date("d",time() );
	for($x=1;$x<32;$x++) {
		if( $sel==$x ) $selected='SELECTED';
		else $selected='';
	$out.='<option value="'.$x.'" '.$selected.'>'.$x.'</option>';
  }
  return $out;
}

function GetAllMonths($sel=0,$t="m",$def=0) {
	if( $def != 0 ) $out='<option value=0>select</option>';
	else $out='';
	if( $sel == 0 && $def == 0 ) $sel = date("m",time() );
	for($x=1;$x<=12;$x++) {
		if( $sel==$x ) $selected='SELECTED';
		else $selected='';
	$out.='<option value="'.$x.'" '.$selected.'>'.date($t,mktime(10,10,10,$x,1,2000) ).'</option>';
  }
  return $out;
}

function GetAllYears($from,$to,$sel=0,$def=0) {
	if( $def != 0 ) $out='<option value=0>select</option>';
	else $out='';
	if( $sel == 0 && $def == 0 ) $sel = date("Y",time() );
	for($x=$from;$x<=$to;$x++) {
		if( $sel==$x ) $selected='SELECTED';
		else $selected='';
	$out.='<option value="'.$x.'" '.$selected.'>'.$x.'</option>';
  }
  return $out;
}

