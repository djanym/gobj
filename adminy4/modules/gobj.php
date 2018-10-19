<?php
//VERSION 0.1

if( is_action('logout') ){
	do_logout();
}
elseif( is_action('login') ){
	try_login();
}
elseif( is_action('save') ){
	do_item_save();
}

//if(isset($_GET['kobj_ordr'])) $_SESSION['kobj'][eregi_replace('[^a-z0-9_\-]+','',$_GET['item'])]['ordr_desc']=(int)$_GET['desc'];
//
//if( _sess('kobj')][eregi_replace('[^a-z0-9_\-]+','',$_GET['item'])]['ordr_desc'] == 1 ) {
//	$GLOBALS['sort_desc']=" DESC";
//	$GLOBALS['asc_desc'] = "down";
//}
//else {
//	$GLOBALS['sort_desc']="";
//	$GLOBALS['asc_desc'] = "up";
//}

//if(is_array($_POST['ko_filters'])) foreach($_POST['ko_filters'] as $k=>$v) $_SESSION['ko_filters'][$k]=addsl(strip($v));

if($_GET['item']==""||(!is_array($GLOBALS['_KITEMS'][$_GET['item']])&&$_GET['item']!="ko_options"&&$_GET['item']!="ko_dbbackup")) $_GET['item']=$GLOBALS['_KSITE']['startitem'];
if(!$_LANG['kobj_back']) $_LANG['kobj_back']="BACK";
if(!$_LANG['kobj_save']) $_LANG['kobj_save']="Save";
$GLOBALS['page']=$_GET['page']?$_GET['page']:$_POST['page'];

$GLOBALS['_YESNO']=array(1=>"Yes",2=>"No");

$GLOBALS['fck_loaded']=false;

function generate_content(){
	if( ! user_logged() ){
		return false;
	}
	// ?
	elseif( $_GET['item']=='ko_options' ){
		//OPTIONS\SETTINGS
		ko_parse_options();
	}
	// ?
	elseif($_GET['item']=='ko_dbbackup'){
		//DB BACKUP/RESTORE
		ko_parse_dbbackup();
	}
	else{
		// Load gobj items
		$item = _get('item');
		if( ! array_key_exists($item, _conf('items')) )
			$item = _conf('default_item');
		if( ! $item )
			die('Wrong URL');
		$action = _get('act') ? _get('act') : 'list';
		
		$pid=(int)$_GET['pid']; // ?

//		if($GLOBALS['_KITEMS'][$item]['userlevel']==0||($_SESSION['userlevel']<=$GLOBALS['_KITEMS'][$item]['userlevel']&&$_SESSION['userlevel']!=0)) ko_generate_content($item,$act,$id,$pid);
//		else {
//			header("Location: index.php");
//			die();
//		}
		gobj_generate_content($item, $action, $pid);
	}
}

function ko_parse_dbbackup(){
	set_time_limit(0);
	$act=$_GET['act'];
	if($act=='backup'){
		$fn=kobj_sqldumpdb();
		require_once("plugins/zip.php");
		chdir($GLOBALS['_KSITE']['tmp']);
		$a=new PclZip($fn.".zip");
		$a->create(array($fn));
		header("Content-type: application/force-download");
    header("Content-disposition: attachment; filename=".$fn.".zip");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".filesize($fn.".zip"));
    readfile($fn.".zip");
    unlink($fn.".zip");
    unlink($fn);
    chdir("..");
    die();
	}
	if($act=='restore'&&$_FILES['rf']['name']!=""){
		$GLOBALS['msg']="";
		require_once("plugins/zip.php");
		$a=new PclZip($_FILES['rf']['tmp_name']);
		chdir($GLOBALS['_KSITE']['tmp']);
		$a->extract();
		preg_match("/dump_([0-9]+)/",$_FILES['rf']['name'],$m);
		$tf="dump_".$m[1].".sql";
		$query=file($tf);
    unlink($tf);
    chdir("..");
		$queries=array();
		$num=count($query);
		foreach($query as $key=>$v){
			$v=trim($v);
			if(substr($v,0,1)!='#'){
				if(substr($v,-1,1)!=';'&&$key!=$num-1) $f1.=$v;
				else {
					if($f1=="") $f1=$v; else $f1.=$v;
					if(substr($v,-1,1)==';') $qq=substr($f1,0,-1);
					else $qq=$f1;
					if(trim($qq)) $queries[]=$qq;
					$f1="";
				}
			}
		}

		foreach($queries as $ttt=>$q){
			if($q){
				$w=sqlq($q);
				if(mysql_error()) $GLOBALS['msg'].="<font color=#ff0000><b>MySQL error:</b></font> ".mysql_error()."<br>";
			}
		}

		if($GLOBALS['msg']=="") $GLOBALS['msg']="Database Restored";
	}
	tpl_load("kobj_dbbackup.html",1,1);
}

function kobj_sqldumpdb(){
	$dbdump="";
  $q=sqlq("SHOW TABLES");
  while($data=mysql_fetch_row($q)){
  	$table=$data[0];
  	$index=array();

	  $tabledump="DROP TABLE IF EXISTS `$table`;\n";
	  $tabledump.="CREATE TABLE `$table` (\n";
	  $firstfield=1;
	  $champs=sqlq("SHOW FIELDS FROM `$table`");
	  while($champ=mysql_fetch_array($champs)){
	  	if(!$firstfield) $tabledump.=",\n";
	  	else $firstfield=0;
	  	$tabledump.="   `$champ[Field]` $champ[Type]";
	  	if($champ['Null']!="YES") $tabledump.=" NOT NULL";
	  	if(!empty($champ['Default'])) $tabledump.=" default '$champ[Default]'";
	  	if($champ['Extra']!="") $tabledump.=" $champ[Extra]";
	  }
	  @mysql_free_result($champs);
	  $keys=sqlq("SHOW KEYS FROM `$table`");
	  while($key=mysql_fetch_array($keys)){
	  	$kname=$key['Key_name'];
	  	if($kname!="PRIMARY"&&$key['Non_unique']==0) $kname="UNIQUE|`$kname`";
	  	if(!is_array($index[$kname])) $index[$kname]=array();
	  	$index[$kname][]=$key['Column_name'];
	  }
	  @mysql_free_result($keys);
	  while(list($kname,$columns)=@each($index)){
	  	$tabledump.=",\n";
	  	$colnames=implode($columns,",");
	  	if($kname=="PRIMARY") $tabledump.="   PRIMARY KEY (`".eregi_replace(',','`,`',$colnames)."`)";
	  	else {
	  		if(substr($kname,0,6)=="UNIQUE") $kname=substr($kname,7);
	  		$tabledump.="   KEY $kname (`".eregi_replace(',','`,`',$colnames)."`)";
	  	}
	  }
	  $tabledump.="\n);\n\n";

	  $rows=sqlq("SELECT * FROM `$table`");
	  $numfields=mysql_num_fields($rows);
	  while($row=mysql_fetch_array($rows)){
	  	$tabledump.="INSERT INTO `$table` VALUES(";
	  	$cptchamp=-1;
	  	$firstfield=1;
	  	while(++$cptchamp<$numfields){
	  		if(!$firstfield) $tabledump.=",";
	  		else $firstfield=0;
	  		if(!isset($row[$cptchamp])) $tabledump.="NULL";
	  		else $tabledump.="'".mysql_escape_string($row[$cptchamp])."'";
	  	}
	  	$tabledump.=");\n";
	  }
	  @mysql_free_result($rows);
	  $dbdump.=$tabledump."\n\n";
	}

  $fn="dump_".time().".sql";
  $fff=fopen($GLOBALS['_KSITE']['tmp']."/".$fn,"w");
  fwrite($fff,$dbdump);
  fclose($fff);
  return $fn;
}

