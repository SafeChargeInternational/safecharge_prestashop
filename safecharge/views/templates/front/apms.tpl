<script type="text/javascript" src="https://cdn.safecharge.com/safecharge_resources/v1/websdk/safecharge.js"></script>

<style type="text/css">
	#scForm div, #scForm p { display: block; }
	#scForm span, #scForm label { display: inline; }
	
	#scForm *, ::after, ::before {
		box-sizing: revert;
	}
	
	#scForm h4 {
		font-size: 1.125rem;
		margin-bottom: .5rem;
		font-family: Noto Sans, sans-serif;
		font-weight: 700;
		line-height: 1.1;
		color: black;
		margin-top: 0;
	}
	
	#scForm h4:after, #scForm h4:before {
		box-sizing: inherit;
	}
	
	/***/
	
	#scForm #sc_apms_list, #scForm #sc_upos_list {
		margin-top: 15px;
		font-size: .875rem;
		color: #232323;
		direction: ltr;
		font-family: Noto Sans,sans-serif;
		line-height: 1.25em;
	}
	
	#scForm #sc_apms_list:after,
	#scForm #sc_apms_list:before,
	#scForm #sc_upos_list:after 
	#scForm #sc_upos_list:before {
		box-sizing: inherit;
	}
	
	/***/
	
	#scForm .payment-option {
		margin-bottom: .5rem;
		font-size: .875rem;
		color: #232323;
		direction: ltr;
		font-family: Noto Sans,sans-serif;
		line-height: 1.25em;
	}
	
	#scForm .payment-option  p.help-block {
		margin-top: .625rem;
		font-size: .9375rem;
		color: #7a7a7a;
		font-weight: 400;
		margin-bottom: 1rem;
		direction: ltr;
		font-family: Noto Sans,sans-serif;
		line-height: 1.25em;
	}
	
	#scForm .payment-option p.help-block b {
		font-weight: bolder;
		font-size: .9375rem;
		color: #7a7a7a;
		direction: ltr;
		font-family: Noto Sans,sans-serif;
		line-height: 1.25em;
	}
	
	#scForm .payment-option label {
		display: block;
		color: #232323;
		text-align: left;
		font-size: .875rem;
		margin-bottom: .5rem;
		touch-action: manipulation;
		direction: ltr;
		font-family: Noto Sans,sans-serif;
		line-height: 1.25em;
	}
	
	#scForm .payment-option label:first-child { text-align: left; }
	
	#scForm .payment-option label .custom-radio {
		margin-right: 1.25rem;
		display: inline-block;
		position: relative;
		width: 20px;
		height: 20px;
		vertical-align: middle;
		cursor: pointer;
		border-radius: 50%;
		border: 2px solid #7a7a7a;
		background: #fff;
		color: #232323;
		text-align: right;
		font-size: .875rem;
		direction: ltr;
		font-family: Noto Sans,sans-serif;
		line-height: 1.25em;
		margin-right: 0.5rem
	}
	
	#scForm .payment-option label span:last-child { float: right; line-height: 36px; }
	
	#scForm .payment-option label .custom-radio input[type="radio"] {
		height: 1.25rem;
		width: 1.25rem;
		opacity: 0;
		cursor: pointer;
		box-sizing: border-box;
		padding: 0;
		line-height: inherit;
		touch-action: manipulation;
		overflow: visible;
		font: inherit;
		margin: 0;
		color: #232323;
		text-align: right;
		direction: ltr;
		margin-right: 0.5rem
	}
	
	#scForm .payment-option label .sc_visa_mc_maestro_logo {
		height: 39px;
		width: auto;
		margin-left: -2px;
	}
	
	#scForm .payment-option label img {
		vertical-align: middle;
		border-style: none;
		color: #232323;
		text-align: right;
		font-size: .875rem;
		direction: ltr;
		font-family: Noto Sans,sans-serif;
		line-height: 1.25em;
	}
	
	#scForm .payment-option .sc_fields_holder {
		margin-top: 1rem;
		margin-left: 3rem;
		display: none;
	}
	
	#scForm .sc_upos_cvvs { max-width: 100px; }
	
	#scForm .alert {
		font-size: .8125rem;
		padding: .75rem 1.25rem;
		margin-bottom: 1rem;
		border: 1px solid transparent;
		border-radius: 0;
		direction: ltr;
		font-family: Noto Sans,sans-serif;
		line-height: 1.25em;
	}
	
	#scForm .alert-warning {
		background-color: rgba(255,154,82,.3);
		border-color: #ff9a52;
		color: #232323;
		display: none;
	}
	
	#scForm .close {
		float: right;
		font-size: 1.5rem;
		font-weight: 700;
		line-height: 0.5;
		color: #000;
		text-shadow: 0 1px 0 #fff;
		opacity: .2;
		direction: ltr;
		font-family: Noto Sans,sans-serif;
	}
	
    #safechargesubmit .sc_pm_error {
        color: red;
        font-size: 12px;
    }

	#sc_error_msg {
		display: inline-block;
		width: 90%;
	}
	
	body .sc_hide { display: none !important; }
	

    /* Chrome, Firefox, Opera, Safari 10.1+ */
    #scForm #sc_apms_list .apm_field input::placeholder,
	#scForm #sc_upos_list .apm_field input::placeholder,
    /* Internet Explorer 10-11 */
    #scForm #sc_apms_list .apm_field input:-ms-input-placeholder,
	#scForm #sc_upos_list .apm_field input:-ms-input-placeholder,
    /* Microsoft Edge */
    #scForm #sc_apms_list .apm_field input::-ms-input-placeholder,
	#scForm #sc_upos_list .apm_field input::-ms-input-placeholder,
    #scForm .SfcField iframe::placeholder
    {
        opacity: 0.6; /* Firefox */
    }

    #scForm #sc_apms_list .apm_error, #scForm #sc_upos_list .apm_error {
        background: none;
        width: 100%;
        margin-top: 0.2rem;
        padding-top: 5px;
    }

    #scForm .SfcField, #scForm .sc_fields_holder input {
		width: auto;
		padding: 5px;
		margin-bottom: 1rem;
		border: 2px solid lightgray;
		border-radius: 0px;
		background-color: white;
	}
	
	#scForm .SfcField {
		padding-bottom: 4px;
		padding-top: 5px;
	}
	
    #scForm .SfcField iframe {
        min-height: 20px;
    }

    #scForm img.fast-right-spinner {
        -webkit-animation: sc_spin 1s infinite linear;
        animation: sc_spin 1s infinite linear;
    }
    
    /* for the 3DS popup */
    .sfcModal-dialog {
        margin-top: 10%;
    }
	
	#scForm .cc_load_spinner {
		text-align: center;
		padding-top: 10px;
	}
	
	#scForm .cc_load_spinner img {
		-webkit-animation: sc_spin 1s infinite linear;
        animation: sc_spin 1s infinite linear;
	}
	
	@media screen and (max-width: 380px) {
		#scForm .sc_visa_mc_maestro_logo { height: 24px !important; }
		#sc_error_msg { width: 80%; }
	}

    @-webkit-keyframes sc_spin {
        0% {
            -webkit-transform: rotate(0deg);
            transform: rotate(0deg);
        }

        100% {
            -webkit-transform: rotate(359deg);
            transform: rotate(359deg);
        }
    }

    @keyframes sc_spin {
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

{if $customStyle}
	<style type="text/css">
		{$customStyle}
	</style>
{/if}

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
		
		<div class="cc_load_spinner sc_hide">
			<img class="sc_rotate_img" src="/modules/safecharge/views/img/loading.png" alt="loading..." />
		</div>
		
		<div id="sc_upos_list">
			<input type="hidden" id="sc_upo_name" name="sc_upo_name" value="" />
			
			{foreach $upos as $upo}
				<div class="payment-option">
					<p class="help-block sc_hide"><b></b></p>
					
					<label>
						<span class="custom-radio">
							<input id="upo_{$upo.userPaymentOptionId}" class="ps-shown-by-js" name="sc_payment_method" type="radio" value="{$upo.userPaymentOptionId}" data-upo-name="{$upo.paymentMethodName}">
							<span></span>
						</span>

						{if $upo.paymentMethodName == 'cc_card'}
							<img src="/modules/safecharge/views/img/visa_mc_maestro.svg" alt="{$pm.paymentMethodDisplayName[0].message}"  class="sc_visa_mc_maestro_logo" />
						{else}
							<img src="{$upo.logoURL|replace:'/svg/':'/svg/solid-white/'}" alt="{$upo.paymentMethodDisplayName[0].message}" />
						{/if}&nbsp;
						
						<span>
							{if $upo.paymentMethodName == 'cc_card'}
								{$upo.upoData.ccCardNumber}
							{else}
								{$upo.upoName}
							{/if}

							<a id="sc_remove_upo_{$upo.userPaymentOptionId}" rel="nofollow" href="javascript:deleteScUpo({$upo.userPaymentOptionId});">
								<img src="/modules/safecharge/views/img/trash.png" alt="delete..." />
							</a>

							<img src="/modules/safecharge/views/img/loading.png" class="fast-right-spinner sc_hide" alt="sync..." />
						</span>
					</label>
						
					{if $upo.paymentMethodName == 'cc_card'}
						<div class="sc_fields_holder">
							<div id="cvv_for_{$upo.userPaymentOptionId}" class="sc_upos_cvvs" data-upo-id="{$upo.userPaymentOptionId}"></div>
								
							<div class="alert alert-warning">
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
			<img src="/modules/safecharge/views/img/loading.png" class="fast-right-spinner sc_hide" alt="sync..." />
		</div>
		
        <div id="sc_apms_list">
            {foreach $paymentMethods as $pm}
				<div class="payment-option">
					<p class="help-block sc_hide"><b></b></p>
					
					<label>
						<span class="custom-radio">
							<input id="sc_apm_{$pm.paymentMethod}" class="ps-shown-by-js" name="sc_payment_method" type="radio" value="{$pm.paymentMethod}" data-sc-is-direct="{$pm.isDirect}">
							<span></span>
						</span>
							
						{if $pm.paymentMethod == 'cc_card'}
							<img src="/modules/safecharge/views/img/visa_mc_maestro.svg" alt="{$pm.paymentMethodDisplayName[0].message}"  class="sc_visa_mc_maestro_logo" />
							<span></span>
						{else}
							<img src="{$pm.logoURL|replace:'/svg/':'/svg/solid-white/'}" alt="{$pm.paymentMethodDisplayName[0].message}" />&nbsp;
							{if $showAPMsName eq 1}<span>{$pm.paymentMethodDisplayName[0].message}</span>{/if}
						{/if}
					</label>
						
					{if in_array($pm.paymentMethod, array('cc_card', 'dc_card'))}
						<div class="sc_fields_holder" id="sc_{$pm.paymentMethod}">
							<input class="" type="text" id="sc_card_holder_name" name="{$pm.paymentMethod}[cardHolderName]" placeholder="{l s='Card holder name' mod='safecharge'}" />

							<div id="sc_card_number" class=""></div>
							<div id="sc_card_expiry" class=""></div>
							<div id="sc_card_cvc" class=""></div>
								
							<div class="alert alert-warning">
								<span class="sc_error_msg"></span>
								<span class="close" onclick="$(this).closest('.alert-warning').hide();">×</span>
							</div>
						</div>
					{elseif $pm.fields}
						<div class="sc_fields_holder">
							{foreach $pm.fields as $field}
								<input	id="{$pm.paymentMethod}_{$field.name}" 
										class=""
										name="{$pm.paymentMethod}[{$field.name}]" 
										type="{$field.type}" 
										{if isset($field.regex) and $field.regex}pattern="{$field.regex}"{/if} 
										placeholder="{if !empty($field.caption[0].message)}{$field.caption[0].message}{elseif !empty($field.name)}{$field.name}{/if}"
										data-sc-field-name="{$field.name}"
								/>
							{/foreach}
							
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
	var scAPMsErrorMsg	= "{$scAPMsErrorMsg}";
	
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
        scData.env = 'int';
    {/if}
    // for the fields END
	
	var scDefaultErrorMsg	= "{l s='Please, select a payment method, and fill all of its fileds!' mod='safecharge'}";
	var scPayButton			= '#payment-confirmation button[type="submit"]';
	var scPayButtonOriginId = '';

    function scValidateAPMFields() {
        $(scPayButton).prop('disabled', true);
        $(scPayButton + ' .fast-right-spinner').removeClass('sc_hide');

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
			webMasterId		: "{$webMasterId}",
			userTokenId		: "{$userTokenId}"
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
		else if(
			typeof $('input[name="sc_payment_method"]:checked').attr('data-upo-name') != 'undefined'
			&& 'cc_card' == $('input[name="sc_payment_method"]:checked').attr('data-upo-name')
		) {
			console.log('upo cc');
		
			if($('#cvv_for_' + selectedPM + '.sfc-complete').length == 0) {
				scFormFalse();
				return;
			}
			
			scPaymentParams.paymentOption = {
				userPaymentOptionId: selectedPM,
				card: {
					CVV: cardCvc
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
			$(scPayButton).prop('disabled', false);
			$(scPayButton + ' .fast-right-spinner').addClass('sc_hide');
		}
	}
	
	// show error message
    function scFormFalse(_text) {
        $(scPayButton).prop('disabled', false);
        $(scPayButton + ' .fast-right-spinner').addClass('sc_hide');
		
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
		
		if($(scPayButton + ' .fast-right-spinner').length == 0) {
			$(scPayButton).prepend('<img src="/modules/safecharge/views/img/loading.png" class="fast-right-spinner sc_hide" alt="sync..." />');
		}
    }
	
	function createSCFields() {
		var _self = $('input[name="sc_payment_method"]:checked');
			
		// hide all pm fields
		$('#scForm .sc_fields_holder').fadeOut("fast");
		$('#scForm p.help-block').addClass("sc_hide");

		// show current apm_fields
		_self.closest('.payment-option').find('.sc_fields_holder').toggle('slow');
		_self.closest('.payment-option').find('p.help-block').removeClass('sc_hide');

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
				createSCFields();
				
				$('.cc_load_spinner').addClass('sc_hide');
				$('#sc_apms_list').removeClass('sc_hide');
				
				$(scPayButton).prop('disabled', false);
				$(scPayButton + ' .fast-right-spinner').addClass('sc_hide');
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
	
	function scFindPaymentButton() {
		// One Page Checkout PS
		if(typeof OnePageCheckoutPS != 'undefined' && $('#btn_place_orders').length == 1) {
			scPayButtonOriginId = '#btn_place_orders';
			scPayButton = '#sc_pay_button';
			
			$('#btn_place_orders').attr('id', scPayButton);
		}
	}

	document.addEventListener('DOMContentLoaded', function(event) {
        prepareSCFields();

		$('body').on('change', 'input[name="sc_payment_method"]', function() {
			createSCFields();
		});

		// find payment button
		$('input[name=payment-option]').on('change', function() {
			console.log('payment-option', $('input[name=payment-option]:checked').attr('data-module-name'));
			
			if ($('input[name=payment-option]:checked').attr('data-module-name') == 'safecharge') {
				if($('#scForm').closest('#payment-confirmation').length == 0) {
					scFindPaymentButton();
				}
			}
			else if (scPayButtonOriginId != scPayButton && '' != scPayButtonOriginId) {
				$(scPayButton).attr('id', scPayButtonOriginId);
			}
		});
		// find payment button END

		$(scPayButton)
			.on('click', function(e) {
				if($('input[name=payment-option]:checked').attr('data-module-name') == 'safecharge') {
					e.stopPropagation();
			
					scValidateAPMFields();
					return false;
				}
            });

		{if $customAPMsNote}
			$('#scForm').find('p.help-block b').text("{$customAPMsNote}"
				+ ' "' + $(scPayButton).text().trim() + '".');
		{/if}
		
		{if $preselectCC eq 1}
			$('#sc_apm_cc_card').trigger('click');
		{/if}
	});
</script>