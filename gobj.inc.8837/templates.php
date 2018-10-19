<?php

/*
 * $p - ?
 * $e - if true, then echo the output
 * $vars - variables to use in template file
 */
function tpl_load ( $f, $p = true, $e = false, $vars = array() ) {
    if ( $vars ) extract( $vars );
    $f = ABSPATH . 'templates/'._conf('admin_theme').'/'.$f;
    if ( ! file_exists( $f ) ) die( '<font color=red><b>Template processing error:</b> template file "'.$f.'" doesn\'t exist!</font>' );
    $c = join( '', file( $f ) );
    $c = str_replace( '$', '&#0036;', $c );
    // Strip subs runs as separate function.
    $c = tpl_strip_subs( $c );
    if ( $p ) $c = tpl_process( $c, $vars );
    if ( $e ) echo( str_replace( '&#0036;', '$', $c ) );
    return str_replace( '&#0036;', '$', $c );
}

/*
 * Process sub block
 */
function tpl_process_sub ( $subname, $vars = array(), $process = true, $echo = true ) {
    $c = gobj_get_sub( $subname );
    if ( ! $c ) return NULL;
    if ( $process ) $c = tpl_process( $c, $vars );
    if ( $echo ) echo( $c );
    return $c;
}

/*
 * Collect sub blocks from template
 */
function tpl_strip_subs ( $c ) {
    global $tpl_SUBS, $tpl_SUBS_li, $tpl_SUBS_nli, $tpl_SUBS_box, $tpl_SUBS_box_nobox;
//	if(!isset($tpl_SUBS)) $tpl_SUBS=Array();
//	while(preg_match('/% *SUB *([^% ]+) *%(.*)% *ENDSUB *\\1 *%/Ums',$c,$m)){
//		$m[2]=tpl_strip_subs($m[2]);
//		$tpl_SUBS[$m[1]]=$m[2];
//		$c=str_replace($m[0],'%{'.$m[1].'()}%',$c);
//	}
    $c = preg_replace_callback( '/%SUB ([\d\w_]+)%(.+)%ENDSUB \\1%/Ums', function( $matches ) {
        $content = tpl_strip_subs( $matches[2] );
        gobj_set_sub( $matches[1], $content );
        // Add
        return '%='.$matches[1].'()%';
    }, $c );

    // ?
    if ( ! isset( $tpl_SUBS_box ) ) $tpl_SUBS_box = Array();
    while ( preg_match( '/% *BOX *([^% ]+) *%(.*)% *ENDBOX *\\1 *%/Ums', $c, $m ) ) {
        $m[2] = tpl_strip_subs( $m[2] );
        $tpl_SUBS_box[ $m[1] ] = $m[2];
        $c = str_replace( $m[0], '%IFBOX '.$m[1].'%', $c );
    }

    // ?
    if ( ! isset( $tpl_SUBS_box_nobox ) ) $tpl_SUBS_box_nobox = Array();
    while ( preg_match( '/% *NOBOX *([^% ]+) *%(.*)% *ENDNOBOX *\\1 *%/Ums', $c, $m ) ) {
        $m[2] = tpl_strip_subs( $m[2] );
        $tpl_SUBS_box_nobox[ $m[1] ] = $m[2];
        $c = str_replace( $m[0], '', $c );
    }

    if ( ! isset( $tpl_SUBS_li ) ) $tpl_SUBS_li = Array();
    while ( preg_match( '/% *IFL *%(.*)% *ENDIFL *%/Ums', $c, $m ) ) {
        $m[1] = tpl_strip_subs( $m[1] );
        $tpl_SUBS_li[ sizeof( $tpl_SUBS_li ) ] = $m[1];
        $c = str_replace( $m[0], '%{tpl_ifli(0,'.( sizeof( $tpl_SUBS_li ) - 1 ).')}%', $c );
    }

    if ( ! isset( $tpl_SUBS_nli ) ) $tpl_SUBS_nli = Array();
    while ( preg_match( '/% *IFNL *%(.*)% *ENDIFNL *%/Ums', $c, $m ) ) {
        $m[1] = tpl_strip_subs( $m[1] );
        $tpl_SUBS_nli[ sizeof( $tpl_SUBS_nli ) ] = $m[1];
        $c = str_replace( $m[0], '%{tpl_ifli(1,'.( sizeof( $tpl_SUBS_nli ) - 1 ).')}%', $c );
    }

    return $c;
}

function tpl_ifli ( $t, $n ) {
    $t = $t ? 'n' : '';
    global ${'tpl_SUBS_'.$t.'li'};
    if ( ( $t == '' && $_SESSION['uid'] ) || ( $t == 'n' && ! $_SESSION['uid'] ) ) return tpl_process( ${'tpl_SUBS_'.$t.'li'}[ $n ] );
    else return "";
}

