<?php
/**
 * Collection of functions which should work if a module was not enabled.
 */

// Empty function for translation
if( ! function_exists('__') ){
    function __($str){
        return $str;
    }
}
