<?php
/*
Plugin Name: Admin Supermenu
Plugin URI: http://factoryjoe.com/projects/wp-admin-supermenu
Description: WP-Admin-Supermenu is a plugin for adding jQuery Superfish dropdown menus to the WordPress Admin interface. 
Author: FactoryCity
Author URI: http://factoryjoe.com/
Version: 0.1

Copyleft (c) 2007 Chris Messina. Some rights reserved.

Dual licensed under the MIT and GPL licenses:
	http://www.opensource.org/licenses/mit-license.php
	http://www.gnu.org/licenses/gpl.html

YOU SHOULD DELETE THIS CHANGELOG TO REDUCE FILE SIZE:

0.1 Initial release.

TODO

* check for other admin menu plugins

*/

/**
* Adds in the stylesheets for the Superfish menus
**/
function wp_admin_supermenu_css() {
  $wp_admin_supermenu_styles = '<link rel="stylesheet" type="text/css" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-admin-supermenu/css/supermenu.css" />' . "\n";
  echo($wp_admin_supermenu_styles);
}


/**
* Adds in the necessary JavaScript files
**/
function wp_admin_supermenu_add_scripts() {
	if (function_exists('wp_enqueue_script') && function_exists('wp_register_script')) {
		wp_register_script('jquery', get_bloginfo('wpurl') . '/wp-content/plugins/wp-admin-supermenu/js/jquery.js');
		wp_enqueue_script('jquery.dimensions', get_bloginfo('wpurl') . '/wp-content/plugins/wp-admin-supermenu/js/jquery.dimensions.min.js', array('jquery'), '1.1.2');
		wp_enqueue_script('jquery.hoverIntent', get_bloginfo('wpurl') . '/wp-content/plugins/wp-admin-supermenu/js/jquery.hoverIntent.js', array('jquery'), '1.1.2');
		wp_enqueue_script('jquery.superfish', get_bloginfo('wpurl') . '/wp-content/plugins/wp-admin-supermenu/js/jquery.superfish.js', array('jquery'), '1.3.2');
	} else {
		wp_admin_supermenu_add_scripts_legacy();
	}
}
function wp_admin_supermenu_add_scripts_legacy() {
	if (function_exists('wp_enqueue_script') && function_exists('wp_register_script')) { wp_admin_supermenu_add_scripts(); return; }
	print('<script type="text/javascript" src="'.get_bloginfo('wpurl') . '/wp-content/plugins/wp-admin-supermenu/js/jquery.js"></script>'."\n");
}


/* Main function : creates the new set of intricated <ul> and <li> */
function wp_admin_supermenu() {
	
	if (function_exists('wp_admin_tiger_css')) {
		$tiger = true;
	} else {
		$tiger = false;
	}

	$menu = wp_admin_supermenu_build ();
	
	$supermenu = '';
	$printsub = 1;
	
	foreach ($menu as $k=>$v) {
		$url 	= $v['url'];
		$name 	= $k;
		$anchor = $v['name'];
		$class	= $v['class'];
    $active = "supermenu-" . sanitize_title_with_dashes(strtolower($anchor));

    if ($class)
		  $supermenu .= "<li id=\"". $active . "\"><a href=\"" . $url . "\"" . $class . ">" . $anchor . "</a>";
    else
		  $supermenu .= "<li id=\"". $active . "\"><a href=\"" . $url . "\">" . $anchor . "</a>";
		if (is_array($v['sub'])) {
			
			$ulclass='';
			if ($class) $ulclass = " class=\"current\"";
			if ($class) $active_menu = $active;
      
			$supermenu .= "<ul$ulclass>";

			foreach ($v['sub'] as $subk=>$subv) {
				$suburl = $subv['url'];
				$subanchor = $subv['name'];
				$subclass='';
				if (array_key_exists('class',$subv)) $subclass=$subv['class'];
				$supermenu .= "<li id=\"supermenu-" . sanitize_title_with_dashes(strtolower($anchor)) . "-" . sanitize_title_with_dashes(strtolower($subanchor)) . "\"><a href=\"" . $suburl . "\"" . $subclass . ">" . $subanchor . "</a></li>";
			}
			$supermenu .= "</ul>";
		} else {
			$supermenu .= "<ul><li class='altmenu_empty' title='This menu has no sub menu'><small>&#8230;</small></li><li>&nbsp;</li><li>&nbsp;</li><li>&nbsp;</li></ul>";
			if ($class) $printsub = 0;
		}
		$supermenu .="</li> ";			
	}
	
	wp_admin_supermenu_printjs($supermenu, $printsub, $active_menu);
}

