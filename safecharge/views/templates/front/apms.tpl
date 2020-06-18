<script type="text/javascript" src="https://cdn.safecharge.com/safecharge_resources/v1/websdk/safecharge.js"></script>

<style type="text/css">
    #safechargesubmit .sc_pm_error {
        color: red;
        font-size: 12px;
    }

    #sc_apms_list, #sc_upos_list { margin-top: 15px; }

	#sc_apms_list .payment-options .custom-radio, #sc_upos_list .payment-options .custom-radio {
		margin-right: 0.5rem !important;
	}
	
	.sc_visa_mc_maestro_logo {
		height: 39px;
		width: auto;
		margin-left: -2px;
	}
	
	.sc_fields_holder {
		display: none;
		margin-top: 1rem;
		margin-left: 1.8rem;
	}
	
	.sc_upos_cvvs { max-width: 100px; }
    .sc_hide { display: none; }
	
	#sc_error_msg {
		display: inline-block;
		width: 90%;
	}

    /* Chrome, Firefox, Opera, Safari 10.1+ */
    #sc_apms_list .apm_field input::placeholder, #sc_upos_list .apm_field input::placeholder,
    /* Internet Explorer 10-11 */
    #sc_apms_list .apm_field input:-ms-input-placeholder, #sc_upos_list .apm_field input:-ms-input-placeholder,
    /* Microsoft Edge */
    #sc_apms_list .apm_field input::-ms-input-placeholder, #sc_upos_list .apm_field input::-ms-input-placeholder,
    .SfcField iframe::placeholder
    {
        opacity: 0.6; /* Firefox */
    }

    #sc_apms_list .apm_error, #sc_upos_list .apm_error {
        background: none;
        width: 100%;
        margin-top: 0.2rem;
        padding-top: 5px;
    }

    .SfcField iframe {
        min-height: 20px !important;
    }

    .fast-right-spinner {
        -webkit-animation: glyphicon-spin-r 1s infinite linear;
        animation: glyphicon-spin-r 1s infinite linear;
    }
    
    /* for the 3DS popup */
    .sfcModal-dialog {
        margin-top: 10%;
    }
	
	.cc_load_spinner {
		text-align: center;
		padding-top: 10px;
	}
	
	@media (max-width: 767px) { /*xs*/
		.margin-top-5rem { margin-top: .5rem; }
	}
	
	@media screen and (max-width: 380px) {
		.sc_visa_mc_maestro_logo { height: 24px !important; }
		#sc_error_msg { width: 80%; }
	}

    @-webkit-keyframes glyphicon-spin-r {
        0% {
            -webkit-transform: rotate(0deg);
            transform: rotate(0deg);
        }

        100% {
            -webkit-transform: rotate(359deg);
            transform: rotate(359deg);
        }
    }

    @keyframes glyphicon-spin-r {
        0% {
            -webkit-transform: rotate(0deg);
            transform: rotate(0deg);
        }

        100% {
            -webkit-transform: rotate(359deg);
            transform: rotate(359deg);
        }
    }
</style>

<div id="sc_pm_error" class="alert alert-warning sc_hide">
    <span class="sc_error_msg">{l s='Please, select a payment method, and if there are fields, fill all of them!' mod='safecharge'}</span>
    <span class="close" onclick="$('#sc_pm_error').hide();">&times;</span>
</div>
	
<div id="sc_remove_upo_error" class="alert alert-warning sc_hide">
    <span class="sc_error_msg">{l s='UPO remove fails.' mod='safecharge'}</span>
    <span class="close" onclick="$('#sc_remove_upo_error').hide();">&times;</span>
</div>
	
<div id="sc_remove_upo_success" class="alert alert-success sc_hide">
    <span class="sc_error_msg">{l s='UPO remove done.' mod='safecharge'}</span>
    <span class="close" onclick="$('#sc_remove_upo_success').hide();">&times;</span>
</div>

