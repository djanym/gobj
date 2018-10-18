<?php

function gobj_setup($config){
	global $gobj_config;
	$gobj_config = $config;
	gobj_set_tpl_var('siteurl', admin_siteurl());
	gobj_set_tpl_var('sitename', $gobj_config['sitename']);
	
	// Set default values for items.
	foreach($gobj_config['items'] as $item_key => $item_conf){
		// Set values from default config
		if( is_array($gobj_config['item_default_config'])){
			$gobj_config['items'][$item_key] = array_merge($gobj_config['item_default_config'], $gobj_config['items'][$item_key]);
		}

		if(isset($item_conf['deftitle'])){
			$title = ucfirst($item_conf['deftitle']);
			$titles['titles'] = array(
					'list' => $title . (substr($title, -1) === 's' ? "'" : '') . 's List',
					'edit' => "Edit " . $title,
					'add' => "Add " . $title,
					'view' => "View " . $title
			);
			$gobj_config['items'][$item_key] = array_merge($titles, $gobj_config['items'][$item_key]);
		}
	}
//	print_r($gobj_config); die;
}

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

function __($str){
	return $str;
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

//=== kTPL functions =============================================

/*
 * $p - ?
 * $e - if true, then echo the output
 * $vars - variables to use in template file
 */
function tpl_load($f,$p=true,$e=false,$vars = array()){
	if( $vars ) extract($vars);
	$f = 'templates/' . $f;
	if(!file_exists($f)) die('<font color=red><b>Template processing error:</b> template file "'.$f.'" doesn\'t exist!</font>');
	$c = join('',file($f));
	$c = str_replace('$','&#0036;',$c);
	// Strip subs runs as separate function.
	$c = tpl_strip_subs($c);
	if($p) $c = tpl_process($c, $vars);
	if($e) echo(str_replace('&#0036;','$',$c));
	return str_replace('&#0036;','$',$c);
}

/*
 * Process sub block
 */
function tpl_process_sub($subname, $vars = array(), $process = true, $echo = true){
	$c = gobj_get_sub($subname);
	if(!$c) return null;
	if($process) $c = tpl_process($c, $vars);
	if($echo) echo($c);
	return $c;
}

/*
 * Collect sub blocks from template
 */
function tpl_strip_subs($c){
	global $tpl_SUBS,$tpl_SUBS_li,$tpl_SUBS_nli,$tpl_SUBS_box,$tpl_SUBS_box_nobox;
//	if(!isset($tpl_SUBS)) $tpl_SUBS=Array();
//	while(preg_match('/% *SUB *([^% ]+) *%(.*)% *ENDSUB *\\1 *%/Ums',$c,$m)){
//		$m[2]=tpl_strip_subs($m[2]);
//		$tpl_SUBS[$m[1]]=$m[2];
//		$c=str_replace($m[0],'%{'.$m[1].'()}%',$c);
//	}
	$c = preg_replace_callback('/%SUB ([\d\w_]+)%(.+)%ENDSUB \\1%/Ums',function($matches){
		$content = tpl_strip_subs($matches[2]);
		gobj_set_sub($matches[1],$content);
		// Add 
		return '%='.$matches[1].'()%';
	},$c);
	
	// ?
	if(!isset($tpl_SUBS_box)) $tpl_SUBS_box=Array();
	while(preg_match('/% *BOX *([^% ]+) *%(.*)% *ENDBOX *\\1 *%/Ums',$c,$m)){
		$m[2]=tpl_strip_subs($m[2]);
		$tpl_SUBS_box[$m[1]]=$m[2];
		$c=str_replace($m[0],'%IFBOX '.$m[1].'%',$c);
	}

	// ?
	if(!isset($tpl_SUBS_box_nobox)) $tpl_SUBS_box_nobox=Array();
	while(preg_match('/% *NOBOX *([^% ]+) *%(.*)% *ENDNOBOX *\\1 *%/Ums',$c,$m)){
		$m[2]=tpl_strip_subs($m[2]);
		$tpl_SUBS_box_nobox[$m[1]]=$m[2];
		$c=str_replace($m[0],'',$c);
	}

	if(!isset($tpl_SUBS_li)) $tpl_SUBS_li=Array();
	while(preg_match('/% *IFL *%(.*)% *ENDIFL *%/Ums',$c,$m)){
		$m[1]=tpl_strip_subs($m[1]);
		$tpl_SUBS_li[sizeof($tpl_SUBS_li)]=$m[1];
		$c=str_replace($m[0],'%{tpl_ifli(0,'.(sizeof($tpl_SUBS_li)-1).')}%',$c);
	}

	if(!isset($tpl_SUBS_nli)) $tpl_SUBS_nli=Array();
	while(preg_match('/% *IFNL *%(.*)% *ENDIFNL *%/Ums',$c,$m)){
		$m[1]=tpl_strip_subs($m[1]);
		$tpl_SUBS_nli[sizeof($tpl_SUBS_nli)]=$m[1];
		$c=str_replace($m[0],'%{tpl_ifli(1,'.(sizeof($tpl_SUBS_nli)-1).')}%',$c);
	}

	return $c;
}

function tpl_ifli($t,$n){
	$t=$t?'n':'';
	global ${'tpl_SUBS_'.$t.'li'};
	if(($t==''&&$_SESSION['uid'])||($t=='n'&&!$_SESSION['uid'])) return tpl_process(${'tpl_SUBS_'.$t.'li'}[$n]);
	else return "";
}

function tpl_process($c, $vars = array() ){
	global $_LANG;
	global $gobj_tpl_vars;
	$vars = array_merge($gobj_tpl_vars, $vars);
	
	//VARIABLES before FUNCTIONS - ? why we need this
	// var syntax = %!=varname%
	while(preg_match('/% *! *= *([^%]+) *%/',$c,$__m)){
		$arn=preg_replace('/\[[^\]]*\]/Usmi','',$__m[1]);
		eval('global $'.$arn.'; $rv=$'.$__m[1].';');
		$rv = str_replace('$','&#0036;',$rv);
		$c=preg_replace('/'.str_replace('/','\/',preg_quote($__m[0])).'/',tpl_process($rv),$c,1);
	}

	//BOXES - ? why we need this
  ob_start();
	while(preg_match('/% *IFBOX *([^\}]+) *%/',$c,$m)){
		$old=ob_get_contents();
		ob_end_clean();
		ob_start();
		if(function_exists($m[1]))	$c2=eval('return '.$m[1].'();');
		else $c2='';
		$c1=ob_get_contents();
		ob_end_clean();
		$c2.=$c1;
		if($c2) $c=preg_replace(
			'/'.str_replace('/','\/',preg_quote($m[0])).'/',
			str_replace('%{'.$m[1].'()}%',str_replace('$','&#0036;',tpl_process($c2)),$GLOBALS['tpl_SUBS_box'][$m[1]]),
			$c,1);
		else $c=preg_replace(
			'/'.str_replace('/','\/',preg_quote($m[0])).'/',
			str_replace('%{'.$m[1].'()}%',str_replace('$','&#0036;',tpl_process($c2)),$GLOBALS['tpl_SUBS_box_nobox'][$m[1]]),
			$c,1);
		ob_start();
		echo($old);
	}
  ob_end_clean();

	// FUNCTIONS
	// syntax = %=function_name()%
	$c = preg_replace_callback('/%=([\d\w_]+)\(\)%/',function($matches){
		if(function_exists($matches[1])){
			ob_start();
			call_user_func($matches[1]);
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}
		else
			return ''; // returns empty string
//			return $matches[0]; // return same tpl tag
	},$c);

	// IF
	// syntax = %IF varname% ... %ELSE varname% - optional ... %ENDIF varname%
	$c = preg_replace_callback('/%IF ([\d\w_]+)%(.+)%ENDIF \\1%/Ums',function($matches) use ($vars){
		$key = $matches[1];
		$if_content = $matches[2];
		// Search for ELSEIF case and if found then split IF content from ELSEIF
		preg_match('/(.+?)((?:%ELSEIF [\d\w_]+%|%ELSE '.$key.'%).+)/sm', $if_content, $elseif);
		if($elseif[1]){
			$if_content = $elseif[1];
			$elseif_content = $elseif[2];
		}
//		print_r($matches);
//		var_dump( (bool)$is_else);
//		if( $is_else ) die;
		if( is_array($vars) && array_key_exists($key, $vars) && $vars[ $key ] ){
			return tpl_process( $if_content, $vars, true, false );
		}
		// if main IF case failed then check if we have ELSEIF
		elseif( $elseif_content ){
			$elseif = array();
			preg_match_all('/%ELSEIF ([\d\w_]+)%(.+?)(?=%ELSEIF|%ELSE '.$key.'%)/sm', $elseif_content, $elseif, PREG_SET_ORDER);
			// if ELSEIF was found
			if($elseif){
				foreach( $elseif as $case ){
					// Check ELSEIF case
					if( is_array($vars) && array_key_exists($case[1], $vars) && $vars[ $case[1] ] ){
						return tpl_process( $case[2], $vars, true, false );
					}
				}
			}
			// if ELSEIF was not found then search for ELSE
			preg_match('/%ELSE '.$key.'%(.+)/sm', $elseif_content, $else);
			if($else[1]){
				return tpl_process( $else[1], $vars, true, false );
			}
		}
		
		return '';
	},$c);

	//VARIABLES after FUNCTIONS
	// var syntax = %=varname%
//	while(preg_match('/% *= *([^%]+) *%/',$c,$__m)){ // old
//		$arn=preg_replace('/\[[^\]]*\]/Usmi','',$__m[1]); // ?
//		eval('global $'.$arn.'; $rv=$'.$__m[1].';'); // old thing. full di4i
//		$rv = str_replace('$','&#0036;',$rv); // ?
//		echo $c=preg_replace('/'.str_replace('/','\/',preg_quote($__m[0])).'/',tpl_process($rv),$c,1);
//	}
	$c = preg_replace_callback('/%=([\d\w_\-]+)%/',function($matches) use ($vars){
		$var = isset( $vars[ $matches[1] ] ) ? $vars[ $matches[1] ] : gobj_get_tpl_var($matches[1]);
		return tpl_process( $var );
	},$c);
	
	return $c;
}

function gobj_set_tpl_var($varname, $value){
	global $gobj_tpl_vars;
	$gobj_tpl_vars[$varname] = $value;
}

function gobj_get_tpl_var($varname){
	global $gobj_tpl_vars;
	return $gobj_tpl_vars[$varname];
}

/*
 * Add sub to global variable and sub contents as var value.
 * Will be used in sub function 
 */
function gobj_set_sub($subname, $content){
	global $gobj_subs;
	$gobj_subs[$subname] = $content;
}

function gobj_get_sub($subname){
	global $gobj_subs;
	return $gobj_subs[$subname];
}

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

function admin_siteurl(){
	return SITEURL;
}

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

