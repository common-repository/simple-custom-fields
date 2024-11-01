jQuery(function(){

	//check required fields with jQuery validation
	jQuery("#post").validate();
	
	//add new empty field value
	jQuery(".add_value").click(function(){
		var newvalue;
		newvalue = jQuery('.sanitized_value:first').clone().appendTo('.available_values');
		newvalue.find( 'input[type="text"], input[type="radio"]' ).attr( 'name', '' ).attr( 'value', '' ).addClass( 'unsanitized' );
	});	
	
	//modify input names before submit
	jQuery('form#edit_field').submit(function(e){
		
		//stop for submission
		e.preventDefault();
		
		//jQuery(".unsanitized").attr('value', 'toto');

		//call the ajax function that sanitize title for each new field
		jQuery(".unsanitized").each(function(){
		//console.log(jQuery(".unsanitized"));
		console.log(jQuery(this).val());
			jQuery.ajax({
				type: "GET",
				url: ajaxurl,
				data: { "action" : "sanitize_field_name", "title" : "Copain"},
				complete: function {
				}
			});
		});

		
		//this.submit();
	});
});