<form method="post" id="scForm" action="{$formAction}">
	{if $upos}
		<h4 id="sc_upos_title">{l s='Choose from preferred payment methods:' mod='safecharge'}</h4>
		
		<div class="sc_hide cc_load_spinner">
			<i class="material-icons fast-right-spinner">sync</i>
		</div>
		
		<div id="sc_upos_list">
			<input type="hidden" id="sc_upo_name" name="sc_upo_name" value="" />
			
			{foreach $upos as $upo}
				<div class="payment-option clearfix">
					<label>
						<span class="custom-radio">
							<input id="upo_{$upo.userPaymentOptionId}" class="ps-shown-by-js" name="sc_payment_method" type="radio" value="{$upo.userPaymentOptionId}" data-upo-name="{$upo.paymentMethodName}">
							<span></span>
						</span>

						<span>
							{if $upo.paymentMethodName == 'cc_card'}
								<img src="/modules/safecharge/views/img/visa_mc_maestro.svg" alt="{$pm.paymentMethodDisplayName[0].message}"  class="sc_visa_mc_maestro_logo" />
								{$upo.upoData.ccCardNumber}
							{else}
								<img src="{$upo.logoURL|replace:'/svg/':'/svg/solid-white/'}" alt="{$upo.paymentMethodDisplayName[0].message}" />
								{$upo.upoName}
							{/if}
						</span>&nbsp;
						
						<a id="sc_remove_upo_{$upo.userPaymentOptionId}" rel="nofollow" href="javascript:deleteScUpo({$upo.userPaymentOptionId});">
							<i class="material-icons icon-trash">delete</i>
						</a>
							
						<i class="material-icons fast-right-spinner sc_hide">sync</i>
					</label>
						
					{if $upo.paymentMethodName == 'cc_card'}
						<div class="container-fluid sc_fields_holder">
							<section class="form-fields">
								<div class="form-group">
									<div id="cvv_for_{$upo.userPaymentOptionId}" class="form-control sc_upos_cvvs" data-upo-id="{$upo.userPaymentOptionId}"></div>
								</div>
							</section>
								
							<div class="alert alert-warning sc_hide">
								<span class="sc_error_msg">{l s='Please, fill the card CVC!' mod='safecharge'}</span>
								<span class="close" onclick="$(this).closest('.alert-warning').hide();">×</span>
							</div>
						</div>
					{/if}
				</div>
			{/foreach}
		</div>
		<br/>
	{/if}
	
    {if $paymentMethods}
        <h4 id="sc_apms_title">{l s='Choose from available payment methods:' mod='safecharge'}</h4>
		
		<div class="sc_hide cc_load_spinner">
			<i class="material-icons fast-right-spinner">sync</i>
		</div>
		
        <div id="sc_apms_list">
            {foreach $paymentMethods as $pm}
				<div class="payment-option clearfix">
					<label>
						<span class="custom-radio">
							<input id="sc_apm_{$pm.paymentMethod}" class="ps-shown-by-js" name="sc_payment_method" type="radio" value="{$pm.paymentMethod}" data-sc-is-direct="{$pm.isDirect}">
							<span></span>
						</span>
							
						<span>
							{if $pm.paymentMethod == 'cc_card'}
								<img src="/modules/safecharge/views/img/visa_mc_maestro.svg" alt="{$pm.paymentMethodDisplayName[0].message}"  class="sc_visa_mc_maestro_logo" />
							{else}
								<img src="{$pm.logoURL|replace:'/svg/':'/svg/solid-white/'}" alt="{$pm.paymentMethodDisplayName[0].message}" />&nbsp;
								{$pm.paymentMethodDisplayName[0].message}
							{/if}
						</span>
					</label>
						
					{if in_array($pm.paymentMethod, array('cc_card', 'dc_card'))}
						<div class="container-fluid sc_fields_holder">
							<section class="form-fields" id="sc_{$pm.paymentMethod}">
								<div class="form-group ">
									<input class="form-control" type="text" id="sc_card_holder_name" name="{$pm.paymentMethod}[cardHolderName]" placeholder="{l s='Card holder name' mod='safecharge'}" />
								</div>
								
								<div class="form-group row " style="margin-bottom: 0;">
									<div class="col-md-6 col-xs-12">
										<div id="sc_card_number" class="form-control"></div>
									</div>

									<div class="col-md-6 col-xs-12 margin-top-5rem">
										<div class="form-group row ">
											<div class="col-xs-6">
												<div id="sc_card_expiry" class="form-control"></div>
											</div>

											<div class="col-xs-6">
												<div id="sc_card_cvc" class="form-control"></div>
											</div>
										</div>
									</div>
								</div>
							</section>
								
							<div class="alert alert-warning sc_hide">
								<span class="sc_error_msg"></span>
								<span class="close" onclick="$(this).closest('.alert-warning').hide();">×</span>
							</div>
						</div>
					{elseif $pm.fields}
						<div class="container-fluid sc_fields_holder">
							<section class="form-fields">
								{foreach $pm.fields as $field}
									<div class="form-group ">
										<input	id="{$pm.paymentMethod}_{$field.name}" 
												class="form-control"
												name="{$pm.paymentMethod}[{$field.name}]" 
												type="{$field.type}" 
												{if isset($field.regex) and $field.regex}pattern="{$field.regex}"{/if} 
												placeholder="{if !empty($field.caption[0].message)}{$field.caption[0].message}{elseif !empty($field.name)}{$field.name}{/if}"
												data-sc-field-name="{$field.name}"
										/>
									</div>
								{/foreach}
							</section>
							
							<div class="alert alert-warning sc_hide">
								<span class="sc_error_msg"></span>
								<span class="close" onclick="$(this).closest('.alert-warning').hide();">×</span>
							</div>
						</div>
					{/if}
				</div>
            {/foreach}
        </div>
        <br/>
    {/if}

    <input type="hidden" name="lst" id="sc_lst" value="{$sessionToken}" />
    <input type="hidden" name="sc_transaction_id" id="sc_transaction_id" value="" />