/* Core stuff : builds an array populated with all the infos needed for menu and submenu */
function wp_admin_supermenu_build () {
	global $menu, $submenu, $plugin_page, $pagenow;
	
	/* Most of the following garbage are bits from admin-header.php,
	 * modified to populate an array of all links to display in the menu
	 */

	$self = preg_replace('|^.*/wp-admin/|i', '', $_SERVER['PHP_SELF']);
	$self = preg_replace('|^.*/plugins/|i', '', $self);
	
	get_admin_page_parent();
	
	$altmenu = array();
	
	/* Step 1 : populate first level menu as per user rights */
	foreach ($menu as $item) {
		// 0 = name, 1 = capability, 2 = file
		if ( current_user_can($item[1]) ) {
			if ( file_exists(ABSPATH . "wp-content/plugins/{$item[2]}") )
				$altmenu[$item[2]]['url'] = get_settings('siteurl') . "/wp-admin/admin.php?page={$item[2]}";			
			else
				$altmenu[$item[2]]['url'] = get_settings('siteurl') . "/wp-admin/{$item[2]}";

			if (( strcmp($self, $item[2]) == 0 && empty($parent_file)) || ($parent_file && ($item[2] == $parent_file)))
			$altmenu[$item[2]]['class'] = " class=\"current\"";
			
			$altmenu[$item[2]]['name'] = $item[0];

			/* Windows installs may have backslashes instead of slashes in some paths, fix this */
			$altmenu[$item[2]]['name'] = str_replace(chr(92),chr(92).chr(92),$altmenu[$item[2]]['name']);
		}
	}
	
	/* Step 2 : populate second level menu */
	foreach ($submenu as $k=>$v) {
		foreach ($v as $item) {
			if (array_key_exists($k,$altmenu) and current_user_can($item[1])) {
				
				// What's the link ?
				$menu_hook = get_plugin_page_hook($item[2], $k);
				if (file_exists(ABSPATH . "wp-content/plugins/{$item[2]}") || ! empty($menu_hook)) {
					if ( 'admin.php' == $pagenow )
						$link = get_settings('siteurl') . "/wp-admin/admin.php?page={$item[2]}";
					else
						$link = get_settings('siteurl') . "/wp-admin/{$k}?page={$item[2]}";
				} else {
					$link = get_settings('siteurl') . "/wp-admin/{$item[2]}";
				}
				/* Windows installs may have backslashes instead of slashes in some paths, fix this */
				$link = str_replace(chr(92),chr(92).chr(92),$link);
				
				$altmenu[$k]['sub'][$item[2]]['url'] = $link;
				
				// Is it current page ?
				$class = '';
				if ( (isset($plugin_page) && $plugin_page == $item[2] && $pagenow == $k) || (!isset($plugin_page) && $self == $item[2] ) ) $class=" class=\"current active\"";
				if ($class) {
					$altmenu[$k]['sub'][$item[2]]['class'] = $class;
					$altmenu[$k]['class'] = $class;
				}
				
				// What's its name again ?
				$altmenu[$k]['sub'][$item[2]]['name'] = $item[0];
			}
		}
	}
	
	return ($altmenu);
}

/**
* Adds in the InnerHTML that replaces the existing menus
* Activates the active secondary menu
* Activates the SuperFish dropdowns
**/
function wp_admin_supermenu_printjs ($admin = '', $sub = 1, $active_menu = '') {
  $admin = str_replace("\"","'",$admin);
	print "<script type=\"text/javascript\">\n
	jQuery(document).ready(function(){\n
	  jQuery('#adminmenu').html(\"" . $admin . "\");
	  jQuery('#" . $active_menu . "').addClass('active'); \n
    jQuery('#adminmenu').superfish({\n
      hoverClass  : 'sfHover',\n
    	pathClass   : 'active',\n
    	animation   : { opacity:'show', height:'show' },\n
    	speed       : 'fast',\n 
    	delay       : 500,\n
    	onShow      : function(){ /*your callback function here*/ } /*new to v1.3.2*/ \n
    });\n
    jQuery('#adminmenu').css('width',eval(\"jQuery(window).innerWidth() - 26\"));\n
    jQuery('#adminmenu ul').css('width',eval(\"jQuery(window).innerWidth() - 65\"));\n
  });\n
  </script>";
}

add_action('admin_head', 'wp_admin_supermenu_css');
add_action('init', 'wp_admin_supermenu_add_scripts');
add_action('admin_footer', 'wp_admin_supermenu');

?>