function ko_parse_options(){
	if(isset($_POST['op'])){
		$op=$_POST['op'];
		foreach($GLOBALS['_KSITE']['options'] as $k=>$v) if( $fld['type'] === 'checkbox'&&!$_POST[$k]) sqlq("update options set varvalue='' where varname='".addslashes($k)."'");
		foreach($op as $k=>$v){
			if(mysql_num_rows(sqlq("select * from options where varname='".addslashes($k)."'"))==0) sqlq("insert into options set varname='".addslashes($k)."'");
			sqlq("update options set varvalue='".addslashes($v)."' where varname='".addslashes($k)."'");
		}
	}
	tpl_load("kobj_options.html",true,true);
}

function ko_show_options(){
	foreach($GLOBALS['_KSITE']['options'] as $k=>$v){
		$GLOBALS['name']=$v['name'];
		if( $fld['type'] === 'text') $GLOBALS['field']='<input type="text" name="op['.$k.']" value="'.eregi_replace('"','&#34;',geto($k)).'">';
		if( $fld['type'] === 'password') $GLOBALS['field']='<input type="password" name="op['.$k.']" value="'.eregi_replace('"','&#34;',geto($k)).'">';
		if( $fld['type'] === 'checkbox') $GLOBALS['field']='<input type="checkbox" name="op['.$k.']" value="1" '.(geto($k)?'checked':'').' style="border: none;">';
		if( $fld['type'] === 'textarea') $GLOBALS['field']='<textarea name="op['.$k.']" cols="'.($v['cols']?$v['cols']:'60').'" rows="'.($v['rows']?$v['rows']:'4').'">'.htmlentities(geto($k)).'</textarea>';
		tpl_block("ko_show_options",true,true);
	}
}

function gobj_generate_content($item, $action, $pid = null){
	$GLOBALS['ifpid']=$pid?'&pid='.$pid:''; // ?
	$GLOBALS['pid']=$pid; // ?

	$iconf = _conf('items')[$item];
	
	// Set page title
	gobj_set_tpl_var('header_title', $iconf['titles'][ $action ]);
	// Set active class for menu
	gobj_set_tpl_var( $item.'_active', 'active');
	
	$GLOBALS['header_view_name']=$_it['headers']['view']; // ?
	

	// ?
	if(is_array($_it['modules'])) foreach($_it['modules'] as $v) require_once($v);
	
	switch ($action) {
		case 'list':
			// $_GET['act']=='print'?$_it['templates']['printlist']:$_it['templates']['list']
			gobj_list_items($item,$GLOBALS['sa_where']);
			break;
		case 'edit':
			gobj_edit_item($item, _get('id'));
			break;
		case 'view': // ?
			_klist_view($_it['templates']['view'],$item,$id);
			break;
		case 'del': // ?
			_klist_del($item,$id);
			break;
		case 'position': // ?
			_klist_position($item,$id,addslashes($_GET['repid']));
			break;
		default:
			break;
	}
//	($act=="print") $act=$_GET['what'];
}

function do_item_save(){
//	echo '<pre>';
//	print_r($_GET);
//	print_r($_POST);
	$gitem = _get('item');
	$id = (int)_post('id');
	$iconf = _conf('items')[$gitem];
	if( ! $iconf ) die('Wrong url. Error: DIS01');
	
	if( $id ){
		$item = db()->get_row("SELECT * FROM `".$iconf['table']."` WHERE id = '".esc_sql($id)."' ");
	}
	if( $id && ! $item ) die('Wrong ID. Error: DIS02');
	
	// ? 
	$r = true;
	if (function_exists("on_save_start"))
		$r = on_save_start($id);
	$_it = $GLOBALS['_KITEMS'][$item];
	$new = false;
	$emsg = array();
	if (strlen($r) > 1) {
		$emsg[] = $r;
		$r = false;
	}
	
	$errors = array();
//	if ($r) {
	// Validate form values
	foreach( $iconf['fields'] as $fld_key => $fld ){
		$value = _post( $fld_key );
		
		// ?
		if( $fld['type'] === "checkbox" && $_POST[$k] == "")
				$_POST[$k] = "0";
		
		// if field should be unique then check it
		if( $fld['unique'] && 
						( ! $fld['not_required'] || ! empty($value) ) &&
						! is_unique( $iconf['table'], $fld_key, $value, $id ) ){
			$errors[ $fld_key ] = $fld['title'] . ' must be unique.';
		}
		
		// if field should not be empty
		if( ! $fld['not_required'] &&
						( $fld['type'] !== 'switch' || $fld['switch_type'] !== 'toggle' ) &&
						empty($value) ){
			$errors[ $fld_key ] = $fld['title'] . ' not filled.';
		}
		
//			if ($v['is_number'] && $_POST[$k] && preg_match("/[^0-9\.]/", $_POST[$k]))
//				$emsg[] = ($v['list_name'] ? $v['list_name'] : $v['edit_name']) . " " . ($GLOBALS['_LANG']['kobj_must_be_number'] ? $GLOBALS['_LANG']['kobj_must_be_number'] : "must be number.") . " (" . $_POST[$k] . ")";
	}
//	}
	
	if( $errors ){
		json_error( $errors );
	}
	
	// Prepare query from fields values
	foreach($iconf['fields'] as $fld_key => $fld){
		$value = _post( $fld_key );
//		if ($v['type'] != 'query' && $v['type'] != 'reflist')
		// if field is switch and toggle then value should be 0 or 1
		if( $fld['type'] === 'switch' && $fld['switch_type'] === 'toggle' )
			$value = $value ? '1' : '0';
		
		$query_fields[ $fld_key ] = $value; 
	}
	
	if( ! $id ) {
		db()->insert( $iconf['table'], $query_fields );
		$id = db()->insert_id;
	}
	else{
		db()->update( $iconf['table'], $query_fields, array('id' => $id) );
	}
	
	$res['success'] = 1;
	$res['msg'] = 'Saved';
	json($res);

	/*
	$q = array();
	foreach ($_it['fields'] as $k => $v) {
		if ($k != 'id' && $v['type'] != "comment") {
			if ($v['type'] != 'query' && $v['type'] != 'reflist' && $v['type'] != 'file' && $v['type'] != 'info' && $v['type'] != 'array') {
				if ($new || !$v['save_protected'] || ($v['save_protected'] && ($_POST[$k] != '' || $fld['type'] === 'date'))) {
					//if(!$v['hidden']||$new){
					if (!$v['hidden']) {
						if ($fld['type'] === 'date')
							$_POST[$k] = mydateformat('mktime', $v['date_format'], $_POST[$k]);
						if ($fld['type'] === 'datetime')
							$_POST[$k] = mydatetimeformat('mktime', $v['date_format'], $_POST[$k]);
						if (function_exists($v['save_act'])) {
							$sret = $v['save_act']($_POST[$k], $id);
							if ($sret)
								$q[] = $k . "='" . addslashes($sret) . "'";
						} else
							$q[] = $k . "='" . addslashes($_POST[$k]) . "'";
					}
				}
			}
			if ($fld['type'] === 'reflist') {
				sqlq("delete from " . $v['ref_table'] . " where " . $v['ref_id_this'] . "='" . $id . "'");
				if (is_array($_POST[$k])) {
					foreach ($_POST[$k] as $k1 => $v1) {
						if ($v['is_select'])
							sqlq("insert into " . $v['ref_table'] . " set " . $v['ref_id_this'] . "='" . $id . "'," . $v['ref_id_other'] . "='" . $v1 . "'");
						else
							sqlq("insert into " . $v['ref_table'] . " set " . $v['ref_id_this'] . "='" . $id . "'," . $v['ref_id_other'] . "='" . $k1 . "'");
					}
				}
			}
			if ($fld['type'] === 'array') {
				if (is_array($_POST[$k])) {
					foreach ($_POST[$k] as $k1 => $v1) {
						$e = 0;
						foreach ($v1 as $v2)
							if ($v2 == "")
								$e++;
						if ($e == sizeof($v1))
							unset($_POST[$k][$k1]);
					}
					$ak = base64_encode(serialize($_POST[$k]));
					$q[] = $k . "='" . addslashes($ak) . "'";
				}
			}
			if ($fld['type'] === 'file') {
				$fl = $_FILES[$k];
				if (file_exists($fl['tmp_name'])) {
					if ($v['simple_save'])
						$fname = $v['save_path'] . "/" . $id . "." . $v['file_extension'];
					else {
						preg_match("/\.([^\.]+)$/Usmi", $fl['name'], $m);
						$ext = $m[1];
						$fname = $v['save_path'] . "/" . $k . "_" . $id . "." . $ext;
						$q[] = $k . "='" . addslashes($k . "_" . $id . "." . $ext) . "'";
					}
					copy($fl['tmp_name'], $fname);
				}
				if ($_POST[$k . "_delfile"] == 1) {
					if ($v['simple_save'])
						$fn = $v['save_path'] . "/" . $id . "." . $v['file_extension'];
					else
						$fn = sqlr("select " . $k . " from " . $_it['table'] . " where id='" . $id . "'", 1);
					@unlink($fn);
					$q[] = $k . "=''";
				}
			}
		}
	}
	$q = join(",", $q);
	sqlq("update " . $_it['table'] . " set $q where id='" . $id . "'");
	if ($_it['if_position'] && $new) {
		$kpos = sqlr("select greatest(0,max(k_pos)) from " . $_it['table']);
		sqlq("update " . $_it['table'] . " set k_pos=" . ($kpos + 1) . " where id='" . $id . "'");
	}

	$r = true;
	if (function_exists("on_save_end"))
		$r = on_save_end($id, $new);
	if ($r) {
		if (!$_it['parent']) {
			header("Location: index.php?mod=kobj&item=" . $item);
			die;
		} else
			header("Location: index.php?mod=kobj&item=" . $_it['parent'] . "&pid=" . $_GET['pid'] . "&page=" . $GLOBALS['page']);
	}
	*/
}

