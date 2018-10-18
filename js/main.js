var $ = jQuery.noConflict();

$(function(){
	$('.ajax_form').submit(function(){
		submitForm( $(this) );
		return false;
	});
});