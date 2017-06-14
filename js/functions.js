/* global ajaxurl */

function submitForm(form, callback){
	var postData = form.serializeArray();
	var url_params = form.serialize();
//  var postData = new FormData(form[0]); // should work with these parameters: processData: false, contentType: false, cache: false,
	var url = form.attr('action') ? form.attr('action') : ( ajaxurl ? ajaxurl : false );
	postData.push({name: 'is_ajax', value: true});

	form_state('disable', form);
	form.find('.ajax_loader_icon').show();
	$('.has-error', form).removeClass('has-error');
	$('#msg', form).html('').removeClass('alert-dange alert-success').hide();
	$('.error', form).hide();
	
	$.ajax({
		type: 'POST', url: url, dataType: 'json', data: postData,
		success: function(data){
			if(!data || data === -1 ){
				$('#msg', form).html('Error. Please reload the page', 3);
				return false;
			}
			
			if( data.error )
					$('#msg', form).addClass('error');
				
			if( data.errors ){
				var fld, _p, fs_nn;
				$.each(data.errors, function(key, value){
					if(key.indexOf(':') > -1){
						_p = key.split(':');
						key = _p[0];
						fs_nn = _p[1];
					}
					else fs_nn = 0;
					
					fld = $('input[name="'+key+'"]', form).eq(fs_nn);
					if( fld.length === 0  )
							fld = $('textarea[name="'+key+'"]', form).eq(fs_nn);
					if( fld.length === 0  ){
						fld = $('select[name="'+key+'"]', form).eq(fs_nn);
						if( fld.length !== 0  )
								fld = fld.siblings('.selectBox');
					}
					
					if( fld.length !== 0  ){
						fld.closest('.form-group').addClass('has-error');
						fld.after('<span id="'+key+'-error" class="help-block error">'+value+'</span>');
					}
					else{
						$('#msg', form).html(value).addClass('alert-danger').show();
					}
				});
			}
			else if( data.success ){
				if( data.msg ) $('#msg', form).html(data.msg).removeClass('alert-danger').addClass('alert-success').show();
				
				if( callback && callback === 'reload')
						location.reload();
				else if( data.redirect_url )
						location.href = data.redirect_url;
				else if( data.reload ){
						location.reload();
				}
				else if(callback){
						form.data = data;
						form.url_params = url_params;
						callfunc(callback, form);
				}
			}
		},
		error	: function(data){
			$('#msg', form).html('Request Error. Try again.', 3);
		},
		complete: function(){
			form.find('.ajax_loader_icon').hide();
			form_state('enable', form);
		}
	});
}

function form_state(status, form){
	if(status === 'disable'){
		disable_form(form);
	}
	else {
		enable_form(form);
	}
}

function disable_form(form){
	$(form).find('input, select, textarea, button').attr('disabled','disabled');
	$(form).attr('disabled','disabled');
	return true;
}

function enable_form(form){
	$(form).find('input, select, textarea, button').removeAttr('disabled');
	$(form).removeAttr('disabled');
	return true;
}

function element_state(status, el){
	if(status === 'disable'){
		disable_element(el);
	}
	else {
		enable_element(el);
	}
}

function disable_element(el){
	$(el).attr('disabled','disabled');
	return true;
}

function enable_element(el){
	$(el).removeAttr('disabled');
	return true;
}

function is_disabled(sel){
	if( $(sel).attr('disabled') === 'disabled' ) return true;
	else return false;
}

function callfunc(func){
	this[func].apply(this, Array.prototype.slice.call(arguments, 1));
}