function _klist_position($item,$id,$repid){
	$_it=$GLOBALS['_KITEMS'][$item];
	$mykpos=sqlr("select k_pos from ".$_it['table']." where id='".$id."'");
	$nwkpos=sqlr("select k_pos from ".$_it['table']." where id='".$repid."'");
	sqlq("update ".$_it['table']." set k_pos='".(int)$nwkpos."' where id='".$id."'");
	sqlq("update ".$_it['table']." set k_pos='".(int)$mykpos."' where id='".$repid."'");
	if(!$_it['parent']) _klist_items($_it['templates']['list'],$item,$GLOBALS['sa_where']);
	else header("Location: index.php?mod=kobj&item=".$_it['parent']."&pid=".$_GET['pid']);
}

function _klist_del($item,$id){ //DELETE ITEM
	if(!$GLOBALS['_KITEMS'][$item]['if_bandelete']){
		if(function_exists("on_del_start")) $ifdel=on_del_start($id);
		else $ifdel=true;
		$_it=$GLOBALS['_KITEMS'][$item];
		if($ifdel){
			//DELETE CORRESPONDING FILES
			foreach($_it['fields'] as $k=>$v) if( $fld['type'] === 'file'){
				if($v['simple_save']) $fn=$v['savepath']."/".$id.".".$v['file_extension'];
				else $fn=sqlr("select ".$k." from ".$_it['table']." where id='".$id."'",1);
				@unlink($fn);
			}

			sqlq("delete from ".$_it['table']." where id='".$id."'");

			if(!$_it['parent']) _klist_items($_it['templates']['list'],$item,$GLOBALS['sa_where']);
			else header("Location: index.php?mod=kobj&item=".$_it['parent']."&pid=".$_GET['pid']."&page=".$GLOBALS['page']);
		}
		if(function_exists("on_del_end")) on_del_end($id);
		elseif(!$ifdel){
			if(!$_it['parent']) _klist_items($_it['templates']['list'],$item,$GLOBALS['sa_where']);
			else header("Location: index.php?mod=kobj&item=".$_it['parent']."&pid=".$_GET['pid']."&page=".$GLOBALS['page']);
		}
	}
}

function gobj_edit_item($item, $id){
	global $current_gobj_item, $current_gobj_item_config, $_gedit_item;
	
	$current_gobj_item = $item;
	$current_gobj_item_config = _conf('items')[$item];
	$iconf = _conf('items')[$item];
	
	// if new then change headet title
	if( ! $id )
		gobj_set_tpl_var('header_title', $iconf['titles']['add']);
	
	// if back url was provided
	echo _get('back_url'); die; // ???
	if( _get('back_url') )
		gobj_set_tpl_var('back_url', _get('back_url') );
	
	$vars['edit_form_url'] = 'index.php?mod=gobj&item='.$item.'&act=save';
	gobj_set_tpl_var('id', $id);
	
	if( $id ){
		$_gedit_item = db()->get_row("SELECT * FROM `".$iconf['table']."` WHERE id = '".esc_sql($id)."' ");
	}
	
	// ?
	if(!$GLOBALS['_KITEMS'][$_it['parent']]['children_inline']) $GLOBALS['back_item']=$item;
	else $GLOBALS['back_item']=$_it['parent'];
	
	// ?
	$ret=true;
	
	// ?
	if(function_exists("on_edit_start")) $ret=on_edit_start($id,$new);
	
		// Template file
	$tpl = $iconf['templates']['edit'];
	
	if($ret){
		tpl_load($tpl,true,true, $vars);
	}
	else {
		header("Location: index.php");die();
	}
	// ?
	if(function_exists("on_edit_end")) $GLOBALS['kon_edit_end']=on_edit_end($id,$new);
}