</form>

<script type="text/javascript">
	var scAPMsErrorMsg = "{$scAPMsErrorMsg}";
	
    var selectedPM  = "";
    var payloadURL  = "";

    // for the fields
    var sfc             = null;
    var sfcFirstField   = null;
    var cardNumber		= null;
    var cardExpiry		= null;
    var cardCvc			= null;
	var lastCvcHolder	= '';
    var scFields		= null;
	
	// set some classes
	var scElementClasses = {
		focus: 'focus',
		empty: 'empty',
		invalid: 'invalid',
	};
	
	var scFieldsStyle = {
		base: {
			iconColor: "#c4f0ff",
			color: "#000",
			fontWeight: 500,
			fontFamily: "arial",
			fontSize: '15px',
			fontSmoothing: "antialiased",
			":-webkit-autofill": {
				color: "#fce883"
			},
			"::placeholder": {
				color: "grey" 
			}
		},
		invalid: {
			iconColor: "#FFC7EE",
			color: "red"
		}
	};
	
    var scData          = {
        merchantSiteId		: "{$merchantSideId}",
        merchantId			: "{$merchantId}",
        sessionToken		: "{$sessionToken}",
        sourceApplication	: "{$sourceApplication}"
    };

    {if $isTestEnv eq 'yes'}
        scData.env = 'test';
    {/if}
    // for the fields END
	
	var scDefaultErrorMsg = "{l s='Please, select a payment method, and fill all of its fileds!' mod='safecharge'}";

    function scValidateAPMFields() {
        $('#payment-confirmation button.btn.btn-primary').prop('disabled', true);
        $('#payment-confirmation button.btn.btn-primary .fast-right-spinner').removeClass('sc_hide');

		if('' != scAPMsErrorMsg) {
			scFormFalse("{l s=$scAPMsErrorMsg mod='safecharge'}");
			return;
		}

        selectedPM = $('input[name="sc_payment_method"]:checked').val();

		if(typeof selectedPM == 'undefined' || selectedPM == '') {
            scFormFalse();
            return;
        }
		
		var formValid		= true;
		var pmFieldsHolder	= $('input[name="sc_payment_method"]:checked')
				.closest('.payment-option')
				.find('.sc_fields_holder');
		
		var scPaymentParams = {
			sessionToken    : "{$sessionToken}",
			merchantId      : "{$merchantId}",
			merchantSiteId  : "{$merchantSiteId}",
			currency        : "{$currency}",
			amount          : "{$amount}",
			webMasterId		: "{$webMasterId}"
		};

		// use cards
		if(selectedPM == 'cc_card') {
			console.log('card');
			
			if(jQuery('#sc_card_holder_name').val() === '') {
				scFormFalse("{l s='Please, fill Card holder name!' mod='safecharge'}");
				return;
			}

			if(jQuery('#sc_card_number').hasClass('empty')) {
				scFormFalse("{l s='Please fill Card number field!' mod='safecharge'}");
				return;
			}
			if(!jQuery('#sc_card_number').hasClass('empty') && !jQuery('#sc_card_number').hasClass('sfc-complete')) {
				scFormFalse("{l s='Your card number is not correct, please check it!' mod='safecharge'}");
				return;
			}

			if(jQuery('#sc_card_expiry').hasClass('empty')) {
				scFormFalse("{l s='Please fill Card expiry date field!' mod='safecharge'}");
				return;
			}
			if(!jQuery('#sc_card_expiry').hasClass('empty') && !jQuery('#sc_card_expiry').hasClass('sfc-complete')) {
				scFormFalse("{l s='Your card expiry date is not correct, please check it!' mod='safecharge'}");
				return;
			}

			if(jQuery('#sc_card_cvc').hasClass('empty')) {
				scFormFalse("{l s='Please fill CVC field!' mod='safecharge'}");
				return;
			}
			if(!jQuery('#sc_card_cvc').hasClass('empty') && !jQuery('#sc_card_cvc').hasClass('sfc-complete')) {
				scFormFalse("{l s='Your CVC is not correct, please check it!' mod='safecharge'}");
				return;
			}
			
			scPaymentParams.cardHolderName	= document.getElementById('sc_card_holder_name').value;
			scPaymentParams.paymentOption	= sfcFirstField;

			// create payment with WebSDK
			sfc.createPayment(scPaymentParams, function(resp){
				afterSdkResponse(resp);
			});
		}
		// use CC UPO
		else if('cc_card' == $('input[name="sc_payment_method"]:checked').attr('data-upo-name')) {
			console.log('upo cc');
		
			if( ! $('#cvv_for_' + selectedPM).hasClass('sfc-complete')) {
				scFormFalse();
				return;
			}
			
			scPaymentParams.paymentOption = {
				userPaymentOptionId: selectedPM,
				card: {
					CVV: window['scCVV' + selectedPM]
				}
			};
			
			// create payment with WebSDK
			sfc.createPayment(scPaymentParams, function(resp){
				afterSdkResponse(resp);
			});
		}
		// use APM or non-CC UPO
		else {
			console.log(selectedPM, 'APM or non-CC UPO');
			
			scPaymentParams.paymentOption = {
				alternativePaymentMethod: {
					paymentMethod: selectedPM
				}
			};
			
			// iterate over payment fields, exclude AstropayPrePaid
			pmFieldsHolder.find('input').each(function(){
				var apmField = $(this);

				if (
					typeof apmField.attr('pattern') != 'undefined'
					&& apmField.attr('pattern') !== false
					&& apmField.attr('pattern') != ''
				) {
					var regex = new RegExp(apmField.attr('pattern'), "i");

					// SHOW error
					if(apmField.val() == '' || regex.test(apmField.val()) == false) {
						formValid = false;
					}
				}
				else if(apmField.val() == '') {
					formValid = false;
				}

				if(!formValid) {
					scFormFalse("{l s='Please, fill all fileds, of the selected payment method!' mod='safecharge'}");
					return;
				}

				// perpare object for the SDK, just in case
				scPaymentParams.paymentOption.alternativePaymentMethod[apmField.attr('data-sc-field-name')] = apmField.val();
			});
			
			// direct APMs can use the SDK
			if($('input[name="sc_payment_method"]:checked').attr('data-sc-is-direct') == 'true') {
				sfc.createPayment(scPaymentParams, function(resp){
					afterSdkResponse(resp);
				});
				
				return;
			}
			// direct APMs can use the SDK END
			
			// Non-direct APMs - submit the form
			$('form#scForm').submit();
		}
    }

	// process after we get the response from the webSDK
	function afterSdkResponse(resp) {
		console.log(resp);
		
		var reloadForm = false;

		if(typeof resp.result != 'undefined') {
			if(resp.result == 'APPROVED' && resp.transactionId != 'undefined') {
				jQuery('#sc_transaction_id').val(resp.transactionId);
				jQuery('#scForm').submit();
				return;
			}
			else if(resp.result == 'DECLINED') {
				reloadForm = true;
				scFormFalse("{l s='Your Payment was DECLINED. Please try another payment method!' mod='safecharge'}");
			}
			else {
				reloadForm = true;

				if(resp.hasOwnProperty('errorDescription') && resp.errorDescription != '') {
					scFormFalse(resp.errorDescription);
				}
				else if(resp.hasOwnProperty('reason') && '' != resp.reason) {
					scFormFalse(resp.reason);
				}
				else {
					scFormFalse("{l s='Error with your Payment. Please try again later!' mod='safecharge'}");
				}
			}
		}
		else {
			reloadForm = true;
			
			if(resp.hasOwnProperty('errorDescription') && resp.errorDescription != '') {
				scFormFalse(resp.errorDescription);
			}
			else if(resp.hasOwnProperty('reason') && '' != resp.reason) {
				scFormFalse(resp.reason);
			}
			else {
				scFormFalse("{l s='Error with your Payment. Please try again later!' mod='safecharge'}");
			}
			
			return;
		}

		if(reloadForm) {
			console.log('upo/card payment recreate');
			reCreateSCFields();
		}
		else {
			$('#payment-confirmation button.btn.btn-primary').prop('disabled', false);
			$('#payment-confirmation button.btn.btn-primary .fast-right-spinner').addClass('sc_hide');
		}
	}
	
	// show error message
    function scFormFalse(_text) {
        $('#payment-confirmation button.btn.btn-primary').prop('disabled', false);
        $('#payment-confirmation button.btn.btn-primary .fast-right-spinner').addClass('sc_hide');
		
		var selectedCheckbox = $('input[name="sc_payment_method"]:checked');
			
		if(selectedCheckbox.length == 0) {
			$('#sc_pm_error').show();
			
			$("body,html").animate({
				scrollTop: $('html').offset().top - 50
			}, 1000);
			
			return;
		}
		
		if(typeof _text != 'undefined' && _text != '') {
			selectedCheckbox.closest('.payment-option').find('.alert .sc_error_msg').html(_text);
		}
		
		selectedCheckbox.closest('.payment-option').find('.alert').show();

		$("body, html").animate({
			scrollTop: $('input[name="sc_payment_method"]:checked').offset().top - 50
		}, 1000);
    }

    function prepareSCFields() {
		console.log('createSCFields sessionToken', scData.sessionToken);
		
		if(!scData.hasOwnProperty('sessionToken') || '' == scData.sessionToken) {
			console.error('createSCFields sessionToken is missing.');
			
			if('' != scAPMsErrorMsg) {
				scFormFalse("{l s=$scAPMsErrorMsg mod='safecharge'}");
			}
			
			return;
		}
		
        sfc = SafeCharge(scData);

        // prepare fields
        scFields = sfc.fields({
            locale: "{$languageCode}"
        });
		
		if($('#payment-confirmation button .fast-right-spinner').length == 0) {
			$('#payment-confirmation button').prepend('<i class="material-icons fast-right-spinner sc_hide">sync</i>');
		}
    }
	
	function createSCFields() {
		var _self = $('input[name="sc_payment_method"]:checked');
			
		// hide all pm fields
		$('#scForm .sc_fields_holder').fadeOut("fast");

		// show current apm_fields
		_self.closest('.payment-option').find('.sc_fields_holder').toggle('slow');

		// create CVC object if need to
		cardCvc = null;

		if(lastCvcHolder !== '') {
			$(lastCvcHolder).html('');
		}

		if(typeof _self.attr('data-upo-name') != 'undefined') {
			$('#sc_upo_name').val(_self.attr('data-upo-name'));

			// initialize inputs
			if(_self.attr('data-upo-name') === 'cc_card') {
				console.log('upo with sdk');
				
				lastCvcHolder = '#' + _self.closest('.payment-option').find('.sc_upos_cvvs').attr('id');

				cardCvc = scFields.create('ccCvc', {
					classes: scElementClasses
					,style: scFieldsStyle
				});
				cardCvc.attach(lastCvcHolder);
			}
		}
		else {
			$('#sc_upo_name').val('');

			// initialize inputs for CC
			if(_self.val() === 'cc_card') {
				console.log('cc with sdk');
				
				$('#sc_card_number').html('');
				cardNumber = sfcFirstField = scFields.create('ccNumber', {
					classes: scElementClasses
					,style: scFieldsStyle
				});
				cardNumber.attach('#sc_card_number');

				$('#sc_card_expiry').html('');
				cardExpiry = scFields.create('ccExpiration', {
					classes: scElementClasses
					,style: scFieldsStyle
				});
				cardExpiry.attach('#sc_card_expiry');

				lastCvcHolder = '#sc_card_cvc';

				cardCvc = scFields.create('ccCvc', {
					classes: scElementClasses
					,style: scFieldsStyle
				});
				cardCvc.attach(lastCvcHolder);
			}
			// initialize inputs for APMs with fields
			else if(_self.closest('.payment-option').find('.sc_fields_holder').length > 0) {
				console.log('apm with sdk');
				
				{*_self.closest('.payment-option').find('.sc_fields_holder .sc_field_container').each(function() {
					var containerId = $(this).attr('id');
					$('#' + containerId).html('');
					
					console.log(containerId);
					
					if(containerId.search('cc_card_number') >= 0 || containerId.search('ccCardNumber') >= 0) {
						cardNumber = sfcFirstField = scFields.create('ccNumber', {
							classes: scElementClasses
							,style: scFieldsStyle
						});
						cardNumber.attach('#' + containerId);
					}
					
					if(containerId.search('cc_cvv') >= 0) {
						cardCvc = sfcFirstField = scFields.create('ccCvc', {
							classes: scElementClasses
							,style: scFieldsStyle
						});
						cardCvc.attach('#' + containerId);
					}
					
					if(containerId.search('cc_exp_month') >= 0 || containerId.search('ccExpMonth') >= 0) {
						cardExpiry = sfcFirstField = scFields.create('ccExpiration', {
							classes: scElementClasses
							,style: scFieldsStyle
						});
						cardExpiry.attach('#' + containerId);
					}
					
				});*}
			}
		}
	}
	
	/**
	 * Function reCreateSCFields
	 * use it after DECLINED payment try
	*/
	function reCreateSCFields() {
		console.log('reCreateSCFields');
	
		sfc				= null;
		sfcFirstField	= null;
	
		$('.cc_load_spinner, .cc_load_spinner i').removeClass('sc_hide');
		$('#sc_apms_list').addClass('sc_hide');
		$('#sc_card_number, #sc_card_expiry, #sc_card_cvc, .sc_upos_cvvs').html(''); // clear SDK containers
		
		$.ajax({
			dataType: "json",
			url: "{$ooAjaxUrl}",
			data: {}
		})
		.done(function(res) {
			console.log('reCreate response', res);
			
			if(typeof res.session_token != 'undefined' && '' != res.session_token) {
				scData.sessionToken = res.session_token;
				prepareSCFields();
				
				$('.cc_load_spinner').addClass('sc_hide');
				$('#sc_apms_list').removeClass('sc_hide');
				
				$('#payment-confirmation button.btn.btn-primary').prop('disabled', false);
				$('#payment-confirmation button.btn.btn-primary .fast-right-spinner').addClass('sc_hide');
			}
			else {
				window.location.reload();
			}
		})
		.fail(function(e) {
			console.error('reCreate fail');
			window.location.reload();
		});
	}
	
	function deleteScUpo(upoId) {
		if(confirm("{l s='Do you want to delete this UPO?' mod='safecharge'}")) {
			var thisElem = $('#sc_remove_upo_' + upoId);
			var parentLabel = thisElem.closest('label');
			
			thisElem.addClass('sc_hide');
			parentLabel.find('.fast-right-spinner').removeClass('sc_hide');
			
			$.ajax({
				dataType: "json",
				url: "{$scDeleteUpoUrl}",
				data: {
					upoId: upoId
				}
			})
			.done(function(res) {
				console.log('delete UPO response', res);

				if(typeof res.status != 'undefined' && 'success' == res.status) {
					parentLabel.closest('.payment-option').remove();
					$('#sc_remove_upo_success').show();
				}
				else {
					parentLabel.find('.fast-right-spinner').addClass('sc_hide');
					$('#sc_remove_upo_error').show();
				}
			})
			.fail(function(e) {
				parentLabel.find('.fast-right-spinner').addClass('sc_hide');
				$('#sc_remove_upo_error').show();
			});
		}
	}

    window.onload = function() {
        prepareSCFields();

		$('#payment-confirmation button')
			.on('click', function(e) {
				if($('input[name=payment-option]:checked').attr('data-module-name') == 'safecharge') {
					e.stopPropagation();
			
					scValidateAPMFields();
					return false;
				}
            });

		$('body').on('change', 'input[name="sc_payment_method"]', function() {
			createSCFields();
		});
    }
</script>