jQuery(function($){
	$('.edit-sf-auth').click(function() {
		$('#sf-auth-div').slideDown('fast');
		$(this).hide();
		return false;
	});

	$('.cancel-sf-auth').click(function() {
		$('#sf-auth-div').slideUp('fast');
		$('#sf-auth-div').siblings('a.edit-sf-auth').show();
		return false;
	});

	$('.edit-accounts').click(function() {
		$('#edit-accounts-div').slideDown('fast');
		$(this).hide();
		$('#sf-passcode-display').hide();
		return false;
	});

	$('.cancel-accounts').click(function() {
		$('#edit-accounts-div').slideUp('fast');
		$('#edit-accounts-div').siblings('a.edit-accounts').show();
		$('#sf-accounts-display').show();
		return false;
	});

	$('.save-accounts').click(function() {
		$('#edit-accounts-div').slideUp('fast');
		$('#edit-accounts-div').siblings('a.edit-accounts').show();
		$('#sf-accounts-display').html( $('#sf-passcode').val() );
		$('#sf-accounts-display').show();
		return false;
	});

	$('.edit-enable').click(function() {
		$('#enable-select').slideDown('fast');
		$(this).hide();
		return false;
	});

	$('.cancel-enable').click(function() {
		$('#enable-select').slideUp('fast');
		$('#enable-select').siblings('a.edit-enable').show();
		return false;
	});

	$('.save-enable').click(function() {
		$('#enable-select').slideUp('fast');
		$('#enable-select').siblings('a.edit-enable').show();
		$('#enable-display').html( sf_l10n[$('input:radio:checked', '#enable-select').val()] );
		return false;
	});

	$('.edit-message-options').click(function() {
		$('#message-option-select').slideDown('fast');
		$(this).hide();
		return false;
	});

	$('.cancel-message-options').click(function() {
		$('#message-option-select').slideUp('fast');
		$('#message-option-select').siblings('a.edit-message-options').show();
		return false;
	});

	$('.save-message-options').click(function() {
		$('#message-option-select').slideUp('fast');
		$('#message-option-select').siblings('a.edit-message-options').show();
		$('#message-option-display').html(sf_l10n[$('input:radio:checked', '#message-option-select').val()]);
		$('.hidden_message_option').val($('input:radio:checked', '#message-option-select').val());
		return false;
	});

	$('.shorten-links').click(function() {
		$('.ajax-loading').css('visibility','visible');

		var sf_message = $('#sf-text').val();
		var data = {
			action: 'sf-shorten-msg',
			sf_message: sf_message
		};

		$.post(ajaxurl, data, function(response) {
			$('#sf-text').val(response);
			$('#shorten-links #ajax-loading').css('visibility','hidden');
			$('#sf-text').trigger('keyup');
		});
	});
});

jQuery(document).ready(function($){
	$('#title').keyup(function() {
		//if ( 0 == $('#sf-text').val().length || $('#title').val().search( $('#sf-text').val() ) === 0 ) {
			$('#sf-text').val( $('#title').val() );
			$('#sf-text').trigger('keyup');
		//}
	});

  $('#sf-text').maxlength({
    events: [],				// Array of events to be triggerd    
    maxCharacters: sf_l10n['max'],		// Characters limit   
    statusID: "count",
    status: true,			// True to show status indicator bewlow the element    
    statusClass: "",	// The class on the status div  
    statusText: "", 					// The status text  
    notificationClass: "notification",	// Will be added when maxlength is reached  
    showAlert: false, // True to show a regular alert message    
    alertText: "You have typed too many characters.", // Text in alert message   
    slider: false // True Use counter slider    
  }); 
});