function gedit_item_field(){
	global $_gedit_item;
	
	$item = $_gedit_item;
	$iconf = gobj_get_current_item_config();
	
	$isnew = $item ? false : true;


	// ? 
//	if($id!=-1){
//		$isnew=false;
//		foreach($r as $k=>$v) ${"d_".$k}=str_replace("%","&#37;",strip($v,1));
//	} else {
//		foreach($_it['fields'] as $k=>$v) ${"d_".$k}=str_replace("%","&#37;",$_POST[$k]);
//	}

	// ?
	if($_it['parent_field']) ${"d_".$_it['parent_field']}=$_GET['pid'];

	foreach($iconf['fields'] as $k => $fld){
		$vars = array();
		$vars['label'] = $fld['title'];
		$vars['value'] = esc_attr($item->$k);
		$vars['name'] = $k;
		
		switch( $fld['type'] ){
			case 'text':
				$vars['is_input'] = true;
//					if ($v['date_format'])
//						$GLOBALS['field'] = ${"d_" . $k} ? date($v['date_format'], ${"d_" . $k}) : "-";
//					elseif ($v['is_reference'] != "" || is_array($v['is_reference'])) {
//						if (is_array($v['is_reference'])) {
//							foreach ($v['is_reference'] as $k1 => $v1)
//								if ($k1 == ${"d_" . $k})
//									$GLOBALS['field'] = $v1;
//						} else {
//							$ro1 = sqlq("select * from " . $v['is_reference']);
//							while ($r1 = mysql_fetch_assoc($ro1)) {
//								if ($r1['id'] == ${"d_" . $k})
//									$GLOBALS['field'] = strip($r1[$v['reference_field']], 1);
//							}
//						}
//					} else
//						$GLOBALS['field'] = $v['prefix'] . ${"d_" . $k};
				break;
			case 'select':
				$vars['is_select'] = true;
				break;
			case 'switch':
				$vars['is_toggle'] = true;
				$vars['is_on'] = $vars['value'] ? 'checked' : '';
				$vars['on_label'] = $fld['labels'][1];
				$vars['off_label'] = $fld['labels'][0];
				break;
			default: // info
				break;
		}
		tpl_process_sub('gedit_item_field', $vars, true, true);
		
		continue;
		if (!$v['hidden'] && $GLOBALS['field'] != "" && $v['type'] != 'comment')
			tpl_block("klist_edit", true, true);
		elseif ($fld['type'] === 'comment')
			tpl_block("klist_edit_comment", true, true);
		else {
			if ($fld['type'] === 'date') {
				$dateformat = $v['date_format'] ? $v['date_format'] : "d/m/Y";
				${"d_" . $k} = date($dateformat, ${"d_" . $k});
			}
			if ($fld['type'] === 'datetime') {
				$dateformat = $v['date_format'] ? $v['date_format'] : "d/m/Y H:i";
				${"d_" . $k} = date($dateformat, ${"d_" . $k});
			}
			echo("<input type='hidden' name='" . $k . "' value='" . ${"d_" . $k} . "'>");
		}
		/***/
		if($v['type']!="query"){
			if($v['if_new_hide']&&$isnew) $v['hidden']=true;
			if($v['if_old_hide']&&!$isnew) $v['hidden']=true;

			if( $fld['type'] === 'comment') $GLOBALS['field']=$v['text'];
			
			if($isnew&&isset($v['default'])) ${"d_".$k}=$v['default'];
			if($isnew&&isset($_SESSION['ko_filters'][$k])) ${"d_".$k}=$_SESSION['ko_filters'][$k];
			$GLOBALS['name']=$v['edit_name']?$v['edit_name']:$v['list_name'];
			if( $fld['type'] === 'date'){
				if($id==-1) ${"d_".$k}=time();
				$dateformat=$v['date_format']?$v['date_format']:"d/m/Y";
				if(${"d_".$k}==0) $date=""; else $date=date($dateformat,${"d_".$k});
				$GLOBALS['field']="<input type='text' name='".$k."' value='".$date."' id='_df_".$k."'><script type=\"text/javascript\" src=\"plugins\calendar\calendar.js\"></script><script type=\"text/javascript\" src=\"plugins\calendar\calendar-en.js\"></script><script type=\"text/javascript\" src=\"plugins\calendar\calendar-setup.js\"></script><script type=\"text/javascript\">Calendar.setup({inputField:\"_df_".$k."\",ifFormat:\"".mydateformat('calendar',$v['date_format'])."\",showsTime:false,button:\"_fd_".$k."\",singleClick:false,step:1,weekNumbers:false});</script>";
			}
			if( $fld['type'] === 'datetime'){
				if($id==-1) ${"d_".$k}=time();
				$dateformat=$v['date_format']?$v['date_format']:"d/m/Y H:i";
				if(${"d_".$k}==0) $date=""; else $date=date($dateformat,${"d_".$k});
				$GLOBALS['field']="<input type='text' name='".$k."' value='".$date."' id='_df_".$k."'><script type=\"text/javascript\" src=\"plugins\calendar\calendar.js\"></script><script type=\"text/javascript\" src=\"plugins\calendar\calendar-en.js\"></script><script type=\"text/javascript\" src=\"plugins\calendar\calendar-setup.js\"></script><script type=\"text/javascript\">Calendar.setup({inputField:\"_df_".$k."\",ifFormat:\"".mydatetimeformat('calendar',$v['date_format'])."\",showsTime:true,button:\"_fd_".$k."\",singleClick:false,step:1,weekNumbers:false});</script>";
			}
			
			if( $fld['type'] === 'color_picker'){
				$GLOBALS['field']="<link rel=\"stylesheet\" href=\"plugins/color_picker/js_color_picker_v2.css\" media=\"screen\">";
				$GLOBALS['field'].="<script type=\"text/javascript\" src=\"plugins/color_picker/color_functions.js\"></script>";
				$GLOBALS['field'].="<script type=\"text/javascript\" src=\"plugins/color_picker/js_color_picker_v2.js\"></script>";
				$GLOBALS['field'].="<input type='text' name='".$k."' value='".str_replace("'","&#39;",${"d_".$k})."' ".($v['size']?"size='".$v['size']."'":"").">";
				$GLOBALS['field'].="<input type='button' value='Color picker' onclick='showColorPicker(this,document.forms[0].".$k.")' ";
			}
			if( $fld['type'] === 'checkbox'){
				$GLOBALS['field']="<input type='checkbox' name='".$k."' value='1' ".(${"d_".$k}?"checked":"")." style=\"border: none;\">";
			}
			if( $fld['type'] === 'textarea'){
				$GLOBALS['field']="<textarea name='".$k."' cols='".($v['cols']?$v['cols']:50)."' rows='".($v['rows']?$v['rows']:6)."'>".${"d_".$k}."</textarea>";
			}
			if( $fld['type'] === 'richedit'){
				$GLOBALS['field']="";
				if(!$GLOBALS['fck_loaded']){
					$GLOBALS['field']="<script type=\"text/javascript\" src=\"plugins/fck/fckeditor.js\"></script>";
					$GLOBALS['fck_loaded']=true;
				}
				$GLOBALS['field'].="<textarea id='".$k."' name='".$k."' cols='".($v['cols']?$v['cols']:50)."' rows='".($v['rows']?$v['rows']:6)."'>".${"d_".$k}."</textarea><script>var oFCKeditor=new FCKeditor('".$k."',".($v['width']?$v['width']:400).",".($v['height']?$v['height']:200).");oFCKeditor.BasePath='./plugins/fck/';oFCKeditor.ReplaceTextarea();</script>";
			}
			if( $fld['type'] === 'password'){
				$GLOBALS['field']="<input type='password' name='".$k."' value=''>";
			}
			if( $fld['type'] === 'file'){
				$GLOBALS['field']="<input type='file' name='".$k."'>";
				if(${"d_".$k}!="") $GLOBALS['field'].="<br><input type='checkbox' name='".$k."_delfile' value='1' style=\"border: none;\"> Delete file";
				if($v['show_image']&&${"d_".$k}!=""){
					if($v['make_thumb']) $GLOBALS['field'].="<br><img src='img.php?src=".$v['save_path']."/".${"d_".$k}."&w=".$v['make_thumb']."'>";
					else $GLOBALS['field'].="<br><img src='".$v['save_path']."/".${"d_".$k}."'>";
				}
			}
			if( $fld['type'] === 'module'){
				require_once($v['module']);
				if(function_exists($v['edit_act'])) $GLOBALS['field']=$v['edit_act'](${"d_".$k},$id);
				else $GLOBALS['field']="";
			}
			if( $fld['type'] === 'select'){
				if(!$v['no_choose']) $options="<option value=''>[".($GLOBALS['_LANG']['kobj_choose']?$GLOBALS['_LANG']['kobj_choose']:"Choose")."]";
				else $options="";
				if(is_array($v['is_reference'])){
					foreach($v['is_reference'] as $k1=>$v1){
						$options.="<option value='".$k1."'".($k1==${"d_".$k}?" selected":"").">".$v1;
					}
				} else {
					$idthis=$v['ref_id_this']?$v['ref_id_this']:'id';
					$refw=preg_replace("/\{([^}]+)\}/Usmie","${'d_'.'\\1'}",$v['reference_where']);
					$ro1=sqlq("select * from ".$v['is_reference'].($v['reference_where']?' where '.$refw:'')." order by ".($v['orderby_field']?$v['orderby_field']:$v['reference_field']));
					while($r1=mysql_fetch_assoc($ro1)){
						$options.="<option value='".$r1[$idthis]."'".($r1[$idthis]==${"d_".$k}?" selected":"").">".strip($r1[$v['reference_field']],1);
					}
				}
				$GLOBALS['field']="<select name='".$k."'>".$options."</select>";
			}
			if( $fld['type'] === 'reflist'){
				$out="";
				$ro1=sqlq("select * from ".$v['other_table']." order by ".($v['other_field']?$v['other_field']:"name"));
				while($r1=mysql_fetch_assoc($ro1)){
					if(!$v['is_select']){
						if(mysql_num_rows(sqlq("select * from ".$v['ref_table']." where ".$v['ref_id_this']."='".$id."' and ".$v['ref_id_other']."='".$r1['id']."'"))>0) $sel="checked"; else $sel="";
						$out.="<input type='checkbox' name='".$k."[".$r1['id']."]' ".$sel." style=\"border: none;\"> ".strip($r1[$v['other_field']?$v['other_field']:"name"])."<br>";
					} else {
						if(mysql_num_rows(sqlq("select * from ".$v['ref_table']." where ".$v['ref_id_this']."='".$id."' and ".$v['ref_id_other']."='".$r1['id']."'"))>0) $sel="selected"; else $sel="";
						$out.="<option value='".$r1['id']."' ".$sel."> ".strip($r1[$v['other_field']?$v['other_field']:"name"]);
					}
				}
				if(!$v['is_select']) $GLOBALS['field']=$out;
				else $GLOBALS['field']="<select name='".$k."[]' multiple size='".$v['size']."'>".$out."</select>";
			}
			if( $fld['type'] === 'array'){
				$ret="<table border='0'><tr>";
				foreach($v['array'] as $v1) $ret.="<th>".$v1."</th>";
				$ret.="</tr>";
				$ak=@unserialize(@base64_decode(${"d_".$k}));
				$cnt=0;
				if(is_array($ak)){
					foreach($ak as $v2){
						$ret.="\n<tr>";
						foreach($v['array'] as $v1) $ret.="<td><input type='text' size='".($v['size']?$v['size']:"15")."' name='".$k."[".$cnt."][".$v1."]' value='".$v2[$v1]."'></td>\n";
						$ret.="</tr>\n";
						$cnt++;
					}
				}
				foreach($v['array'] as $v1) $ret.="<td><input type='text' size='".($v['size']?$v['size']:"15")."' name='".$k."[".$cnt."][".$v1."]' value=''></td>";
				$ret.="</table>";
				$GLOBALS['field']=$ret;
			}
			
		}
	}
}

