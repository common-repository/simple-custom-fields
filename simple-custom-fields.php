<?php
/*
Plugin Name: Simple Custom Fields - BETA
Version: 0.1
Plugin URI: http://www.beapi.fr
Description: A plugin for WordPress that allow to create visual custom fields for custom post types and taxonomies - BETA VERSION DON'T USE NOW
Author: Be API
Author URI: http://www.beapi.fr

Copyright 2011 - BeAPI Team (technique@beapi.fr)

Todo :
	bug with regexp and stripslashes
	messages
	conflit SCF avec plugin custom fields existant 
	sauvegarder les CF même quand il s'agit de révisions
	
	----
	other field types
	
	-----
	add metaboxes on content edition
	regexp and error 
	autoadd on the_content
	
	---- BUGS ---
	
	- si je crée un champ et que je modifie l'ordre directement ensuite, j'ai une erreur
	
	-----
	
	ask for a wordpress.org depository
	
	-----
*/

define( 'SCF_VERSION', '1.0' );
define( 'SCF_OPTIONS_NAME', 'simple-custom-fields' ); // Option name for save settings
define( 'SCF_URL', plugins_url( '', __FILE__ ) );
define( 'SCF_DIR', dirname( __FILE__ ) );
define( 'SCF_SETTINGS', 'scf-settings' );


require( SCF_DIR . '/inc/functions.plugin.php');
require( SCF_DIR . '/inc/class.client.php');

//admin
require( SCF_DIR . '/inc/class.admin.php' );

//ajax
require( SCF_DIR . '/inc/class.ajax.php' );

// Composants
require( SCF_DIR . '/composants/text.class.php');
require( SCF_DIR . '/composants/textarea.class.php');
require( SCF_DIR . '/composants/dropdown.class.php');

// Activation, uninstall
register_activation_hook( __FILE__, 'SimpleCustomFields_Install'   );
register_uninstall_hook ( __FILE__, 'SimpleCustomFields_Uninstall' );

// Init SimpleCustomFields
function SimpleCustomFields_Init() {
	global $simple_custom_fields;

	// Load translations
	load_plugin_textdomain ( 'simple-custom-fields', false, basename( rtrim( dirname( __FILE__ ), '/' ) ) . '/languages' );
	
	// Load client
	$simple_custom_fields['client'] = new SimpleCustomFields_Client();
	
	// Admin
	if ( is_admin() ) {
		
		$simple_custom_fields['admin'] = new SimpleCustomFields_Admin();
		$simple_custom_fields['ajax'] = new WP_Ajax();
	}
	
	// Composants
	$simple_custom_fields['composant-text'] = new SCF_Composant_Text();
	$simple_custom_fields['composant-textarea'] = new SCF_Composant_Textarea();
	$simple_custom_fields['composant-dropdown'] = new SCF_Composant_Dropdown();
	
}
add_action( 'plugins_loaded', 'SimpleCustomFields_Init' );
?>