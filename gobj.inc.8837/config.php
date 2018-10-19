<?php

define('DB_NAME',	'naels.blog');
define('DB_USER',	'root');
define('DB_PASS',	'');
define('DB_HOST',	'localhost');
define('DB_CHARSET',	'utf8');

define('SITEURL', 'http://localhost/ZAMZAMLAB.COM/naels-blog/root/' );
define('ADMIN_SITEURL', 'http://localhost/ZAMZAMLAB.COM/naels-blog/root/adminy4/' );
define('AUTH_COOKIE',	'sofisdf7897234dfg&&1212323Qworuowirua829asddoa78999as98d0OIdqweuao');
define('AUTH_SALT',		'NVuvis8A?,/as)(ASU)D34HSD3f8&^S$%^>#<#2=M3< spdoadi(*0as98d0OIduao');

/*
 * on_glist_start() - function runs before listing. like a do action hook
 */


/*
 * multiuser -	if true then we should use db to login,
 *							if false, then we should use login/pass from config
 * username - if multiuser is false then we use this field to enter the site
 * password - password for admin username
 *
 *
 * default_item - item will be used if no itemwas specified. Like after login redirect.
 * item_default_config - sets a default values for each item if it was not defined
 *
 */
$config = array(
	'multiuser' => false,
	'username' => 'adminov',
	'password' => 'qqqqqq',
	'sitename' => 'Nael\'s blog',
	'admin_theme' => 'default',

	"startitem"=>"plans", // ?
	"logout_url"=>"index.php", // ?
	"login_url"=>"index.php", // ?
	"options"=>array( // ?
		"admin_login"=>array("type"=>"text","name"=>"Admin login"), // ?
		"admin_pass"=>array("type"=>"text","name"=>"Admin password"), // ?
		"site_url"=>array("type"=>"text","name"=>"Site URL"), // ?
		"site_name"=>array("type"=>"text","name"=>"Site title"), // ?
		"site_email"=>array("type"=>"text","name"=>"Site email"), // ?
		"domain"=>array("type"=>"text","name"=>"Main domain name"), // ?
		"paypal_id"=>array("type"=>"text","name"=>"Paypal ID"), // ?
		"worldpay_id"=>array("type"=>"text","name"=>"WorldPay ID"), // ?
		"callbackPW"=>array("type"=>"text","name"=>"WorldPay callback password"), // ?
	),
	"tmp"=>"./tmp", // ?
	'default_item' => 'partners',
	'item_default_config' => array(
			'perpage' => 50,
			'if_controls' => true,
			'if_addnew' => true,
			'templates' => array(
					'list' => 'gobj_list.html',
					'edit' => 'gobj_edit.html'
			),
	),
	/*
	 * table : mysql table name to use
	 * titles : array:
	 *		- page_title - will be used for header title
	 * deftitle : will be used for titles as default name of item
	 * perpage : how many rows should be displayed per page
	 * if_controls : adds edit/delete ability
	 * if_addnew : adds "add new" ability
	 * if_sorting : bool|array - adds sorting ability to list table
	 *		if array:
	 *		* default_field - default field for sorting
	 *		* default_field_desc - default sorting direction
	 * templates : array() - for custom template file
	 * fields : array:
	 *		array key : db table field name
	 *		- type : info|text|select|textarea|switch|...
	 *		* title : field display title
	 *		* switch_type : for type=switch : toggle|... default: toggle
	 *		* values : array. key/value pair. for fields like switch, select.
	 *		* list_hide : hide from listing view
	 *		* unsortable : disable sorting option for current field
	 *		* unique : value for this field should be unique
	 *		* not_required : value can be empty
	 */
	'items' => array(
//	"home"=>array(
//			"_perpage"=>50,
//			"if_controls"=>true,
//			"if_addnew"=>true,
//			"orderby_field"=>"title",
//			"if_position" => true,
//			"deftitle"=>"page",
//			"fields"=>array(
//					"id"=>array("type"=>"info",'title'=>'ID',"unique"=>true),
//					"title"=>array("type"=>"text",'title'=>'Title',"unique"=>true),
//					"text"=>array("type"=>"richedit", 'edit_name' => 'Text', 'width' => 700, 'height' => 500),
//			)
//	),
			'partners' => array(
					'table' => 'partners',
//					'templates' => array( // if custom template is needed
//							'list' => ''
//					),
					'deftitle' => 'partner',
					'if_sorting' => array(
							'default_field' => 'enabled',
							'default_field_desc' => true
					),
					"if_view"=>true, // ?

//						"orderby_field" => "name", // not neccesary anymore
						"modules" => array("modules/servers.php"), // ?

					'fields' => array(
							'id' => array('type' => 'info', 'title' => 'ID'),
							'title' => array('type' => 'text', 'title' => 'Title', 'unique' => true),
							'domain' => array('type' => 'text', 'title' => 'Domain'),
							'cashback' => array('type' => 'text', 'title' => 'Cashback'),
							'cashback_title' => array('type' => 'text', 'title' => 'Cashback Title', 'unsortable' => true),
							'cashback_time' => array('type' => 'text', 'title' => 'Cashback Time', 'unsortable' => true),
							'enabled' => array('type' => 'switch', 'title' => 'Enabled',
									'switch_type' => 'toggle',
									'labels' => array('No','Yes') ),
//  `slug` varchar(50) DEFAULT NULL,
//  `logo` varchar(255) DEFAULT NULL,
//  `settings` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
//								"ftpport" => array("type" => "text", "edit_name" => 'Port', 'default' => 21),
//								"sload" => array("type" => "module", 'module' => 'modules/servers.php', 'title' => 'Current load of the server', 'edit_act' => 'server_load2', 'save_act' => 'server_load2', 'list_act' => 'server_load', 'not_required' => true),
//								"login" => array("type" => "text", 'title' => 'Login'),
//								"passw" => array("type" => "text", 'title' => 'Password'),
					)
				),
//				"users" => array(
//						"table" => "users",
//						"_perpage" => 100,
//						"if_controls" => true,
//						"if_addnew" => true,
//						"orderby_field" => "ulogin",
//						"deftitle" => "user",
//						//"children"=>array("packages"=>"<img src='img/plus_white.gif' border=0 alt='Open'>"),
//						//"children_inline"=>true,
//						"modules" => array("modules/users.php"),
//						"templates" => array("list" => "list_users.html"),
//						"fields" => array(
//								"id" => array("type" => "info", 'title' => 'ID'),
//								"ulogin" => array("type" => "text", 'title' => 'Username', "unique" => true),
//								"upass" => array("type" => "text", "edit_name" => 'Password'),
//								"email" => array("type" => "text", 'title' => 'Email'),
//								"gender" => array("type" => "text", 'title' => 'Gender'),
//								"birth" => array("type" => "text", 'title' => 'Birth year'),
//								"country" => array("type" => "text", 'title' => 'Country'),
//								"date" => array("type" => "info", "edit_name" => 'Registration Date', "date_format" => 'd F, Y - H:i'),
//								"activated" => array("type" => "checkbox", "edit_name" => 'Activated'),
//						)
//				),
//				"plans" => array(
//						"table" => "plans",
//						"_perpage" => 200,
//						"if_controls" => true,
//						"if_addnew" => true,
//						"orderby_field" => "name",
//						"deftitle" => "plan",
//						"templates" => array("list" => "list_plans.html"),
//						"modules" => array("modules/plans.php"),
//						"fields" => array(
//								"name" => array("type" => "text", 'title' => 'Title', "unique" => true),
//								"setup_fee" => array("type" => "text", 'title' => 'Setup fee ($)', "is_number" => true, "if_old_hide" => true),
//								"_trial" => array("type" => "text", 'title' => 'Trial Period', "if_new_hide" => true, "if_old_hide" => true, "hidden" => true),
//								"trial" => array("type" => "checkbox", "edit_name" => 'Trial Period', "if_old_hide" => true),
//								"trial_duration" => array("type" => "text", "edit_name" => 'Trial Period Duration (days)', "is_number" => true, "not_required" => true, "if_old_hide" => true),
//								"_subscription" => array("type" => "text", 'title' => 'Subscription Price', "if_new_hide" => true, "if_old_hide" => true, "hidden" => true),
//								"subscription_type" => array("type" => "select", "edit_name" => 'Subscription Period Type', "is_reference" => array("month" => "month", "year" => "year"), "if_old_hide" => true),
//								"subscription_price" => array("type" => "text", "edit_name" => 'Subscription Period Price ($)', "is_number" => true, "if_old_hide" => true),
//								"gallery" => array("type" => "checkbox", 'title' => 'Includes Gallery module', "if_old_hide" => true),
//								"blog" => array("type" => "checkbox", 'title' => 'Includes Blog module', "if_old_hide" => true),
//								"activated" => array("type" => "checkbox", "edit_name" => 'Activated'),
//						)
//				),
//				"templates" => array(
//						"table" => "tpls",
//						"orderby_field" => "name",
//						"deftitle" => "template",
//						"modules" => array("modules/templates.php"),
//						"fields" => array(
//								"name" => array("type" => "text", 'title' => 'Title', "unique" => true),
//								"thumb" => array("type" => "file", 'title' => 'Thumbnail', 'save_path' => '../uploads/tpls', 'make_thumb' => 100, 'show_image' => true),
//								"tpl" => array("type" => "file", "edit_name" => 'Template', 'save_path' => '../uploads/tpls'),
//								"activated" => array("type" => "checkbox", 'title' => 'Active'),
//						)
//				),
//				"wordpress_templates" => array(
//						"table" => "wordpress_tpls",
//						"orderby_field" => "name",
//						"deftitle" => "wordpress template",
//						"modules" => array("modules/wordpress_templates.php"),
//						"fields" => array(
//								"name" => array("type" => "text", 'title' => 'Title', "unique" => true),
//								"main_tpl_id" => array("type" => "select", 'title' => 'Main Template Title', "is_reference" => "tpls", "reference_field" => "name"),
//								"thumb" => array("type" => "file", 'title' => 'Thumbnail', 'save_path' => '../uploads/wordpress_tpls', 'make_thumb' => 100, 'show_image' => true),
//								"tpl" => array("type" => "file", "edit_name" => 'Template', 'save_path' => '../uploads/wordpress_tpls'),
//						)
//				),
//				"packages" => array(
//						"table" => "packages",
//						"if_addnew" => false,
//						"orderby_field" => "date",
//						"deftitle" => "site",
//						//"parent"=>"users",
//						//"parent_field"=>"uid",
//						"modules" => array("modules/packages.php"),
//						"fields" => array(
//								"id" => array("type" => "info", 'title' => 'ID'),
//								"pid" => array("type" => "select", 'title' => 'Plan', "is_reference" => "plans", "reference_field" => "name"),
//								"uid" => array("type" => "select", 'title' => 'User', "is_reference" => "users", "reference_field" => "ulogin"),
//								"server_id" => array("type" => "select", 'title' => 'Server', "is_reference" => "servers", "reference_field" => "name"),
//								"expire_date" => array("type" => "date", 'title' => 'Expire date'),
//								"domain" => array("type" => "text", 'title' => 'Domain', 'not_required' => true),
//								"subdomain" => array("type" => "text", 'title' => 'Subdomain', 'not_required' => true),
//								"date" => array("type" => "info", 'title' => 'Add date', "date_format" => "d F, Y - H:i"),
//								"activated" => array("type" => "checkbox", 'title' => 'Activated'),
//						)
//				),
//				"allpackages" => array(
//						"table" => "packages",
//						"orderby_field" => "date",
//						"deftitle" => "site",
//						"if_addnew" => false,
//						//"parent"=>"users",
//						//"parent_field"=>"uid",
//						"templates" => array("list" => "list_allpackages.html"),
//						"modules" => array("modules/allpackages.php"),
//						"fields" => array(
//								"id" => array("type" => "info", 'title' => 'ID'),
//								"pid" => array("type" => "select", 'title' => 'Plan', "is_reference" => "plans", "reference_field" => "name"),
//								"uid" => array("type" => "select", 'title' => 'User', "is_reference" => "users", "reference_field" => "ulogin"),
//								"server_id" => array("type" => "select", 'title' => 'Server', "is_reference" => "servers", "reference_field" => "name"),
//								"expire_date" => array("type" => "date", 'title' => 'Expire date'),
//								"domain" => array("type" => "text", 'title' => 'Domain', 'not_required' => true),
//								"subdomain" => array("type" => "text", 'title' => 'Subdomain', 'not_required' => true),
//								"date" => array("type" => "info", 'title' => 'Add date', "date_format" => "d F, Y - H:i"),
//								"activated" => array("type" => "checkbox", 'title' => 'Activated'),
//						)
//				),
//				"sites_chosen" => array(
//						"table" => "sites_chosen",
//						"if_position" => true,
//						"orderby_field" => "k_pos",
//						"deftitle" => "featured site",
//						"fields" => array(
//								"idsite" => array("type" => "text", 'title' => 'Site ID', 'is_number' => true),
//								"thumb" => array("type" => "file", 'title' => 'Thumbnail', 'save_path' => '../uploads/chosen', 'make_thumb' => 100, 'show_image' => true),
//						)
//				),
//				"pm_reports" => array(
//						"table" => "transactions",
//						"if_addnew" => false,
//						"if_orderby" => true,
//						"list_query_func" => "sorting",
//						"orderby_field" => "date",
//						"deftitle" => "transaction",
//						"modules" => array("modules/transactions.php"),
//						"fields" => array(
//								"pid" => array("type" => "select", 'title' => 'Package ID', 'is_reference' => 'packages', 'reference_field' => 'id'),
//								"uid" => array("type" => "select", 'title' => 'User', 'is_reference' => 'users', 'reference_field' => 'ulogin'),
//								"amount" => array("type" => "text", 'title' => 'Amount'),
//								"payer_id" => array("type" => "text", 'title' => 'Payer ID'),
//								"purpose" => array("type" => "text", 'title' => 'Purpose'),
//								"payment_type" => array("type" => "text", 'title' => 'Payment Type'),
//								"date" => array("type" => "date", 'title' => 'Date', 'date_format' => 'd/m/y - H:i'),
//						)
//				),
//				"invoices" => array(
//						"table" => "invoices",
//						"if_addnew" => false,
//						"if_orderby" => false,
//						"orderby_field" => "date",
//						"deftitle" => "invoice",
//						"templates" => array("list" => "list_invoices.html"),
//						"modules" => array("modules/invoices.php"),
//						"fields" => array(
//								"pid" => array("type" => "select", 'title' => 'Package ID', 'is_reference' => 'packages', 'reference_field' => 'id'),
//								"uid" => array("type" => "select", 'title' => 'User', 'is_reference' => 'users', 'reference_field' => 'ulogin'),
//								"amount" => array("type" => "text", 'title' => 'Package Amount'),
//								"setup_fee" => array("type" => "text", 'title' => 'Setup Fee'),
//								"regdomain_amount" => array("type" => "text", 'title' => 'Domain Amount'),
//								"period" => array("type" => "text", 'title' => 'Period'),
//								"period_type" => array("type" => "select", "edit_name" => 'Period Type', "is_reference" => array("month" => "month", "year" => "year")),
//								"date" => array("type" => "info", 'title' => 'Date', 'date_format' => 'd/m/y - H:i'),
//						)
//				),
//				"zones" => array(
//						"table" => "adv_zones",
//						"if_controls" => true,
//						"if_addnew" => true,
//						"orderby_field" => "name",
//						"headers" => array(
//								"list" => 'Zones list',
//								"edit" => 'Edit Zone',
//								"add" => 'Add New Zone',
//								"view" => 'View Zone'
//						),
//						"modules" => array("modules/adv_zones.php"),
//						"templates" => array("list" => "list_adv_zones.html"),
//						"fields" => array(
//								"name" => array("type" => "text", 'title' => 'Zone Name', "unique" => true),
//								"width" => array("type" => "text", 'title' => 'Slot Width'),
//								"height" => array("type" => "text", 'title' => 'Slot Height'),
//						)
//				),
//				"banners" => array(
//						"table" => "adv_items",
//						"if_controls" => true,
//						"if_addnew" => true,
//						"userlevel" => 1,
//						"_perpage" => 10,
//						"headers" => array(
//								"list" => 'Banners list',
//								"edit" => 'Edit Banner',
//								"add" => 'Add New Banner',
//								"view" => 'View Banner'
//						),
//						"modules" => array("modules/adv_items.php"),
//						"templates" => array("list" => "list_adv_items.html"),
//						"fields" => array(
//								"activated" => array("type" => "select", 'title' => 'Activated', "is_reference" => array("0" => "No", "1" => "Yes")),
//								"zone_id" => array("type" => "select", 'title' => 'Zone Name', "is_reference" => "adv_zones", "reference_field" => "name"),
//								"name" => array("type" => "text", 'title' => 'Title'),
//								"url" => array("type" => "text", 'title' => 'URL'),
//								"image" => array("type" => "file", 'title' => 'Image', "save_path" => '../banners_images', "show_image_link" => true),
//						)
//				),
//				"emails" => array(
//						"table" => "emails",
//						"if_controls" => true,
//						"if_addnew" => true,
//						"headers" => array(
//								"list" => 'Emails list',
//								"edit" => 'Edit Email',
//								"add" => 'Add New Email',
//								"view" => 'View Email'
//						),
//						"modules" => array("modules/emails.php"),
//						"templates" => array("list" => "list_emails.html"),
//						"fields" => array(
//								"name" => array("type" => "info", 'title' => 'Name'),
//								"info" => array("type" => "info", "edit_name" => 'Variables'),
//								"text" => array("type" => "textarea", 'title' => 'Text', "html_false" => true),
//						)
//				)
		)
);