/*
 * ?
 */
function kobj_list_view($item,$id){
	$iconf = _conf('items')[$item];
	$_it=$GLOBALS['_KITEMS'][$item];
	if(!$GLOBALS['_KITEMS'][$_it['parent']]['children_inline']) $GLOBALS['back_item']=$item;
	else $GLOBALS['back_item']=$_it['parent'];
	if($tpl=="") $tpl="kobj_view.html";
	$GLOBALS['id']=$id;
	tpl_load($tpl,true,true);
}

function klist_view(){
	$item=$GLOBALS['item'];
	$id=$GLOBALS['id'];
	if(function_exists("on_view_start")) $GLOBALS['kon_view_start']=on_view_start($id);
	$_it=$GLOBALS['_KITEMS'][$item];
	$r=sqla("select * from ".$_it['table']." where id='".$id."'");
	foreach($r as $k=>$v) ${"d_".$k}=strip($v,1);

	if($_it['parent_field']) ${"d_".$_it['parent_field']}=$_GET['pid'];

	foreach($_it['fields'] as $k=>$v){
		if($v['type']!="query"&&$v['type']!='password'&&($v['type']!="file"||$v['show_image'])){
			$GLOBALS['name']=$v['view_name']?$v['view_name']:$v['list_name'];
			if( $fld['type'] === 'info'){
				if($v['date_format']) $GLOBALS['field']=${"d_".$k}?date($v['date_format'],${"d_".$k}):"-";
				elseif($v['is_reference']!=""||is_array($v['is_reference'])){
					if(is_array($v['is_reference'])){
						foreach($v['is_reference'] as $k1=>$v1) if($k1==${"d_".$k}) $GLOBALS['field']=$v1;
					} else {
						$ro1=sqlq("select * from ".$v['is_reference']);
						while($r1=mysql_fetch_assoc($ro1)){
							if($r1['id']==${"d_".$k}) $GLOBALS['field']=strip($r1[$v['reference_field']],1);
						}
					}
				} else $GLOBALS['field']=$v['prefix'].${"d_".$k};
			}
			if( $fld['type'] === 'date'){
				$dateformat=$v['date_format']?$v['date_format']:"d/m/Y";
				if(${"d_".$k}>0) $GLOBALS['field']=date($dateformat,${"d_".$k});
				else $GLOBALS['field']='';
			}
			if( $fld['type'] === 'datetime'){
				$dateformat=$v['date_format']?$v['date_format']:"d/m/Y H:i";
				if(${"d_".$k}>0) $GLOBALS['field']=date($dateformat,${"d_".$k});
				else $GLOBALS['field']='';
			}
			if( $fld['type'] === 'text'|| $fld['type'] === 'textarea'|| $fld['type'] === 'hidden'|| $fld['type'] === 'richedit'){
				$GLOBALS['field']=${"d_".$k};
			}
			if( $fld['type'] === 'checkbox'){
				$GLOBALS['field']=${"d_".$k}?"Yes":"No";
			}
			if( $fld['type'] === 'module'){
				require_once($v['module']);
				//TODO
				$GLOBALS['field']=$v['list_act']($r,$id);
			}
			if( $fld['type'] === "file"&&$v['show_image']){
				if(${"d_".$k}!="") $GLOBALS['field']="<img src='http://".$GLOBALS['_SITEURL']."/".$v['save_path']."/".${"d_".$k}."'>";
				else $GLOBALS['field']="[no image]";
			}
			if( $fld['type'] === 'select'){
				if(is_array($v['is_reference'])){
					foreach($v['is_reference'] as $k1=>$v1) if($k1==${"d_".$k}) $GLOBALS['field']=$v1;
				} else {
					$ro1=sqlq("select * from ".$v['is_reference']." order by ".$v['reference_field']);
					while($r1=mysql_fetch_assoc($ro1)){
						if($r1['id']==${"d_".$k}) $GLOBALS['field']=strip($r1[$v['reference_field']],1);
					}
				}
			}
			if( $fld['type'] === 'reflist'){
				$ro1=sqlq("select o.".($v['other_field']?$v['other_field']:"name")." from ".$v['ref_table']." r,".$v['other_table']." o where r.".$v['ref_id_other']."=o.id and r.".$v['ref_id_this']."=".$r['id']);
				$out=array();
				while($r1=mysql_fetch_assoc($ro1)) $out[]=strip($r1[$v['other_field']?$v['other_field']:"name"]);
				$GLOBALS['field']="<td>".join(", ",$out)."</td>";
			}

			if($v['type']!='hidden') tpl_block("klist_view",true,true);
		} elseif( $fld['type'] === 'query'){
			$GLOBALS['name']=$v['view_name']?$v['view_name']:$v['list_name'];
			$q=$v['query'];
			preg_match_all("/\{([^}]+)\}/",$q,$qm);
			foreach($qm[1] as $qk=>$qv) $q=str_replace($qm[0][$qk],$r[$qv],$q);
			$GLOBALS['field']=sqlr($q,1,1);
			tpl_block("klist_view",true,true);
		}
	}
	if(function_exists("on_view_end")) $GLOBALS['kon_view_end']=on_view_end($id);
}

