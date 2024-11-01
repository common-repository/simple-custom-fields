<?php
class SimpleCustomFields_Admin {

	protected $admin_url = '';
	protected $admin_slug = 'scf-page';
	
	// Error management
	protected $message = '';
	protected $status  = '';
	
	//global settings
	public $settings = array();
	
	protected $post_types;
	protected $taxonomies;
	
	protected $cpt_or_tax = array();
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @author Benjamin Niess
	 */
	function SimpleCustomFields_Admin() {
		
		//call the common constructor (externalised for other classes
		$this->commonConstructor();
		
		add_action( 'admin_init', array( &$this, 'checkAdminPost') );
		add_action( 'add_meta_boxes', array( &$this, 'addAdminMetaboxes'), 9, 2 );
		add_action( 'admin_menu', array( &$this, 'addMenu' ) );
		add_action( 'save_post', array( &$this, 'saveFieldsPostdata') );
		add_action( 'admin_footer', array( &$this, 'addJsFieldCheck') ) ;
		
		// Style, Javascript
		add_action( 'admin_enqueue_scripts', array(&$this, 'addRessources') );
	
	}
	
	/*
	 * A common constructor called by this class and each component classes
	 *
	 * @return : void
	 * @author Benjamin Niess
	 */
	protected function commonConstructor(){
	
		add_action( 'admin_init', array( &$this, 'checkCptOrTaxo') );
		$this->settings = get_option( SCF_SETTINGS );
		$this->admin_url = admin_url( 'options-general.php?page='.$this->admin_slug );
	}
	
	/*
	 * Check $_GET['cpt'] and $_GET['taxo'] and fill-in $this->cpt_or_tax array with slug and name of cpt or taxo
	 *
	 * @return void
	 * @author Benjamin Niess
	 */
	function checkCptOrTaxo(){
	
		if ( isset( $_GET['cpt'] ) && !empty( $_GET['cpt'] ) ) {
			$post_type_infos = get_post_type_object( trim( stripslashes( $_GET['cpt'] ) ) );
			if ( empty( $post_type_infos ) )
				wp_die( __("This post type dosesn't exists", 'simple-customtypes'), 0, array( 'back_link' => true ) );
			$this->cpt_or_tax['name'] = $post_type_infos->labels->name;
			$this->cpt_or_tax['slug'] = $post_type_infos->name;
			$this->cpt_or_tax['type'] = 'cpt';
		}
		else if ( isset( $_GET['taxo'] ) && !empty( $_GET['taxo'] ) ){
			$taxo_infos = get_taxonomy( trim( stripslashes( $_GET['taxo'] ) ) );
			if ( empty( $taxo_infos ) )
				wp_die( __("This taxonomy dosesn't exists", 'simple-customtypes'), 0, array( 'back_link' => true ) );
			
			$this->cpt_or_tax['name'] = $taxo_infos->labels->name;
			$this->cpt_or_tax['slug'] = $taxo_infos->name;
			$this->cpt_or_tax['type'] = 'taxo';
		}
	}
	
