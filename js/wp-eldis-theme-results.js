function update_results_from_keywords($form){
	var selection = { 
		type: 'POST',
		url: wpajax.ajaxurl,
		data: 'action=theme_results&keywords='+jQuery('#keywords').val(),
		dataType: 'html',
		complete: function(){
			$form.removeClass('submitting-ajax');
		},
		error: function(xhr, textStatus, errorThrown, XMLHttpRequest) { 
			jQuery('<p>Sorry, your request could not be completed at the moment due to ' + errorThrown + ' </p>').appendTo('#theme_results') 
			}, 
		success: function(html, htmlStatus) {
			jQuery('#theme_results').addClass('show_results'); 
			jQuery('#theme_results').html(html); 
			} 
	}
	
	$form.addClass('submitting-ajax');
	jQuery.ajax(selection);	
}

jQuery(document).ready(function(){
	var $search_input = jQuery('#theme_results_button'),
			$search_form = $search_input.parents('form');
	
	$search_input.click(function(){
		update_results_from_keywords($search_form);
	});
	
	$search_form.submit(function(){				
		return !$search_form.hasClass('submitting-ajax');
	});
	
});