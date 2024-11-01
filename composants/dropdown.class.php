<?php
class SCF_Composant_Dropdown extends SimpleCustomFields_Admin {

	//array with all missing fields
	private $missing_fields = array();
	
	//array with all fields
	private $fields = array();
	
	//array with all fields without slashes and spaces
	private $cleaned_fields = array();
	
	
	/*
	 * Constructor
	 *
	 * Call the common constructor
	 * Define each fields with their public name and requirement status
	 */
	function SCF_Composant_Dropdown(){
	
		//Array with all fields slugs, names and requirement 
		$this->fields = array( 
			"metabox" => array( "name" => __('Metabox', 'simple-custom-fields'), "required" => true ),
			"field_type" => array( "name" => __('Field type', 'simple-custom-fields' ), "required" => true ),
			"field_name" => array( "name" => __('Field name', 'simple-custom-fields'), "required" => true ),
			"field_description" => array( "name" => __('Field description', 'simple-custom-fields'), "required" => false ),
			"public_name" => array( "name" => __('Public name', 'simple-custom-fields'), "required" => false ),
			"default_value" => array( "name" => __('Default value', 'simple-custom-fields' ), "required" => false ),
			"css_class" => array( "name" => __('CSS class', 'simple-custom-fields'), "required" => false ),
			"required" => array( "name" => __('Required', 'simple-custom-fields' ), "required" => false ),
			"custom_error_message" => array( "name" => __('Custom error message', 'simple-custom-fields' ), "required" => false )
		);
		
		//call the common constructor (on class.admin.php)
		$this->commonConstructor();
	}
	
	
	/*
	 * Display the form that allow to add / edit a field
	 *
	 * @print the form content
	 * @author Benjamin Niess
	 */
	function displayForm() { 
	
		//check if the hidden fields for the metabox slug and field type are set 
		$metabox = $this->setMetaboxVar();
		$field_type = $this->setFieldTypeVar( $metabox );
		
		//if we want to update a field, we first need to load its information
		if ( $_GET['action'] == 'edit-field' ){
			//convert settings to the cleaned_filed var that is used on the field creation 
			$this->cleaned_fields = $this->settings[$this->cpt_or_tax['slug']]['metaboxes'][$metabox]['fields'][$_GET['field']];
		}
		?>
	
		<div id="poststuff" class="metabox-holder has-right-sidebar">
		
			<div id="side-info-column" class="inner-sidebar">
			
				<?php $this->sidebarDonate(); ?>
				
				<?php $this->sidebarFieldInformation( $this->getMetaboxName( $this->cpt_or_tax['slug'], $metabox ), $field_type ); ?>
				
			</div>
			
			<form id="edit_field" action="" method="post">
			
				<div id="post-body">
					<p><a href="<?php echo $this->admin_url . "&action=edit-cpt&". $this->cpt_or_tax['type'] . "=" . $this->cpt_or_tax['slug']; ?>"><< <?php _e('Back', 'simple-custom-fields'); ?></a></p>
				
					<div id="post-body-content">
						<div id="addressdiv" class="stuffbox">
							<h3><label for="link_url"><?php _e('Basic information', 'simple-custom-fields'); ?></label></h3>
							<div class="inside">
								
								<input type="hidden" name="scf[metabox]" value="<?php echo $metabox; ?>" />
								<input type="hidden" name="scf[field_type]" value="<?php echo $field_type; ?>" />
								<table class="form-table">
									<tbody>
										<tr valign="top">
											<th scope="row"><label for="scf[field_name]"><?php _e('Field name *', 'simple-custom-fields'); ?></label></th>
											<td><input name="scf[field_name]" type="text" value="<?php echo ( isset( $this->cleaned_fields['field_name'] ) && !empty( $this->cleaned_fields['field_name'] ) ) ? $this->cleaned_fields['field_name'] : ''; ?>" class="regular-text" />
											<span class="description"></span></td>
										</tr>
										<?php
										$this->cleaned_fields['field_values'] = array( 'cucu' => 'Cu cu', "toto" => 'Totô', 'bonjour-madame' => 'bonjour madame' );
										$this->cleaned_fields['default_value'] = 1;
										?>
										<tr valign="top">
											<th scope="row"><label for="scf[field_values]"><?php _e('Field possible values *', 'simple-custom-fields'); ?></label></th>
											<td>
												<div class="available_values">
													<?php foreach( $this->cleaned_fields['field_values'] as $field_key => $field_value ) : ?>
														<span class="sanitized_value"><input name="scf[field_values][<?php echo $field_key; ?>]" type="text" value="<?php echo $field_value; ?>" class="regular-text" />
														<input type="radio" name="scf[default_value]" value="<?php echo $field_key; ?>" <?php checked( $field_key, $this->cleaned_fields['default_value'] ); ?> /><?php _e('Default value ?', 'simple-custom-fields'); ?><br /></span>
														
													<?php endforeach; ?>
												</div>
												<input type="radio" name="scf[default_value]" value="0" <?php checked( 0, $this->cleaned_fields['default_value'] ); ?> /><?php _e('No default value ?', 'simple-custom-fields'); ?><br />
												<input type="button" class="button add_value" value="<?php _e('Add a value', 'simple-custom-fields'); ?>" />
											<span class="description"></span></td>
										</tr>
										<tr valign="top">
											<th scope="row"><label for="scf[field_description]"><?php _e('Field description', 'simple-custom-fields'); ?></label></th>
											<td><input name="scf[field_description]" type="text" value="<?php echo ( isset( $this->cleaned_fields['field_description'] ) && !empty( $this->cleaned_fields['field_description'] ) ) ? $this->cleaned_fields['field_description'] : ''; ?>" class="regular-text" />
											<span class="description"><?php _e('Add a custom help description', 'simple-custom-fields'); ?></span></td>
										</tr>
										<tr valign="top">
											<th scope="row"><label for="scf[required]"><?php _e('Required', 'simple-custom-fields'); ?></label></th>
											<td><input type="checkbox" name="scf[required]" <?php checked( isset( $this->cleaned_fields['required'] ) ? $this->cleaned_fields['required'] : '' , 1 ); ?> value="1" />
											<span class="description"><?php _e('Check this box if this field need to be required', 'simple-custom-fields'); ?></span></td>
										</tr>
										<tr valign="top">
											<td><input type="submit" class="button-primary" value="<?php _e('Submit', 'simple-custom-fields'); ?>" /></td>
										</tr>
									</tbody>
								</table>
								
							</div>
						</div>
					</div>
					
					<div id="post-body-content">
						<div id="addressdiv" class="stuffbox">
							<h3><label for="link_url"><?php _e('Advanced settings', 'simple-custom-fields'); ?></label></h3>
							<div class="inside">
							
								<table class="form-table">
									<tbody>
										<tr valign="top">
											<th scope="row"><label for="scf[public_name]"><?php _e('Public name ', 'simple-custom-fields'); ?></label></th>
											<td><input name="scf[public_name]" type="text" value="<?php echo ( isset( $this->cleaned_fields['public_name'] ) && !empty( $this->cleaned_fields['public_name'] ) ) ? $this->cleaned_fields['public_name'] : ''; ?>" class="regular-text" />
											<span class="description"><?php _e('The field name that will be displayed on the public website', 'simple-custom-fields'); ?></span></td>
										</tr>
										<tr valign="top">
											<th scope="row"><label for="scf[css_class]"><?php _e('CSS class', 'simple-custom-fields'); ?></label></th>
											<td><input name="scf[css_class]" type="text" value="<?php echo ( isset( $this->cleaned_fields['css_class'] ) && !empty( $this->cleaned_fields['css_class'] ) ) ? $this->cleaned_fields['css_class'] : ''; ?>" class="regular-text" />
											<span class="description"><?php _e('Add a custom CSS class to the input field', 'simple-custom-fields'); ?></span></td>
										</tr>
										<tr valign="top">
											<th scope="row"><label for="scf[regexp]"><?php _e('Regular expression', 'simple-custom-fields'); ?></label></th>
											<td><input name="scf[regexp]" type="text" value="<?php echo ( isset( $this->cleaned_fields['regexp'] ) && !empty( $this->cleaned_fields['regexp'] ) ) ? $this->cleaned_fields['regexp'] : ''; ?>" class="regular-text" />
											<span class="description"><?php _e('Add a regexp condition', 'simple-custom-fields'); ?></span></td>
										</tr>
										<tr valign="top">
											<th scope="row"><label for="scf[custom_error_message]"><?php _e('Custom error message', 'simple-custom-fields'); ?></label></th>
											<td><input name="scf[custom_error_message]" type="text" value="<?php echo ( isset( $this->cleaned_fields['custom_error_message'] ) && !empty( $this->cleaned_fields['custom_error_message'] ) ) ? $this->cleaned_fields['custom_error_message'] : ''; ?>" class="regular-text" />
											<span class="description"><?php _e('Add a custom error message', 'simple-custom-fields'); ?></span></td>
										</tr>
										<tr valign="top">
											<th scope="row"><label for="scf[auto_display]"><?php _e('Auto-display', 'simple-custom-fields'); ?></label></th>
											<td><input type="checkbox" name="scf[auto_display]" <?php checked( isset( $this->cleaned_fields['auto_display'] ) ? $this->cleaned_fields['auto_display'] : '' , 1 ); ?> value="1" />
											<span class="description"><?php _e('Check this box if this field need to be displayed after the post content', 'simple-custom-fields'); ?></span></td>
										</tr>
										<tr valign="top">
											<td><input type="submit" class="button-primary" value="<?php _e('Submit', 'simple-custom-fields'); ?>" /></td>
										</tr>
									</tbody>
								</table>
								
								
							</div>
						</div>
					</div>
					
					<p><a href="<?php echo $this->admin_url . "&action=edit-cpt&". $this->cpt_or_tax['type'] . "=" . $this->cpt_or_tax['slug']; ?>"><< <?php _e('Back', 'simple-custom-fields'); ?></a></p>
					
				</div>
			
			</form>
			
		</div>
		
	<?php
	}
	