/*
 * ?
 */
function gobj_list_items($item, $where = array() ){
	global $current_gobj_item, $current_gobj_item_config;
	
	$current_gobj_item = $item;
	$current_gobj_item_config = _conf('items')[$item];
	$iconf = _conf('items')[$item];
//	if($_GET['act']=='print') $iconf['perpage'] = 999999;

	$pid=(int)$_GET['pid']; // ?

	if( ! is_array($where) )
		$where = (array)$where; // ?
	
	$where = array_merge($where,kobj_items_filters($_it['filters'])); // ?

	// ?
	if($_it['parent_field']){
		$where[]=$_it['parent_field']."='".$pid."'";
		if(!$GLOBALS['_KITEMS'][$_it['parent']]['children_inline']) $GLOBALS['ko_backlink']='[<a href="index.php?mod=kobj&item='.$_it['parent'].'&id='.$GLOBALS['pid'].'"><b>&laquo; '.($GLOBALS['_LANG']['kobj_back']?$GLOBALS['_LANG']['kobj_back']:"BACK").'</b></a>]';
	} else $GLOBALS['ko_backlink']='';

	// Template file
	if( $GLOBALS['_KITEMS'][$_it['parent']]['children_inline']){ // ?
		 $tpl="kobj_listchild.html";
	}
	else {
		if( $_GET['act'] == 'print' ) $tpl="kobj_list_print.html"; // ?
		else $tpl = $iconf['templates']['list'];
	}
	
	if(sizeof($where)>0){ // ?
		$where="where ".join(" and ",$where);
	} else $where="";

	$GLOBALS['_ko_qwhere']=$where; // ?
	$GLOBALS['_ko_item']=$item; // ?

	// ?
	if($_it['if_view']) $GLOBALS['_ko_controls_view']='<a href="index.php?mod=kobj&item='.$item.'&act=view&page='.$GLOBALS['page'].'&id=#ID#'.($pid?'&pid='.$pid:'').'"><img src="img/glass_white.gif" border=0 alt="'.($GLOBALS['_LANG']['kobj_view']?$GLOBALS['_LANG']['kobj_view']:"View").'"></a>';
	else $GLOBALS['_ko_controls_view']='';

	// if items controls enabled
	if( $iconf['if_controls'] ){
		
	}

	// ?
	if( $iconf['if_addnew'] )
			$GLOBALS['ko_addnew']='<a href="index.php?mod=kobj&item='.$item.'&act=edit&page='.$GLOBALS['page'].'&id=-1'.($pid?'&pid='.$pid:'').'" class="red_link">'.($GLOBALS['_LANG']['kobj_add_new']?$GLOBALS['_LANG']['kobj_add_new']:"add new").'</a>';
	
	else $GLOBALS['ko_addnew']='';
	if($_it['if_print_list']) $GLOBALS['ko_printlist']='[<a href="index.php?mod=kobj&item='.$item.'&act=print&what=list&page='.$GLOBALS['page'].'&id='.$pid.($pid?'&pid='.$pid:'').'" target="_blank">'.($GLOBALS['_LANG']['kobj_add_new']?$GLOBALS['_LANG']['kobj_print']:"print").'</a>]';
	else $GLOBALS['ko_printlist']='';

	if(is_array($_it['children'])){
		foreach($_it['children'] as $k=>$v){
			if(!$_it['children_inline']) $GLOBALS['_ko_controls_plus']=' <a href="index.php?mod=kobj&item='.$k.'&act=list&page='.$GLOBALS['page'].'&pid=#ID#">'.$v.'</a>';
			else $GLOBALS['_ko_controls_plus']=' <a href="index.php?mod=kobj&item='.$item.'&act=list&page='.$GLOBALS['page'].'&pid=#ID##pid#ID#">'.$v.'</a>';
		}
	} else $GLOBALS['_ko_controls_plus']='';

	if(function_exists("list_function")) $ret=list_function($pid);
	else $ret=true;
	if($ret) tpl_load($tpl,true,true);
	if(function_exists("on_list_end")) on_list_end($pid);
}