	/**
	 * Register CSS
	 *
	 * @return void
	 * @author Benjamin Niess
	 */
	function addRessources( ) {
		if ( is_admin() ){
			wp_enqueue_style  ( 'admin-scf', SCF_URL.'/ressources/admin.css', array(), SCF_VERSION, 'all' );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'admin-scf-script', SCF_URL.'/ressources/admin.js' );
			wp_enqueue_script( 'jquery-validate', SCF_URL.'/lib/jquery-validation/jquery.validate.min.js', array( 'jquery' ) );
		}
	}
	
	/**
	 * Add settings menu page
	 *
	 * @return void
	 * @author Benjamin Niess
	 */
	function addMenu() {
		add_options_page( __('Simple Custom Fields', 'simple-custom-fields'), __('Simple Custom Fields', 'simple-custom-fields'), 'manage_options', $this->admin_slug, array( &$this, 'dispatch' ) );
		
	}
	
	/**
	 * Meta function for load all check functions.
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function checkAdminPost() {
	
		//Metabox functions
		$this->checkAddMetabox();
		$this->checkDeleteMetabox();
		$this->checkUpdateMetabox();
		
		//Fields functions
		$this->checkUpdateField();
		$this->checkDeleteField();
		$this->checkUpdateFieldsOrder();
		
		
	}
	
	/**
	 * Actions dispatcher
	 *
	 * @return void
	 * @author Benjamin Niess
	 */
	function dispatch() { 
		if (!isset($_GET['action']))
			$_GET['action'] = 'index';
			
		switch( $_GET['action'] ) {
			case 'edit-cpt':
				$this->editCptOrTaxoFields();
				break;
			case 'edit-taxo':
				$this->editCptOrTaxoFields();
				break;
			case 'edit-field':
				$this->editCustomField();
				break;
			case 'add-custom-field-step-2':
				$this->addCustomFieldStep2();
				break;
			case 'add-cpt-metabox':
				$this->editCptOrTaxoFields();
				break;
			case 'delete-cpt-metabox':
				$this->editCptOrTaxoFields();
				break;
			case 'delete-field':
				$this->editCptOrTaxoFields();
				break;
			case 'edit-metabox':
				$this->editCptMetabox();
				break;
			case 'submit-edit-metabox':
				$this->editCptOrTaxoFields();
				break;
			case 'index':
			default :
				$this->editIndex();
				break;
		}
	}
	
	/*
	 * Display the main options page with the list of CPT and taxonomies
	 *
	 * @return void
	 * @author Benjamin Niess
	 *
	 */
	function editIndex() { 
		
		global $wp_post_types, $wp_taxonomies; ?>
		
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			
			<h2><?php _e('Simple Custom Fields Settings', 'simple-custom-fields'); ?></h2>
			
			<?php $this->displayMessage(); ?>
			
			<div id="poststuff" class="metabox-holder has-right-sidebar">
			
				<div id="side-info-column" class="inner-sidebar">
					<?php $this->sidebarDonate(); ?>
				</div>
				
				<div id="post-body">
				
					<div id="post-body-content">
						<div id="addressdiv" class="stuffbox">
							<h3><label for="link_url"><?php _e('Custom post types', 'simple-custom-fields'); ?></label></h3>
							<div class="inside">
								<div class="table table_content">
									<ul>
										<?php 
										//display each post type excepts if they are on the following array
										foreach ( $wp_post_types as $cpt_key => $cpt_value ) :
											if ( !in_array( $cpt_key, array( 'attachment', 'revision', 'nav_menu_item' ) ) ) : ?>
												<li><a href="<?php echo $this->admin_url; ?>&action=edit-cpt&cpt=<?php echo $cpt_key; ?>"><?php echo $cpt_value->labels->name; ?></a></li>
											<?php endif; 
										endforeach; ?>
									</ul>
								</div>
							</div>
						</div>
					</div>
					
					<div id="post-body-content">
						<div id="addressdiv" class="stuffbox">
							<h3><label for="link_url"><?php _e('Taxonomies', 'simple-custom-fields'); ?></label></h3>
							<div class="inside">
								<div class="table table_content">
									<ul>
										<?php 
										//display each taxonomy excepts if they are on the following array
										foreach ( $wp_taxonomies as $taxo_key => $taxo_value ) :
											if ( !in_array( $taxo_key, array( 'nav_menu', 'link_category', 'post_format' ) ) ) : ?>
												<li><a href="<?php echo $this->admin_url; ?>&action=edit-taxo&taxo=<?php echo $taxo_key; ?>"><?php echo $taxo_value->labels->name; ?></a></li>
											<?php endif; 
										endforeach; ?>
									</ul>
								</div>
							</div>
						</div>
					</div>
					
				</div>
				
			</div>
			
			<div class="clear"></div>
			
		</div>

	<?php 
	}
	
	function editCptOrTaxoFieldsContent(){ ?>
	
			<?php $this->displayMessage(); ?>
			
			<div id="poststuff" class="metabox-holder has-right-sidebar">
			
				<div id="side-info-column" class="inner-sidebar">
					<?php $this->sidebarDonate(); ?>
					<?php $this->sidebarAddMetabox(); ?>
					<?php $this->sidebarAddField(); ?>
					
				</div>
				
				<div id="post-body">
				
					<?php 
					//Check if some metaboxes already exist
					if ( !isset( $this->settings[$this->cpt_or_tax['slug']]['metaboxes'] ) || empty( $this->settings[$this->cpt_or_tax['slug']]['metaboxes'] ) ) : ?>
						<p><?php _e("No metabox found for this post type. Use the right side form to add a metabox.", 'simple-custom-fields'); ?></p>
					<?php else :
						$this->settings[$this->cpt_or_tax['slug']]['metaboxes'] = $this->array_sort($this->settings[$this->cpt_or_tax['slug']]['metaboxes'], 'order', SORT_ASC );
						foreach ( $this->settings[$this->cpt_or_tax['slug']]['metaboxes'] as $meta_box_key => $meta_box_value ) : ?>
							<div id="post-body-content">
								<h2><?php echo $meta_box_value['name']; ?></h2>
								<p>
									<a href="<?php echo $this->admin_url; ?>&action=edit-metabox&<?php echo $this->cpt_or_tax['type']; ?>=<?php echo $this->cpt_or_tax['slug']; ?>&metabox=<?php echo $meta_box_key; ?>"><?php _e('Edit metabox', 'simple-custom-fields'); ?></a>
									 - 
									 
									 <a href="<?php echo wp_nonce_url( $this->admin_url . "&action=delete-cpt-metabox&" . $this->cpt_or_tax['type'] . "=" . $this->cpt_or_tax['slug'] . "&metabox=" . $meta_box_key, 'delete-metabox-cpt' . $this->cpt_or_tax['slug'].'-'.$meta_box_key ); ?>" onclick="if ( confirm( '<?php echo esc_js( sprintf( __( "You are about to delete the '%s' metabox \n  'Cancel' to stop, 'OK' to delete.", 'simple-custom-fields' ), $meta_box_value['name'] ) ); ?>' ) ) { return true;}return false;"><?php _e('Delete metabox', 'simple-custom-fields'); ?></a>
									 
								</p>
								<form action="<?php echo $this->admin_url; ?>&action=edit-cpt&cpt=<?php echo $this->cpt_or_tax['slug']; ?>" method="post">
									<input type="hidden" name="edit_field_order" value="<?php echo $meta_box_key; ?>" />
									<table class="widefat">
										<thead>
											<tr>
												<th width="30%"><?php _e("Field name", 'simple-custom-fields'); ?></th>
												<th width="17%"><?php _e("Type", 'simple-custom-fields'); ?></th>
												<th width="17%"><?php _e("Order", 'simple-custom-fields'); ?></th>
												<th width="17%"><?php _e("Edit", 'simple-custom-fields'); ?></th>
												<th width="17%"><?php _e("Delete", 'simple-custom-fields'); ?></th>
											</tr>
										</thead>
										<tfoot>
											<tr>
											<th><?php _e("Field name", 'simple-custom-fields'); ?></th>
											<th><?php _e("Type", 'simple-custom-fields'); ?></th>
											<th><?php _e("Order", 'simple-custom-fields'); ?></th>
											<th><?php _e("Edit", 'simple-custom-fields'); ?></th>
											<th><?php _e("Delete", 'simple-custom-fields'); ?></th>
											</tr>
										</tfoot>
										<tbody>
											<?php if ( empty( $meta_box_value['fields'] ) ) : ?>
												<tr><td colspan="5"><?php _e("No fields found", 'simple-custom-fields'); ?></td></tr>
											<?php else :
												$meta_box_value['fields'] = $this->array_sort($meta_box_value['fields'], 'order', SORT_ASC);
												foreach ( $meta_box_value['fields'] as $fields_key => $field_value ) : ?>
													<tr>
														<td><a href="<?php echo $this->admin_url; ?>&action=edit-field&<?php echo $this->cpt_or_tax['type']; ?>=<?php echo $this->cpt_or_tax['slug']; ?>&metabox=<?php echo $meta_box_key; ?>&field=<?php echo $fields_key; ?>"><?php echo $field_value['field_name']; ?></a></td>
														<td><?php echo $field_value['type']; ?></td>
														<td><input type="text" name="fields[<?php echo $fields_key; ?>]" value="<?php echo $field_value['order']; ?>" size="1" /></td>
														<td><a href="<?php echo $this->admin_url; ?>&action=edit-field&<?php echo $this->cpt_or_tax['type']; ?>=<?php echo $this->cpt_or_tax['slug']; ?>&metabox=<?php echo $meta_box_key; ?>&field=<?php echo $fields_key; ?>"><?php _e("Edit", 'simple-custom-fields'); ?></a></td>
														<td><a href="<?php echo wp_nonce_url( $this->admin_url . "&action=delete-field&" . $this->cpt_or_tax['type'] . "=" . $this->cpt_or_tax['slug'] . "&metabox=" . $meta_box_key . '&field=' . $fields_key, 'delete-field' . $this->cpt_or_tax['slug'].'-'.$meta_box_key . '-' . $fields_key ); ?>" onclick="if ( confirm( '<?php echo esc_js( sprintf( __( "You are about to delete the '%s' field \n  'Cancel' to stop, 'OK' to delete.", 'simple-custom-fields' ), $field_value['field_name'] ) ); ?>' ) ) { return true;}return false;"><?php _e('Delete', 'simple-custom-fields'); ?></a></td>
													</tr>
												<?php endforeach; 
											endif;?>
										</tbody>
										</table>
										<p><input type="submit" class="button-primary" value="<?php _e('Update fields order', 'simple-custom-fields'); ?>" /></p>
									</form>
							</div>
						<?php endforeach; ?>
						<p><a href="<?php echo $this->admin_url; ?>"><< <?php _e('Back', 'simple-custom-fields'); ?></a></p>
					<?php endif; ?>
				</div>
			</div>
			
			<div class="clear"></div>
	<?php
	
	}
	/*
	 * Dashboard that display each metaboxes and fields of a specific CPT or taxonomy
	 *
	 * @return void
	 * @author Benjamin Niess
	 */
	function editCptOrTaxoFields() {
	
		if ( !isset( $this->cpt_or_tax['name'] ) || !isset( $this->cpt_or_tax['slug'] )  ) {
			wp_die( __("A field is missing", 'simple-customtypes'), 0, array( 'back_link' => true ) );
		}
		?>
		
		<div class="wrap">
			<?php screen_icon( 'options-general' ); ?>
			<h2><?php _e('Simple Custom Fields Settings', 'simple-custom-fields'); ?> > <?php echo $this->cpt_or_tax['name'] ; ?></h2>
			
			<p><a href="<?php echo $this->admin_url; ?>"><< <?php _e('Back', 'simple-custom-fields'); ?></a></p>
			
			<?php $this->editCptOrTaxoFieldsContent(); ?>
			
		</div>
		
	<?php }
	
	
	/*
	 * Display a form to edit a specific custom field 
	 *
	 * @return void
	 * @author Benjamin Niess
	 *
	 */
	function editCustomField(){ 
		global $simple_custom_fields;
		
		if ( !isset( $_GET['field'] ) || empty( $_GET['field'] ) || !isset( $_GET['metabox'] ) || empty( $_GET['metabox'] ) || !isset( $this->cpt_or_tax['slug'] ) || empty( $this->cpt_or_tax['slug'] ) || !$this->is_field( $this->cpt_or_tax['slug'], $_GET['metabox'], $_GET['field'] ) ) {
			wp_die( __("A field is missing", 'simple-customtypes'), 0, array( 'back_link' => true ) );
		}
		
		$field_infos = $this->settings[$this->cpt_or_tax['slug']]['metaboxes'][$_GET['metabox']]['fields'][$_GET['field']];
	
	?>
	
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			
			<h2><?php _e('Simple Custom Fields Settings', 'simple-custom-fields'); ?> - <?php echo $field_infos['field_name']; ?></h2>
			
			<?php $this->displayMessage(); ?>
			
				<?php if( isset( $_POST['scf']['metabox'] ) && isset( $_POST['scf']['field_type'] ) ) 
					$simple_custom_fields['composant-' . $field_infos['type'] ]->checkForm(); 
				else
					$simple_custom_fields['composant-' . $field_infos['type'] ]->displayForm(); 
				?>
			
			<div class="clear"></div>
			
		</div><!-- dashboard-widgets-wrap -->
	<?php 
	}
	
	
	/*
	 * View for updating metabox name
	 *
	 * @return void
	 * @author Benjamin Niess
	 */
	function editCptMetabox(){ 
		if ( !isset( $_GET['metabox']) || empty( $_GET['metabox'] ) || !isset( $this->cpt_or_tax['slug'] ) || empty( $this->cpt_or_tax['slug'] ) )
			wp_die( __("The metabox you want to edit doesn't exists", 'simple-customtypes' ), 0, array( 'back_link' => true ) );
			
		$metabox_name = $this->settings[$this->cpt_or_tax['slug'] ]['metaboxes'][$_GET['metabox']]['name'];
		
		$metabox_order = isset( $this->settings[$this->cpt_or_tax['slug'] ]['metaboxes'][$_GET['metabox']]['order'] ) ? $this->settings[$this->cpt_or_tax['slug'] ]['metaboxes'][$_GET['metabox']]['order'] : 0;
	?>
	
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			
			<h2><?php _e('Simple Custom Fields Settings', 'simple-custom-fields'); ?> - <?php echo $_GET['metabox']; ?></h2>
			
			<div id="poststuff" class="metabox-holder has-right-sidebar">
				<p><a href="<?php echo $this->admin_url . "&action=edit-cpt&". $this->cpt_or_tax['type'] . "=" . $this->cpt_or_tax['slug']; ?>"><< <?php _e('Back', 'simple-custom-fields'); ?></a></p>
				
				<div id="post-body">
					<div id="post-body-content">
						<div id="addressdiv" class="stuffbox">
							<h3><label for="link_url"><?php _e('Edit metabox', 'simple-custom-fields'); ?></label></h3>
							<div class="inside">
								<form action="<?php echo wp_nonce_url( $this->admin_url . "&action=submit-edit-metabox&metabox=" . $_GET['metabox'] . "&" . $this->cpt_or_tax['type'] . "=" . $this->cpt_or_tax['slug'], 'update-metabox-cpt' . $this->cpt_or_tax['slug'] ); ?>" method="post">
									<p><input type="text" name="metabox_new_name" value="<?php echo $metabox_name; ?>" /><label><?php _e('Metabox title', 'simple-custom-fields'); ?></label></p>
									<p><input type="text" name="metabox_new_order" value="<?php echo $metabox_order; ?>" /><label><?php _e('Metabox order', 'simple-custom-fields'); ?></label></p>
									<p><input type="submit" class="button-primary" value="<?php _e('Submit', 'simple-custom-fields'); ?>" /></p>
								</form>
							</div>
						</div>
					</div>
					
					<p><a href="<?php echo $this->admin_url . "&action=edit-cpt&". $this->cpt_or_tax['type'] . "=" . $this->cpt_or_tax['slug']; ?>"><< <?php _e('Back', 'simple-custom-fields'); ?></a></p>
				</div>
				
			</div>
			
		</div>
		
	<?php
	}
	
	/*
	 * Display the form to add a new custom field or call the checkForm function. (The step 1 was the sidebar form with drop down lists) 
	 *
	 * @return : void
	 * @author : Benjamin Niess
	 *
	 */
	function addCustomFieldStep2(){
		global $simple_custom_fields; ?>
		
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			
			<h2><?php _e('Simple Custom Fields Settings - Edit Field', 'simple-custom-fields'); ?></h2>
			
			<?php
			//display form or check_form depending on if we find $_POST['is_new'] that means that we don't hve to cheeck data
			if ( isset( $_POST['scf']['field_type'] ) && !empty( $_POST['scf']['field_type'] ) ){
				if ( isset( $_POST['is_new'] ) && !empty( $_POST['is_new'] ) )
					$simple_custom_fields['composant-'.$_POST['scf']['field_type']]->displayForm();
				else
					$simple_custom_fields['composant-'.$_POST['scf']['field_type']]->checkForm();
			}
			else{
				wp_die( __('An error has occured (no metabox selected). Please try again', 'simple-custom-fields'), 0, array( 'back_link' => true ) );
			}
			?>
			
		</div>
		

	<?php }
	
	/*
	* GROUP OF FUNCTIONS THAT CHECK DATA BEFORE ADD / DELETE / UPDATE METABOXES & FIELDS
	*
	*/
	
	
	/**
	 * Check data to add a metabox. Call the function that add metaboxes
	 *
	 * @return boolean
	 * @author Benjamin Niess
	 */
	function checkAddMetabox() {
		if ( isset( $_GET['action'] ) && $_GET['action'] == "add-cpt-metabox"  ) {
			
			if ( empty( $_POST['metabox-name'] ) ) {
				wp_die( __('Tcheater ? You try to edit a metabox without name. Impossible !', 'simple-custom-fields'), 0, array( 'back_link' => true ) );
			}
			
			$metabox_name = trim( stripslashes( $_POST['metabox-name'] ) );
			$metabox_slug = sanitize_title( $metabox_name );
			
			check_admin_referer( 'add-metabox-cpt' . $this->cpt_or_tax['slug'] );
			
			//check if the metabox name already exist or not
			if ( isset( $this->settings[$this->cpt_or_tax['slug']]['metaboxes'][$metabox_slug] ) && !empty( $this->settings[$this->cpt_or_tax['slug']]['metaboxes'][$metabox_slug] ) )
				wp_die( __("This metabox already exists. Please choose another metabox name", 'simple-custom-fields'), 0, array( 'back_link' => true ) );
				
				
			$this->addCptOrTaxoMetabox( $metabox_name, $metabox_slug, $this->cpt_or_tax['slug'] );
			
			return true;
		}
		return false;
	}
	
	/**
	 * Check data for delete a Metabox. Call the function that delete metaboxes
	 *
	 * @return boolean
	 * @author Benjamin Niess
	 */
	function checkDeleteMetabox() {
		if ( isset( $_GET['action'] ) && isset( $_GET['metabox'] ) && isset( $this->cpt_or_tax['slug'] ) && $_GET['action'] == 'delete-cpt-metabox' ) {
			check_admin_referer( 'delete-metabox-cpt' . $this->cpt_or_tax['slug'].'-'.$_GET['metabox'] );
			
			$metabox = stripslashes( $_GET['metabox'] );
			
			$this->deleteCptOrTaxoMetabox( $metabox, $this->cpt_or_tax['slug'] );
			
			return true;
		}
		return false;
	}
	
	/**
	 * Check data for update a Metabox. Call the function that update metaboxes
	 *
	 * @return boolean
	 * @author Benjamin Niess
	 */
	function checkUpdateMetabox() {
		if ( !isset( $_GET['action'] ) || !isset( $_GET['metabox'] ) || !isset( $this->cpt_or_tax['slug'] ) || !isset( $_POST['metabox_new_name'] ) || !$_GET['action'] == 'submit-edit-metabox' || !isset( $_POST['metabox_new_order'] ) ) 
			return false;
		
		check_admin_referer( 'update-metabox-cpt' . $this->cpt_or_tax['slug'] );
		
		$metabox_slug = stripslashes( $_GET['metabox'] );
		$metabox_name = trim( stripslashes( $_POST['metabox_new_name'] ) );
		$metabox_order = trim( stripslashes( (int) $_POST['metabox_new_order'] ) );
		
		$this->updateCptOrTaxoMetabox( $metabox_name, $metabox_order, $metabox_slug, $this->cpt_or_tax['slug'] );
		return true;
	}
	
	/**
	 * Check data for update a field. Call the function that update fields
	 *
	 * @return boolean
	 * @author Benjamin Niess
	 */
	function checkUpdateField(){
		if ( isset( $_GET['action'] ) && isset( $_GET['metabox'] ) && isset( $this->cpt_or_tax['slug'] ) && $_GET['action'] == 'edit-field' ) {
			//check_admin_referer( 'edit-field' . $this->cpt_or_tax['slug'].'-'.$_GET['metabox'] .'-'. $_GET['field'] );
			
			$metabox = trim( stripslashes( $_GET['metabox'] ) );
			$field = trim( stripslashes( $_GET['field'] ) );
			//$this->deleteField( $metabox, $this->cpt_or_tax['slug'], $field );
			
			return true;
		}
		return false;
	}	
	
	/**
	 * Check data for delete a field. Call the function that delete fields
	 *
	 * @return boolean
	 * @author Benjamin Niess
	 */
	function checkDeleteField(){
		if ( isset( $_GET['action'] ) && isset( $_GET['metabox'] ) && isset( $this->cpt_or_tax['slug'] ) && $_GET['action'] == 'delete-field' ) {
			check_admin_referer( 'delete-field' . $this->cpt_or_tax['slug'].'-'.$_GET['metabox'] .'-'. $_GET['field'] );
			
			$metabox = trim( stripslashes( $_GET['metabox'] ) );
			$field = trim( stripslashes( $_GET['field'] ) );
			$this->deleteField( $metabox, $this->cpt_or_tax['slug'], $field );
			
			return true;
		}
		return false;
	}
	
	/**
	 * Check if fields order has been edited
	 *
	 * @return boolean
	 * @author Benjamin Niess
	 */
	function checkUpdateFieldsOrder(){
		if ( !isset( $_POST['edit_field_order'] ) || empty( $_POST['edit_field_order'] ) || !isset( $_POST['fields'] ) || empty( $_POST['fields'] ) )
			return false;
		
		foreach( $_POST['fields'] as $field_slug => $field_value ){
			if ( (int) $field_value )
				$this->settings[$this->cpt_or_tax['slug']]['metaboxes'][$_POST['edit_field_order']]['fields'][$field_slug]['order'] = $field_value;
		}
		$this->scfUpdateOptions( $this->settings );
	}
	
	
	/*
	* PROTECTED FUNCTIONS FOR ADD / DELETE / UPDATE METABOXES & FIELDS
	*/
	
	/*
	 * Add a metabox for a CPT or a taxonomy
	 *
	 * @return boolean
	 * @author Benjamin Niess
	 */
	protected function addCptOrTaxoMetabox( $metabox_name, $metabox_slug, $metabox_cpt ){
		
		if ( !isset( $metabox_name ) || !isset( $metabox_slug ) || !isset( $metabox_cpt ) ) {
			return false;
		}
		
		//Update plugin settings with the new metabox
		$this->settings[$metabox_cpt]["metaboxes"][$metabox_slug] = array( "name" => $metabox_name, 'order' => 0 );
		$this->scfUpdateOptions( $this->settings );
		
		$this->message = __('The metabox has been created.', 'simple-custom-fields.' );
		$this->status  = 'updated';
		
		return true;
		
	
	}
	
	/*
	 * Delete a metabox for a CPT or a taxonomy
	 *
	 * @return boolean
	 * @author Benjamin Niess
	 */
	protected function deleteCptOrTaxoMetabox( $metabox, $cpt ){
		
		if ( !isset( $metabox) || !isset( $cpt ) )
			return false;
		
		//update plugin settings
		unset( $this->settings[$cpt]["metaboxes"][$metabox] );
		$this->scfUpdateOptions( $this->settings );
		
		$this->message = __('The metabox has been deleted.', 'simple-custom-fields.' );
		$this->status  = 'updated';
		
		return true;
		
	}
	
	/*
	 * Update a metabox for a CPT or a taxonomy
	 *
	 * @return boolean
	 * @author Benjamin Niess
	 */
	protected function updateCptOrTaxoMetabox( $metabox_name, $metabox_order, $metabox_slug, $metabox_cpt ){
		
		if ( !isset( $metabox_name ) || !isset( $metabox_order ) || !isset( $metabox_slug ) || !isset( $metabox_cpt ) )
			return false;
		
		//update plugin settings
		$this->settings[$metabox_cpt]["metaboxes"][$metabox_slug]['name'] = $metabox_name;
		$this->settings[$metabox_cpt]["metaboxes"][$metabox_slug]['order'] = $metabox_order;
		$this->scfUpdateOptions( $this->settings );
		
		$this->message = __('The metabox has been updated.', 'simple-custom-fields.');
		$this->status  = 'updated';
		
		return true;
		
	
	}
	
	/*
	 * Delete a field of a metabox
	 *
	 * @return boolean
	 * @author Benjamin Niess
	 */
	protected function deleteField( $metabox, $cpt, $field ){
		
		if ( !isset( $metabox) || !isset( $cpt ) || !isset( $field ) )
			return false;
		
		//update plugin settings
		unset( $this->settings[$cpt]["metaboxes"][$metabox]['fields'][$field] );
		$this->scfUpdateOptions( $this->settings );
		
		$this->message = __('The field has been deleted.', 'simple-custom-fields.');
		$this->status  = 'updated';
		
		return true;
		
	
	}
	
	/*
	 * Update a custom field for a CPT or a taxonomy
	 *
	 * @return boolean
	 * @author Benjamin Niess
	 */
	protected function updateCptOrTaxoCustomField( $field_slug, $field_name, $field_type, $metabox_slug, $metabox_cpt, $field_description = '', $default_value = '', $avaliable_values = array(), $regexp = '', $custom_error_message = '', $css_class = '', $auto_display = false, $public_name = '', $required = false ){
		
		if ( !isset( $field_slug ) || empty( $field_slug ) || !isset( $field_name ) || empty( $field_name )|| !isset( $field_type ) || empty( $field_type ) || !isset( $metabox_cpt ) || empty( $metabox_cpt ) || !isset( $metabox_slug ) || empty( $metabox_slug ) )
			return false;
		
		//check if this field has already been created
		if ( !isset( $this->settings[$metabox_cpt]["metaboxes"][$metabox_slug]['fields'][$field_slug] ) || empty( $this->settings[$metabox_cpt]["metaboxes"][$metabox_slug]['fields'][$field_slug] ) )
			$this->message = __('The field has been created.', 'simple-custom-fields.');
		else
			$this->message = __('The field has been updated.', 'simple-custom-fields.');
			
		$this->status  = 'updated';
		
		//update plugin settings
		$this->settings[$metabox_cpt]["metaboxes"][$metabox_slug]['fields'][$field_slug] = array( 'order' => 0, 'field_name' => $field_name, 'public_name' => $public_name, 'type' => $field_type, 'field_description' => $field_description, 'default_value' => $default_value, 'avaliable_values' => $avaliable_values, 'regexp' => $regexp, 'custom_error_message' => $custom_error_message, 'css_class' => $css_class, 'auto_display' => $auto_display, 'required' => $required );
		$this->scfUpdateOptions( $this->settings );
		
		return true;
		
	
	}
	
	/*
	 * Used on componants to setup $metabox var depending on POST or GET
	 *
	 * @return string metabox slug
	 *
	 */
	function setMetaboxVar(){
		if ( isset( $_POST['scf']['metabox'] ) && !empty( $_POST['scf']['metabox'] ) )
			return  $_POST['scf']['metabox'];
		elseif ( isset( $_GET['metabox'] ) && !empty( $_GET['metabox'] ) )
			return $_GET['metabox'];
		else
			wp_die( __('An error as occured (metabox undefined). Please try again', 'simple-custom-fields'), 0, array( 'back_link' => true ) );
	}
	
	
	/*
	 * Used on composants to setup $field_type var depending on POST or GET
	 *
	 * @param String $metabox : the metabox slug that contain the field
	 * @return : String : the type of the field
	 * @author Benjamin Niess
	 *
	 */
	function setFieldTypeVar( $metabox = '' ){
		if ( isset( $_POST['scf']['field_type'] ) && !empty( $_POST['scf']['field_type'] ) )
			return $_POST['scf']['field_type'];
		elseif ( isset( $_GET['action'] ) && !empty($_GET['action']) && $_GET['action'] == 'edit-field' && isset($_GET['field']) && !empty( $_GET['field'] ) && isset( $this->settings[$this->cpt_or_tax['slug']]['metaboxes'][$metabox]['fields'][$_GET['field']] ) ) {
			return $this->settings[$this->cpt_or_tax['slug']]['metaboxes'][$metabox]['fields'][$_GET['field']]['type'];
		}
		else
			wp_die( __('An error as occured (field type undefined). Please try again', 'simple-custom-fields'), 0, array( 'back_link' => true ) );
	}
	
	function getMetaboxName( $cpt, $metabox_slug ){
		global $simple_custom_fields;
		
		$metabox_name = $simple_custom_fields['admin']->settings[$cpt]['metaboxes'][$metabox_slug]['name'];
		
		if ( isset( $metabox_name ) && !empty( $metabox_name ) )
			return $metabox_name;
			
		return '';
	}
	
	/*
	 * Get the name of a post type or a taxo
	 *
	 * @param string : slug of the post type or taxonomy
	 * @return string : name of the post type or taxonomy || false
	 * @author : Benjamin Niess
	 *
	 */
	function getCptOrTaxoName( $cpt_or_tax_slug ){
		global $wp_taxonomies, $wp_post_types;
		
		if ( !isset( $cpt_or_tax_slug ) || empty( $cpt_or_tax_slug ) )
			return false;
		
		if ( isset( $wp_taxonomies[$cpt_or_tax_slug]->label ) )
			return $wp_taxonomies[$cpt_or_tax_slug]->label;
			
		elseif ( isset( $wp_post_types[$cpt_or_tax_slug]->label ) )
			return $wp_post_types[$cpt_or_tax_slug]->label;
			
		return false;
	}
	
	/*
	 * meta function that update plugin settings
	 *
	 * @return boolean
	 * @author Benjamin Niess
	 */
	function scfUpdateOptions( $settings ){
		if ( !isset( $settings ) || empty( $settings ) ) {
			$this->message = __('An error has occured during the data saving. (settings empty)', 'simple-custom-fields.' );
			$this->status  = 'error';
			return false;
		}
		
		update_option( SCF_SETTINGS, $settings );
		return true;
	}
	
	/*
	 * Check if a field of a metabox of a cpt is set or not
	 *
	 * @return void
	 * @author Benjamin Niess
	 */
	function is_field( $cpt, $metabox, $field ){
		global $wp_taxonomies, $wp_post_types;
		
		if ( !isset( $cpt ) || empty( $cpt ) || !isset( $metabox ) || empty( $metabox ) || !isset( $field ) || empty( $field ) )
			return false;
			
		if ( !isset( $this->settings[$cpt]['metaboxes'][$metabox]['fields'][$field] ) )
			return false;
			
		return true;
	}
	
	/**
	 * Display WP alert
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function displayMessage() {
		if ( $this->message != '') {
			$message = $this->message;
			$status = $this->status;
			$this->message = $this->status = ''; // Reset
		}
		
		if ( isset($message) && !empty($message) ) {
		?>
			<div id="message" class="<?php echo ($status != '') ? $status :'updated'; ?> fade">
				<p><strong><?php echo $message; ?></strong></p>
			</div>
		<?php
		}
	}
	
	
	/*
	* TEMPLATING FUNCTIONS
	*
	*/
	
	/*
	 * Display the donate sidebar metabox
	 *
	 * @return void
	 * @author Benjamin Niess
	 */
	function sidebarDonate( $submit = 0 ){ ?>
	
		<div class="postbox " style="">
			<div class="handlediv" title="Click to toggle"><br></div>
			<h3 class="hndle"><span><?php _e('Donate', 'simple-custom-fields'); ?></span></h3>
			<div class="inside">
				<p><?php _e('Please donate if you can', 'simple-custom-fields'); ?></p>
			</div>
		</div>
		
	<?php
	}
	
	/*
	 * Display the sidebar metabox that add other metaboxes
	 *
	 * @return void
	 * @author Benjamin Niess
	 */
	function sidebarAddMetabox(){ 
		if ( !isset( $this->cpt_or_tax['name'] ) || !isset( $this->cpt_or_tax['slug'] )  ) {
			wp_die( __("A field is missing", 'simple-customtypes'), 0, array( 'back_link' => true ) );
		} ?>
			
		<div class="postbox" style="">
			<div class="handlediv" title="Click to toggle"><br></div>
			<h3 class="hndle"><span><?php _e('Add a custom metabox', 'simple-custom-fields'); ?></span></h3>
			<div class="inside">
				<form action="<?php echo wp_nonce_url( $this->admin_url . "&action=add-cpt-metabox&". $this->cpt_or_tax['type'] . "=" . $this->cpt_or_tax['slug'], 'add-metabox-cpt' . $this->cpt_or_tax['slug'] ); ?>" method="post">
					<input type="text" name="metabox-name" />
					<input type="submit" class="button-primary" value="<?php _e('Submit', 'simple-custom-fields'); ?>" />
				</form>
			</div>
		</div>
		
	<?php
	}
	
	/*
	 * Display the sidebar metabox that allow to add a new field if there is at least one metabox
	 *
	 * @return void
	 * @author Benjamin Niess
	 */
	function sidebarAddField(){ 
		if ( !isset( $this->cpt_or_tax['name'] ) || !isset( $this->cpt_or_tax['slug'] )  ) {
			wp_die( __("A field is missing", 'simple-customtypes'), 0, array( 'back_link' => true ) );
		} ?>
			
		<div class="postbox" style="">
			<div class="handlediv" title="Click to toggle"><br></div>
			<h3 class="hndle"><span><?php _e('Add a custom field', 'simple-custom-fields'); ?></span></h3>
			<div class="inside">
				<?php if( isset( $this->settings[$this->cpt_or_tax['slug']]['metaboxes'] ) && !empty( $this->settings[$this->cpt_or_tax['slug']]['metaboxes'] ) ) : ?>
					<form action="<?php echo wp_nonce_url( $this->admin_url . "&action=add-custom-field-step-2&". $this->cpt_or_tax['type'] . "=" . $this->cpt_or_tax['slug'], 'add-custom-field-step-2' . $this->cpt_or_tax['slug'] ); ?>" method="post">
						<p>
							<select name="scf[metabox]">
								<?php 
								$ordered_metaboxes = $this->array_sort( $this->settings[$this->cpt_or_tax['slug']]['metaboxes'], 'order', SORT_ASC );
								foreach( $ordered_metaboxes as $metabox_key => $metabox_value ) : ?>
									<option value="<?php echo $metabox_key; ?>"><?php echo $metabox_value['name']; ?></option>
								<?php endforeach; ?>
							</select>
							<label for="metabox"><?php _e('Choose a metabox', 'simple-custom-fields'); ?></label>
						</p>
						
						<p>
							<select name="scf[field_type]">
								<option value="text"><?php _e('Text field', 'simple-custom-fields'); ?></option>
								<option value="textarea"><?php _e('Text area', 'simple-custom-fields'); ?></option>
								<option value="dropdown"><?php _e('Drop-down list', 'simple-custom-fields'); ?></option>
								<option value="checkbox"><?php _e('Check box list', 'simple-custom-fields'); ?></option>
								<option value="radiobutton"><?php _e('Radio buttons', 'simple-custom-fields'); ?></option>
								<option value="file"><?php _e('File upload', 'simple-custom-fields'); ?></option>
							</select>
							<label for="metabox"><?php _e('Choose a field type', 'simple-custom-fields'); ?></label>
						</p>
						
						<p>
							<input type="hidden" name="is_new" value="1" />
							<input type="submit" class="button-primary" value="<?php _e('Add field', 'simple-custom-fields'); ?>" />
						</p>
					</form>
				<?php else : ?>
					<p><?php _e('You first need to create a metabox to create a field', 'simple-custom-fields'); ?></p>
				<?php endif; ?>
			</div>
		</div>
		
	<?php
	}

	/*
	 * Add a sidebar box with some information about the current field (Custom type, Metabox, field type)
	 *
	 * @param string $metabox_name : The name of the current metabox
	 * @param string $field_type : The type of the field (text, textarea...)
	 * @return : void
	 * @author : Benjamin Niess
	 *
	 */
	function sidebarFieldInformation( $metabox_name, $field_type ){ ?>
		<div class="postbox" style="">
			<div class="handlediv" title="Click to toggle"><br></div>
			<h3 class="hndle"><span><?php _e('Information about the custom field', 'simple-custom-fields'); ?></span></h3>
			<div class="inside">
				<p><strong><?php _e('Post type / Taxonomy: ', 'simple-custom-fields'); ?></strong><?php echo $this->getCptOrTaxoName( $this->cpt_or_tax['slug'] ); ?></p>
				<p><strong><?php _e('Metabox: ', 'simple-custom-fields'); ?></strong><?php echo $metabox_name ?></p>
				<p><strong><?php _e('Field type: ', 'simple-custom-fields'); ?></strong><?php echo $field_type; ?></p>
			</div>
		</div>
	<?php }
	
	// EDIT POST FUNCTIONS //
	
	/**
	 * ADD metaboxes with custom fields on the edit post page
	 * 
	 * @param string $post_type
	 * @param object $post
	 * @return void
	 */
	function addAdminMetaboxes( $post_type, $post ){
		if ( !isset( $this->settings ) || empty( $this->settings ) || !isset( $this->settings[$post_type]) || empty( $this->settings[$post_type] ) )
			return false;
		
		$ordered_metaboxes = $this->array_sort( $this->settings[$post_type]['metaboxes'], 'order', SORT_ASC );
		
		foreach ( $ordered_metaboxes as $metabox_slug => $metabox_data ){
			if ( isset( $metabox_data['fields'] ) && !empty( $metabox_data['fields'] ) )
				add_meta_box( $metabox_slug, $metabox_data['name'], array( &$this, 'adminMetaboxesCallbackFunction'), $post_type, 'normal', 'default' );
			}
		//set the post type to the private var to use it on the addJsFieldCheck function
		$this->cpt_or_tax['type'] = $post_type;
		
	}
	
	
	/**
	 * Check fields completion with jQuery Validation
	 * 
	 * @return void
	 */
	function addJsFieldCheck() {
		if ( !isset( $this->settings ) || empty( $this->settings ) || !isset( $this->settings[$this->cpt_or_tax['type']]) || empty( $this->settings[$this->cpt_or_tax['type']] ) )
			return false;
		
		$post_type = $this->cpt_or_tax['type'];
		$ordered_metaboxes = $this->array_sort( $this->settings[$post_type]['metaboxes'], 'order', SORT_ASC ); ?>
		
		<script type="text/javascript">
			jQuery("#post").validate({
			
				rules: {
				
					<?php foreach ( $ordered_metaboxes as $metabox_slug => $metabox_data ) :
					
						if ( isset( $metabox_data['fields'] ) && !empty( $metabox_data['fields'] ) ) :
						
							$fields = $this->array_sort( $this->settings[$post_type]['metaboxes'][$metabox_slug]['fields'], 'order', SORT_ASC );
							
								foreach( $fields as $field_slug => $field_data ):
									
										if ( isset( $field_data['required'] ) && !empty( $field_data['required'] ) ) :
											echo "'scf[" . $field_slug . "]': 'required',";
											
										endif;
									
								endforeach;
								
						endif;
						
					endforeach; ?>
				},
				messages: {
		
					<?php foreach ( $ordered_metaboxes as $metabox_slug => $metabox_data ) :
					
						if ( isset( $metabox_data['fields'] ) && !empty( $metabox_data['fields'] ) ) :
						
							$fields = $this->array_sort( $this->settings[$post_type]['metaboxes'][$metabox_slug]['fields'], 'order', SORT_ASC );
							
								foreach( $fields as $field_slug => $field_data ):
									
										if ( isset( $field_data['required'] ) && !empty( $field_data['required'] ) ) :
										
											echo "'scf[" . $field_slug . "]' : { required: '";
											
											if ( isset( $field_data['custom_error_message'] ) && !empty( $field_data['custom_error_message'] ) ) {
												echo $field_data['custom_error_message'];
											}
											else {
												_e('This field is required', 'simple-custom-fields');
											}
											
											echo "'},";
											
										
										
										endif;
									
								endforeach;
								
						endif;
						
					endforeach; ?>
				}
			});
			</script>
		<?php
	}
	
	
	/**
	 * ADD metaboxes with custom fields on the edit post page (The callback function)
	 * 
	 * @param object $object
	 * @param array $args
	 * @return void
	 */
	function adminMetaboxesCallbackFunction( $object, $args ){
		global $simple_custom_fields;
		if ( !isset( $object ) || empty( $object ) || !isset( $args ) || empty( $args ) || !isset( $this->settings[$object->post_type]['metaboxes'][$args['id']]['fields'] ) || empty( $this->settings[$object->post_type]['metaboxes'][$args['id']]['fields'] ) )
			return false; ?>
			
			<table class="form-table">
				<tbody>
					<?php 
					$ordered_fields = $this->array_sort( $this->settings[$object->post_type]['metaboxes'][$args['id']]['fields'], 'order', SORT_ASC );
					foreach( $ordered_fields as $field_slug => $field_data ){
						$simple_custom_fields['composant-' . $field_data['type'] ]->displayFormField( $field_slug, $field_data, $object->ID );
					} ?>
				</tbody>
			</table>
	<?php 
	}
	
	/*
	 * Check each custom fields and update post_data or display errors
	 *
	 * @param $post_id the post id
	 * @param $post the post object
	 * @return : void
	 * @author Benjamin Niess
	 */
	function saveFieldsPostdata( $post_id ){
		
		//if no custom fields for this post type, no need to check data
		if ( !isset( $_POST['scf'] ) || empty( $_POST['scf'] ) )
			return false;
		
		foreach ( $_POST['scf'] as $field_key => $field_value ) {
			
			if ( empty($field_value) ) {
				delete_post_meta($post_id, $field_key ); 
			} else {
				update_post_meta($post_id, $field_key, $field_value );
			}
		}
		
		return true;
	}

	
	/*
	 * function found on http://php.net/manual/fr/function.sort.php
	 * Order an array by specific field
	 */
	function array_sort($array, $on, $order=SORT_ASC) {
		$new_array = array();
		$sortable_array = array();
	
		if (count($array) > 0) {
			foreach ($array as $k => $v) {
				if (is_array($v)) {
					foreach ($v as $k2 => $v2) {
						if ($k2 == $on) {
							$sortable_array[$k] = $v2;
						}
					}
				} else {
					$sortable_array[$k] = $v;
				}
			}
	
			switch ($order) {
				case SORT_ASC:
					asort($sortable_array);
				break;
				case SORT_DESC:
					arsort($sortable_array);
				break;
			}
	
			foreach ($sortable_array as $k => $v) {
				$new_array[$k] = $array[$k];
			}
		}
	
		return $new_array;
	}
	
	
}
?>