	/*
	 * Check if every required fields are set and update plugin options
	 * 
	 * @return void
	 * @author Benjamin Niess
	 */
	function checkForm(){
	
		//the custom post type must be set in the url
		if ( !isset( $this->cpt_or_tax['slug'] ) || empty( $this->cpt_or_tax['slug'] ) )
			wp_die( __('An error as occured (post type is missing). Please try again', 'simple-custom-fields'), 0, array( 'back_link' => true ) );
		
		foreach ( $_POST['scf'] as $field_name => $field_value ){
			//var_dump($field_value);
			//clean each fields and setup them in an array
			if ( !isset( $this->cleaned_fields[$field_name] ) ) {
				$this->cleaned_fields[$field_name] = isset( $field_value ) ? trim( ( $field_name != 'regexp' ) ? stripslashes( $field_value ) : $field_value ) : '';
			} else {
				$this->cleaned_fields[$field_name] = trim( ( $field_name != 'regexp' ) ? stripslashes( $field_value ) : $field_value ) ;
			}
			
			//each empty required fields are set in an array 
			if ( ( !isset( $this->cleaned_fields[$field_name] ) || empty( $this->cleaned_fields[$field_name] ) ) && $this->fields[$field_name]['required'] == true){
				$this->missing_fields[] = $field_name;
			}
			//var_dump($this->cleaned_fields);
			$this->cleaned_fields[$field_name] = $field_value ;
			
		}
		
		//if we have some missing fields...
		if ( !empty( $this->missing_fields ) ){
		
			//display error messages
			$this->message = __('Some fields are not set correctly :', 'simple-custom-fields' );
			foreach ( $this->missing_fields as $field )
				$this->message .= '<br />- ' . $this->fields[$field]['name'];
				
			$this->status = "error";
			$this->displayMessage();
			
			//show form again
			$this->displayForm();
			
		}
		//if everything is correct
		else {
			if ( $_GET['action'] == 'edit-field' ){
				$sanitized_title = stripslashes( trim( $_GET['field'] ) );
			}
			else{
				$sanitized_title = sanitize_title( $this->cleaned_fields['field_name'] );
				//check if the slug of the custom post type for this metabox for this cpt already exists or not
				foreach ( $this->settings[$this->cpt_or_tax['slug']]["metaboxes"] as $metabox_slug => $metabox_values ){
					if ( isset( $this->settings[$this->cpt_or_tax['slug']]["metaboxes"][$metabox_slug]['fields'][$sanitized_title] ) ) {
						//display error messages
						$this->message = __('This name already exists', 'simple-custom-fields' );
						$this->status = "error";
						$this->displayMessage();
						
						//show form again
						$this->displayForm();
						
						return false;
					}
				}
			}
			//call the function that update custom fields
			if ( $this->updateCptOrTaxoCustomField( 
					$sanitized_title, 
					$this->cleaned_fields['field_name'], 
					$this->cleaned_fields['field_type'], 
					$this->cleaned_fields['metabox'], 
					$this->cpt_or_tax['slug'], 
					$this->cleaned_fields['field_description'], 
					$this->cleaned_fields['default_value'], 
					"", 
					$this->cleaned_fields['regexp'], 
					$this->cleaned_fields['custom_error_message'], 
					$this->cleaned_fields['css_class'], 
					isset( $this->cleaned_fields['auto_display'] ) ? $this->cleaned_fields['auto_display'] : '' , 
					$this->cleaned_fields['public_name'], 
					isset( $this->cleaned_fields['required'] ) ? $this->cleaned_fields['required'] : '' ) 
				){
				
				$this->checkCptOrTaxo();
				$this->editCptOrTaxoFieldsContent();
			}
			
			//show success message
			$this->displayMessage(); 
		}
	}
	
	function displayFormField( $field_slug, $field_data, $post_id ){ 
		$field_value = get_post_meta( $post_id, $field_slug, true );
		if ( empty( $field_value ) && isset( $field_data['default_value'] ) && !empty( $field_data['default_value'] ) )
			$field_value = $field_data['default_value'];
		
		$css_class = ( isset( $field_data['css_class'] ) && !empty( $field_data['css_class'] ) ) ? $field_data['css_class'] : '';
		$required = ( isset( $field_data['required'] ) && !empty( $field_data['required'] ) ) ? ' *' : '';
		$description = ( isset( $field_data['field_description'] ) && !empty( $field_data['field_description'] ) ) ? $field_data['field_description'] : ''; 
	?>
		<tr valign="top">
			<th scope="row"><label for="scf[<?php echo $field_slug; ?>]"><?php echo $field_data['field_name'] . $required; ?></label></th>
			<td><Dropdown class="regular-text <?php echo $css_class  ?>" name="scf[<?php echo $field_slug; ?>]" rows="15" cols="34"><?php echo $field_value; ?></Dropdown>
			<span class="description"><?php echo $description; ?></span></td>
		</tr>
	<?php 
	}
}
?>