function glist_item(){
	global $result, $result_pages;
	// Can be customly defined
	if(function_exists("on_glist_start")) on_glist_start();
	$iconf = gobj_get_current_item_config();
	
	// Prepare order part for db query
	// Firstly get order field
	if( _get('sort_by') ){
		$order_key = _get('sort_by');
		$order_desc = _get('sort_desc') ? true : false;
	}
	elseif( is_array($iconf['if_sorting']) && $iconf['if_sorting']['default_field'] ){
		$order_key = $iconf['if_sorting']['default_field'];
		$order_desc = $iconf['if_sorting']['default_field_desc'] ? true : false;
	}
	// If we got order key, then prepare the query part
	if( $order_key ){
		if( array_key_exists( $order_key, $iconf['fields'] ) && ! $iconf[ $order_key ]['unsortable'] ){
			$query_order = 'ORDER BY ' . esc_sql($order_key) ;
			$query_order.= $order_desc ? ' DESC' : ' ASC';
		}
		else $query_order = '';
	}
	
	// ?
	if($_it['parent']!=""&&$GLOBALS['_KITEMS'][$_it['parent']]['children_inline']) $page=0;
	else $page=$GLOBALS['page'];
	
	// ?
	if($_it['if_position']) $_it['orderby_field']='k_pos';
	
	// ?
	if($_SESSION['kobj'][$GLOBALS['_ko_item']]['ordr']=='uid' ) $_it['orderby_field'] = 'u.name';

	// ?
	$old_group=-1;
	$ord=$_it['groupby_field']?"order by ".$_it['groupby_field']:"";
	$ord=$_it['orderby_field']?($ord?$ord.",".$_it['orderby_field']:"order by ".$_it['orderby_field'].$GLOBALS['sort_desc']):"";

	// ?
	$_tf=1;

	// ?
	foreach($iconf['fields'] as $fld_key=>$fld)
			if( $fld['title'] ) $_tf++;
	$GLOBALS['colspan']=$_tf+1;

	// Custom function which returns query string. TODO
//	if(function_exists($_it['list_query_func'])) $ro=sqlq($_it['list_query_func'](false)." ".$ord.$GLOBALS['desc']);
//	else $rows = db()->get_results("SELECT*FROM ".$iconf['table']." ".$GLOBALS['_ko_qwhere']." ".$ord);
	
	// Adjust limit part for db query
	if( $iconf['perpage'] ){
		$query_limit = 'LIMIT '.(get_current_page()*$iconf['perpage']-$iconf['perpage']).','.$iconf['perpage'];
	}
	
	$result = db()->get_results("SELECT*FROM ".$iconf['table']." ".$GLOBALS['_ko_qwhere']." ".$query_order." ".$query_limit);
	if( $iconf['perpage'] ){
		$rows_num = db()->get_var("SELECT COUNT(*) FROM ".$iconf['table']." ".$GLOBALS['_ko_qwhere']);
		$result_pages = ceil($rows_num / $iconf['perpage']);
	}

	// ?
//	if(function_exists($_it['list_query_func'])) $ro=sqlq($_it['list_query_func']()." ".$ord.$GLOBALS['desc'].($NPP!=""?" limit ".($page*$NPP).",".$NPP:""));
//	else $ro=sqlq("select * from ".$_it['table']." ".$GLOBALS['_ko_qwhere']." ".$ord.($NPP!=""?" limit ".($page*$NPP).",".$NPP:""));
	
	foreach($result as $row){
		global $_glist_row;
		$_glist_row = $row;
		$rs=true; // ?
		
		// ? 
		if(function_exists("on_list_record_start")) $rs=on_list_record_start($r['id'],$r);
		
		if( $rs ){
			if(is_array($rs)) $r=$rs;
			$_it=$GLOBALS['_KITEMS'][$GLOBALS['_ko_item']];
			
			// ?
//			if($_it['groupby_field']){
//				if($old_group!=$r[$_it['groupby_field']]){
//					$GLOBALS['ko_catheader']=sqlr(str_replace("{id}",$r[$_it['groupby_field']],$_it['groupby_header']),1);
//					tpl_block("klist_catheader",true,true);
//					$old_group=$r[$_it['groupby_field']];
//				}
//			}

			// ?
	//		foreach($r as $k=>$v){
	//			if($_it['fields'][$k]['date_format']!="") $r[$k]=date($_it['fields'][$k]['date_format'],$r[$k]);
	//		}

			// if items controls enabled
			if( $iconf['if_controls'] ){
				$vars['if_controls'] = true;
				if( ! $_it['if_banedit'] ) // ? 
						$vars['edit_url'] = 'index.php?mod=gobj&item='.gobj_get_current_item().'&act=edit&id='.$row->id.'&back_url='.urlencode(get_current_url()); //.($pid?'&pid='.$pid:'').'">';
				if( ! $_it['if_bandelete'] ) // ? 
						$vars['delete_url']='index.php?mod=gobj&item='.gobj_get_current_item().'&act=del&id='.$row->id; //.($pid?'&pid='.$pid:'')';
			}
			
			$GLOBALS['ko_controls_view']=str_replace("#ID#",$r['id'],$GLOBALS['_ko_controls_view']);
			$GLOBALS['ko_controls_plus']=str_replace("#ID#",$r['id'],$GLOBALS['_ko_controls_plus']);

			// ?
			if($_it['if_position']){
	 			if($_it['groupby_field']!="") $gf=" and ".$_it['groupby_field']."='".$r[$_it['groupby_field']]."'";
	 			else $gf="";
				$pid=(int)$_GET['pid'];
				if($_it['parent']) $ifp="and ".$_it['parent_field']."=".$pid; else $ifp="";
				$posro=sqlq("select id from ".$_it['table']." where k_pos<".$r['k_pos']." ".$ifp." ".$gf." order by k_pos desc limit 1");
				if(mysql_num_rows($posro)>0){
					list($nextid)=mysql_fetch_array($posro);
					$GLOBALS['ko_controls_position1']='<a href="index.php?mod=kobj&item='.$GLOBALS['_ko_item'].'&act=position&page='.$GLOBALS['page'].'&id='.$r['id'].($pid?'&pid='.$pid:'').'&repid='.$nextid.'"><img src="img/up_white.gif" border=0 alt="'.($GLOBALS['_LANG']['kobj_position_up']?$GLOBALS['_LANG']['kobj_position_up']:"Up").'"></a>';
				} else $GLOBALS['ko_controls_position1']='<img src="img/up_h_white.gif" border=0>';

				$posro=sqlq("select id from ".$_it['table']." where k_pos>".$r['k_pos']." ".$ifp." ".$gf." order by k_pos asc limit 1");
				if(mysql_num_rows($posro)>0){
					list($nextid)=mysql_fetch_array($posro);
					$GLOBALS['ko_controls_position2']='<a href="index.php?mod=kobj&item='.$GLOBALS['_ko_item'].'&act=position&page='.$GLOBALS['page'].'&id='.$r['id'].($pid?'&pid='.$pid:'').'&repid='.$nextid.'"><img src="img/down_white.gif" border=0 alt="'.($GLOBALS['_LANG']['kobj_position_down']?$GLOBALS['_LANG']['kobj_position_down']:"Down").'"></a>';
				} else $GLOBALS['ko_controls_position2']='<img src="img/down_h_white.gif" border=0>';

				$GLOBALS['ko_controls_position']=$GLOBALS['ko_controls_position1'].$GLOBALS['ko_controls_position2'];
			} else $GLOBALS['ko_controls_position']='';

			$GLOBALS['id']=$r['id'];
			// ?
			if(is_array($_it['additional_controls']))
				foreach($_it['additional_controls'] as $kac=>$vac){
					$href=str_replace("#ID#",$r['id'],$vac['href']);
					$href=str_replace("#PAGE#",$GLOBALS['page'],$href);
					$GLOBALS['ko_controls_'.$kac]="<a href='".$href."'>".$vac['title']."</a>";
				}

			// ?
//			foreach($r as $k=>$v) $GLOBALS['d_'.$k]=strip($v,1);
			
			tpl_process_sub('glist_item',$vars);

			if($_it['children_inline']&&$r['id']==$_GET['pid']){
				foreach($_it['children'] as $ck=>$cv){
					$save_data=array(
									"colspan"=>$GLOBALS['colspan'],
									"item"=>$GLOBALS['item'],
									"act"=>$GLOBALS['act'],
									"_ko_item"=>$GLOBALS['_ko_item'],
									"_ko_controls_edit"=>$GLOBALS['_ko_controls_edit'],
									"_ko_controls_view"=>$GLOBALS['_ko_controls_view'],
									"_ko_controls_plus"=>$GLOBALS['_ko_controls_plus'],
									"kobj_pages"=>$GLOBALS['kobj_pages'],
									"_ko_controls_del"=>$GLOBALS['_ko_controls_del'],
									"tpl_SUBS"=>$GLOBALS['tpl_SUBS'],
									"ko_addnew"=>$GLOBALS['ko_addnew'],
									"ko_printlist"=>$GLOBALS['ko_printlist"']
					);
					tpl_block('klist_childheader',true,true);
					ko_generate_content($ck,"list","",$r['id']);
					tpl_block('klist_childfooter',true,true);

					foreach($save_data as $rk=>$rv) $GLOBALS[$rk]=$rv;
				}
			}
		}
	}
}

