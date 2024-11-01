<?php 
Class WP_Ajax{
	
	function __construct( $ajax_prefix = 'a', $nopriv_prefix = 'n' ) {
		$regex = "/^($ajax_prefix)?($nopriv_prefix)?_|^($norpriv_prefix)?($ajax_prefix)?_/";
		$methods = get_class_methods( $this );
		
		if( !isset( $methods ) )
			return false;
		
		foreach( $methods as $method ) {
			if( preg_match( $regex, $method, $matches ) ) {
				if( (int)$matches > 1 ){
					$action = preg_replace( $regex, '', $method );
					if( count( $matches ) == 3 ) {
						add_action( 'wp_ajax_'.$action, array( &$this, $method ) );
						add_action( 'wp_ajax_nopriv_'.$action, array( &$this, $method ) );
					} else {
						if( $matches[1] == $ajax_prefix ) {
							add_action( 'wp_ajax_'.$action, array( &$this, $method ) );
						} else {
							add_action( 'wp_ajax_nopriv_'.$action, array( &$this, $method ) );
						}
					}
				}			
			}		
		}
	}
}

Class SCF_WP_Ajax extends WP_Ajax{

	function __construct(){
		parent::__construct();
	}
	
	//ajax function
	function a_sanitizeFieldName(){
		$fieldname = ( isset( $_GET['fieldname'] ) && !empty( $_GET['fieldname'] ) ? $_GET['fieldname'] : '' );
		echo sanitize_title( $fieldname );
		die;
	}
}
?>