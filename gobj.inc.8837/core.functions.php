<?php

function gobj_setup($config){
    global $gobj_config;
    $gobj_config = $config;
    gobj_set_tpl_var('admin_siteurl', ADMIN_SITEURL);
    gobj_set_tpl_var('siteurl', SITEURL);
    gobj_set_tpl_var('sitename', $gobj_config['sitename']);
    gobj_set_tpl_var('theme_url', ADMIN_SITEURL . 'templates/'._conf('admin_theme').'/' );

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

function gobj_set_tpl_var($varname, $value){
    global $gobj_tpl_vars;
    $gobj_tpl_vars[$varname] = $value;
}

function gobj_get_tpl_var($varname){
    global $gobj_tpl_vars;
    return $gobj_tpl_vars[$varname];
}