function tpl_process ( $c, $vars = array() ) {
    global $_LANG;
    global $gobj_tpl_vars;
    $vars = array_merge( $gobj_tpl_vars, $vars );

    //VARIABLES before FUNCTIONS - ? why we need this
    // var syntax = %!=varname%
    while ( preg_match( '/% *! *= *([^%]+) *%/', $c, $__m ) ) {
        $arn = preg_replace( '/\[[^\]]*\]/Usmi', '', $__m[1] );
        eval( 'global $'.$arn.'; $rv=$'.$__m[1].';' );
        $rv = str_replace( '$', '&#0036;', $rv );
        $c = preg_replace( '/'.str_replace( '/', '\/', preg_quote( $__m[0] ) ).'/', tpl_process( $rv ), $c, 1 );
    }

    //BOXES - ? why we need this
    ob_start();
    while ( preg_match( '/% *IFBOX *([^\}]+) *%/', $c, $m ) ) {
        $old = ob_get_contents();
        ob_end_clean();
        ob_start();
        if ( function_exists( $m[1] ) ) $c2 = eval( 'return '.$m[1].'();' );
        else $c2 = '';
        $c1 = ob_get_contents();
        ob_end_clean();
        $c2 .= $c1;
        if ( $c2 ) $c = preg_replace(
            '/'.str_replace( '/', '\/', preg_quote( $m[0] ) ).'/',
            str_replace( '%{'.$m[1].'()}%', str_replace( '$', '&#0036;', tpl_process( $c2 ) ), $GLOBALS['tpl_SUBS_box'][ $m[1] ] ),
            $c, 1 );
        else $c = preg_replace(
            '/'.str_replace( '/', '\/', preg_quote( $m[0] ) ).'/',
            str_replace( '%{'.$m[1].'()}%', str_replace( '$', '&#0036;', tpl_process( $c2 ) ), $GLOBALS['tpl_SUBS_box_nobox'][ $m[1] ] ),
            $c, 1 );
        ob_start();
        echo( $old );
    }
    ob_end_clean();

    // FUNCTIONS
    // syntax = %=function_name()%
    $c = preg_replace_callback( '/%=([\d\w_]+)\(\)%/', function( $matches ) {
        if ( function_exists( $matches[1] ) ) {
            ob_start();
            call_user_func( $matches[1] );
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        } else
            return ''; // returns empty string
//			return $matches[0]; // return same tpl tag
    }, $c );

    // IF
    // syntax = %IF varname% ... %ELSE varname% - optional ... %ENDIF varname%
    $c = preg_replace_callback( '/%IF ([\d\w_]+)%(.+)%ENDIF \\1%/Ums', function( $matches ) use ( $vars ) {
        $key = $matches[1];
        $if_content = $matches[2];
        // Search for ELSEIF case and if found then split IF content from ELSEIF
        preg_match( '/(.+?)((?:%ELSEIF [\d\w_]+%|%ELSE '.$key.'%).+)/sm', $if_content, $elseif );
        if ( $elseif[1] ) {
            $if_content = $elseif[1];
            $elseif_content = $elseif[2];
        }
//		print_r($matches);
//		var_dump( (bool)$is_else);
//		if( $is_else ) die;
        if ( is_array( $vars ) && array_key_exists( $key, $vars ) && $vars[ $key ] ) {
            return tpl_process( $if_content, $vars, true, false );
        } // if main IF case failed then check if we have ELSEIF
        elseif ( $elseif_content ) {
            $elseif = array();
            preg_match_all( '/%ELSEIF ([\d\w_]+)%(.+?)(?=%ELSEIF|%ELSE '.$key.'%)/sm', $elseif_content, $elseif, PREG_SET_ORDER );
            // if ELSEIF was found
            if ( $elseif ) {
                foreach ($elseif as $case) {
                    // Check ELSEIF case
                    if ( is_array( $vars ) && array_key_exists( $case[1], $vars ) && $vars[ $case[1] ] ) {
                        return tpl_process( $case[2], $vars, true, false );
                    }
                }
            }
            // if ELSEIF was not found then search for ELSE
            preg_match( '/%ELSE '.$key.'%(.+)/sm', $elseif_content, $else );
            if ( $else[1] ) {
                return tpl_process( $else[1], $vars, true, false );
            }
        }

        return '';
    }, $c );

    //VARIABLES after FUNCTIONS
    // var syntax = %=varname%
//	while(preg_match('/% *= *([^%]+) *%/',$c,$__m)){ // old
//		$arn=preg_replace('/\[[^\]]*\]/Usmi','',$__m[1]); // ?
//		eval('global $'.$arn.'; $rv=$'.$__m[1].';'); // old thing. full di4i
//		$rv = str_replace('$','&#0036;',$rv); // ?
//		echo $c=preg_replace('/'.str_replace('/','\/',preg_quote($__m[0])).'/',tpl_process($rv),$c,1);
//	}
    $c = preg_replace_callback( '/%=([\d\w_\-]+)%/', function( $matches ) use ( $vars ) {
        $var = isset( $vars[ $matches[1] ] ) ? $vars[ $matches[1] ] : gobj_get_tpl_var( $matches[1] );
        return tpl_process( $var );
    }, $c );

    return $c;
}

/*
 * Add sub to global variable and sub contents as var value.
 * Will be used in sub function
 */
function gobj_set_sub ( $subname, $content ) {
    global $gobj_subs;
    $gobj_subs[ $subname ] = $content;
}

function gobj_get_sub ( $subname ) {
    global $gobj_subs;
    return $gobj_subs[ $subname ];
}