function glist_item_field(){
	global $_glist_row;
	
	$row = $_glist_row;
	$iconf = gobj_get_current_item_config();
	foreach($iconf['fields'] as $fld_key => $fld){
		// ?
//				if(function_exists($v['list_act'])&&$v['type']!="module") $r[$k]=$v['list_act']($r[$k],$r['id']);
		if( $fld['list_hide'] )
				continue;
		
		$out = '';
		switch( $fld['type'] ){
			case 'info':
//							if( $fld['date_format'] )
//									$_fld = $r[$k] ? date($v['date_format'], $r[$k]) : "-";
//								elseif ($v['is_reference']) {
//									if (is_array($v['is_reference'])) {
//										foreach ($v['is_reference'] as $k1 => $v1)
//											if ($k1 == $r[$k])
//												$_fld = $v1;
//									} else {
//										$ro1 = sqlq("select * from " . $v['is_reference']);
//										while ($r1 = mysql_fetch_assoc($ro1)) {
//											if ($r1['id'] == $r[$k])
//												$_fld = strip($r1[$v['reference_field']], 1);
//										}
//									}
//								} else
//							$out = strip($v['prefix'] . $r[$k], 0);
				$out = $row->$fld_key;
//							}
				break;
			default:
				$out = $row->$fld_key;
				break;
		}
		if( $fld['type'] === "select"){
			if(is_array($v['is_reference'])) $_fld=$v['is_reference'][$r[$k]];
			else {
				$_v=sqlr("select ".$v['reference_field']." from ".$v['is_reference']." where id='".$r[$k]."'");
				$_fld=strip($_v,1);
			}
		}
		elseif( $fld['type'] === 'query'){
			$q=$v['query'];
			preg_match_all("/\{([^}]+)\}/",$q,$qm);
			foreach($qm[1] as $qk=>$qv) $q=str_replace($qm[0][$qk],$r[$qv],$q);
			$_fld=sqlr($q,1,1);
		}
		elseif( $fld['type'] === 'reflist'){
			$ro1=sqlq("select o.".($v['other_field']?$v['other_field']:"name")." from ".$v['ref_table']." r,".$v['other_table']." o where r.".$v['ref_id_other']."=o.id and r.".$v['ref_id_this']."=".$r['id']);
			$out=array();
			while($r1=mysql_fetch_assoc($ro1)) $out[]=strip($r1[$v['other_field']?$v['other_field']:"name"]);
			$_fld=join(", ",$out);
		}
		elseif( $fld['type'] === 'date'){
			$dateformat=$v['date_format']?$v['date_format']:"d/m/Y";
			if($r[$k]!=0) $_fld=date($dateformat,$r[$k]);
			else $_fld="&nbsp;";
		}
		elseif( $fld['type'] === 'checkbox'){
			if($r[$k]!=0) $_fld="Yes";
			else $_fld="No";
		}
		elseif( $fld['type'] === 'datetime'){
			$dateformat=$v['date_format']?$v['date_format']:"d/m/Y H:i";
			if($r[$k]!=0) $_fld=date($dateformat,$r[$k]);
			else $_fld="&nbsp;";
		}
		elseif( $fld['type'] === 'module'){
			require_once($v['module']);
			$_fld=$v['list_act']($r,$r['id']);
		}
		elseif( $fld['type'] === 'file'&&$v['show_image']){
			if($v['make_thumb']) $_fld="<img src='img.php?src=".$v['save_path']."/".$r[$k]."&w=".$v['make_thumb']."'>";
			else $_fld="<img src='".$v['save_path']."/".$r[$k]."'>";
		}
		else {
			if($v['html_false']) $_fld=strip($r[$k],0);
			else $_fld=strip($r[$k],1);
			if($v['is_url']) $_fld="<a href='".$_fld."' target='_blank'>".$_fld."</a";
			if($v['is_email']) $_fld="<a href='mailto:".$_fld."' target='_blank'>".$_fld."</a";
		}

		if($v['is_number']&&$_fld=="") $_fld="0";

//					$__fld.="<td>".($v['display']?str_replace("###",$_fld,$v['display']):$_fld)."</td>";
		// adds each field output to item row output
		tpl_process_sub('glist_item_field',array(
				'field' => $out), true, true);
	}
}

// Function which runs by %SUB gobj_pager% in template.
function gobj_pager(){
	global $result_pages;
	$iconf = gobj_get_current_item_config();
	$current_page = get_current_page();
	$pager_items = '';
//	$gobj_item = gobj_get_current_item();
	$query_args = get_current_url_params_array();
	
	// if there is only one page or pages is disabled then we should not show anything
	if( ! $iconf['perpage'] || $result_pages < 2 )
		return false;
	
	for ($x = 1; $x <= $result_pages; $x++) {
		if ($x === $current_page)
			$subname = 'gpage_current';
		else{
			$subname = 'gpage_link';
			$query_args['page'] = $x;
			$page_url = 'index.php?'.http_build_query($query_args);
		}

		$pager_items .= tpl_process_sub($subname, array(
				'page_num' => $x,
				'page_url' => $page_url ), true, false);
	}

		// ?
//		if ($_it['parent']) {
//			if ($GLOBALS['_KITEMS'][$_it['parent']]['children_inline'])
//				$GLOBALS['kobj_pages'] = "";
//		}
	
	
	$prev_page_num = $current_page > 1 ? $current_page - 1 : 1;
	$next_page_num = $current_page < $result_pages ? $current_page + 1 : $result_pages;

	$pager = tpl_process_sub('gobj_pager', array(
					'gobj_pages' => $pager_items,
					'gobj_item' => $gobj_item,
					'prev_page_num' => $prev_page_num,
					'next_page_num' => $next_page_num ), true, true);
	gobj_set_tpl_var('gobj_pager', $pager);
}

function glist_headers(){
	$iconf = gobj_get_current_item_config();
	foreach( $iconf['fields'] as $k=>$v ){
		if( $v['list_hide'] )
				continue;
		$vars = array();
		$vars['fld_title'] = $v['title'];
		if( $iconf['if_sorting'] && ! $v['unsortable'] ){
			$vars['is_sortable'] = true;
			$vars['fld_sort_url_asc'] = 'index.php?mod=gobj&item='.gobj_get_current_item().'&act=list&sort_by='.$k;
			$vars['fld_sort_url_desc'] = 'index.php?mod=gobj&item='.gobj_get_current_item().'&act=list&sort_by='.$k.'&sort_desc=1';
			// check if listing is ordered by current field
			if( get_sort_field() === $k ){
				if( get_sort_direction() === 'desc' )
						$vars['sort_desc_active'] = true;
				else
						$vars['sort_asc_active'] = true;
			}
		}
		
		tpl_process_sub('glist_headers', $vars);
	}
}

function kobj_if_filters(){
	$_it=$GLOBALS['_KITEMS'][$GLOBALS['_ko_item']];
	if(is_array($_it['filters'])){
		$GLOBALS['kobj_filters']=array();
		foreach($_it['filters'] as $k=>$v){
			$GLOBALS['kobj_filters'][]=array(
					"stitle"=>$v['title'],
					"sname"=>$k,
					"soptions"=>$v['func']($_SESSION['ko_filters'][$k])
			);
		}
		tpl_block("kobj_if_filters",1,1);
	}
}

function kobj_filters(){
	foreach($GLOBALS['kobj_filters'] as $v){
		$GLOBALS['stitle']=$v['stitle'];
		$GLOBALS['sname']="ko_filters[".$v['sname']."]";
		$GLOBALS['soptions']=$v['soptions'];
		tpl_block("kobj_filters",1,1);
	}
}

function kobj_items_filters($_it){
	$r=array();
	if(is_array($_SESSION['ko_filters'])) foreach($_SESSION['ko_filters'] as $k=>$v) if($v!=""&&isset($_it[$k])) $r[]=$k."='".$v."'";
	return $r;
}

function gobj_get_current_item_config(){
	global $current_gobj_item_config;
	return $current_gobj_item_config;
}

function gobj_get_current_item(){
	global $current_gobj_item;
	return $current_gobj_item;
}
