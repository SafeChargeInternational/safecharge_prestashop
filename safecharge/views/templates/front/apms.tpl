<script type="text/javascript" src="https://cdn.safecharge.com/safecharge_resources/v1/websdk/safecharge.js"></script>

<style type="text/css">
    #safechargesubmit #sc_pm_error {
        color: red;
        font-size: 12px;
    }

    #sc_apms_list, #sc_upos_list {
        margin-top: 15px;
        box-shadow: 0 2px 4px 0 rgba(0,0,0,0.19);
    }

    .apm_title img {
        width: 60px;
        margin: 0px 10px 6px;
    }

    #sc_apms_list .apm_container, #sc_upos_list .apm_container {
        width: 100%;
        height: 100%;
        cursor: pointer;
        padding: 0.5rem 0 0 0;
        background-color: #FFFFFF;
    }

    #sc_apms_list .apm_title, #sc_upos_list .apm_title {
        cursor: pointer;
        border-bottom: .1rem solid #939393;
    }

    #sc_apms_list .apm_title .material-icons,  #sc_upos_list .apm_title .material-icons {
        cursor: pointer;
        color: #55a985;
        font-size: 26px;
        margin-right: 20px;
        float: right;
    }

    #sc_apms_list .fa-question-circle-o, #sc_upos_list .fa-question-circle-o {
        top: 16px;
        position: absolute;
        right: 10px;
        font-size: 16px;
        color: #14B5F1;
    }

    #sc_apms_list .apm_fields, #sc_upos_list .apm_fields {
        display: none;
        background-color: #fafafa;
        border-bottom: .1rem solid #9B9B9B;
		font-family: 'arial' !important;
    }

    #scForm .apm_fields .apm_field {
        padding-left: 0.7em;
        padding-right: 0.7em;
        padding-top: 1em;
        position: relative;
        border-bottom: .1rem solid #9B9B9B;
        margin: 0px 10px 0px 10px;
    }

    #scForm .apm_fields .apm_field:last-child {
        border-bottom: 0px !important;
    }
	
	#sc_card_holder {
		width: 50% !important;
		display: inline-block !important;
	}
	
	#sc_date_cvv_holder {
		width: 46% !important;
		float: right;
	}
	
	#sc_date_cvv_holder #sc_date_holder {
		width: 40%;
		display: inline-block;
	}
	
	#sc_date_cvv_holder #sc_cvv_holder {
		width: 40%;
		float: right;
	}
	
    #scForm input  {
        border-radius: unset;
        border: 0 !important;
        background-color: inherit !important;
        border-radius: 0px !important;
        padding-bottom: 8px !important;
        padding-left: 0px !important;
        padding-right: 0px !important;
        width: 80%;
		font-size: 15px !important;
		font-family: 'arial' !important;
    }

    #scForm .field_icon {
        float: right;
    }

    .sc_hide {
        display: none;
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

    #sc_apms_list .apm_error label, #sc_upos_list .apm_error label {
        color: #E7463B;
        font-size: 12px;
        text-align: left;
        font-weight: normal;
    }

    #sc_apms_list .apm_error.error_info label, #sc_upos_list .apm_error.error_info label {
        color: #9B9B9B;
        font-style: italic;
    }

    .SfcField iframe {
        min-height: 20px !important;
    }

    .apm_field input {
        border: 0 !important;
        outline: 0 !important;
        background-color: inherit !important;
        border-radius: 0px !important;
        padding-bottom: 8px !important;
        padding-left: 0px !important;
        padding-right: 0px !important;
        width: 100%;
        box-shadow: none !important;
    }

    .fast-right-spinner {
        -webkit-animation: glyphicon-spin-r 1s infinite linear;
        animation: glyphicon-spin-r 1s infinite linear;
    }
    
    /* for the 3DS popup */
    .sfcModal-dialog {
        margin-top: 10%;
    }
	
	#cc_load_spinner {
		text-align: center;
		padding-top: 10px;
	}
	
	@media screen and (max-width: 460px) {
		#sc_fields_holder {
			padding: 0 !important;
			margin: 0 !important;
		}
		
		#sc_card_holder { display: block !important; }
		
		#sc_date_cvv_holder {
			float: none !important;
			border-bottom: 0px !important;
		}
		
		#sc_card_holder, #sc_date_cvv_holder {
			width: auto !important;
			/* .apm_field */
			padding-left: 0.7em;
			padding-right: 0.7em;
			padding-top: 1em;
			position: relative;
			border-bottom: .1rem solid #9B9B9B;
			margin: 0px 10px 0px 10px;
		}
	}
	
	@media screen and (max-width: 380px) {
		#sc_visa_mc_maestro_logo { height: 24px !important; }
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
    <span id="sc_error_msg"></span>
    <span class="close" onclick="$('#sc_pm_error').hide();">&times;</span>
</div>

<form method="post" id="scForm" action="{$formAction}">
    {if $paymentMethods}
        <h3>{l s='Choose a payment method:' mod='safecharge'}</h3>
		
		<div id="cc_load_spinner" class="sc_hide">
			<i class="material-icons fast-right-spinner">sync</i>
		</div>
		
        <ul id="sc_apms_list" class="">
            {foreach $paymentMethods as $pm}
                <li class="apm_container" style="height: auto;">
                    <div class="apm_title">
                        <i class="material-icons sc_hide">check</i>

						{if $pm.paymentMethod == 'cc_card'}
							<img src="/modules/safecharge/views/img/visa_mc_maestro.svg" alt="{$pm.paymentMethodDisplayName[0].message}" style="height: 39px; width: auto" id="sc_visa_mc_maestro_logo" />
						{else}
							<img src="{$pm.logoURL|replace:'/svg/':'/svg/solid-white/'}" alt="{$pm.paymentMethodDisplayName[0].message}" />
						{/if}
						
                        <input type="radio" id="sc_payment_method_{$pm.paymentMethod}" class="sc_hide" name="sc_payment_method" value="{$pm.paymentMethod}" />
                    </div>

                    {if in_array($pm.paymentMethod, array('cc_card', 'dc_card'))}
                        <div class="apm_fields" id="sc_{$pm.paymentMethod}">
                            <div class="apm_field">
                                <input type="text" id="sc_card_holder_name" name="{$pm.paymentMethod}[cardHolderName]" placeholder="Card holder name" style="padding-bottom: 2px !important;" />
                            </div>

							<div id="sc_fields_holder" class="apm_field">
								<div id="sc_card_holder">
									<div id="sc_card_number"></div>
								</div>

								<div id="sc_date_cvv_holder">
									<div id="sc_date_holder">
										<div id="sc_card_expiry"></div>
									</div>

									<div id="sc_cvv_holder">
										<div id="sc_card_cvc"></div>
									</div>
								</div>
							</div>
							
                            {*<div class="apm_field">
                                <div id="card-field-placeholder"></div>
                            </div>*}
                        </div>
                    {else}
                        <div class="apm_fields">
                            {foreach $pm.fields as $field}
                                <div class="apm_field">
                                    <input id="{$pm.paymentMethod}_{$field.name}" 
                                           name="{$pm.paymentMethod}[{$field.name}]" 
                                           type="{$field.type}" 
                                           {if isset($field.regex) and $field.regex}pattern="{$field.regex}"{/if} 
                                           {if !empty($field.caption[0].message)}placeholder="{$field.caption[0].message}"
                                           {elseif !empty($field.name)}placeholder="{$field.name}"
                                           {/if}
                                    />

                                    {if isset($field.regex) and $field.regex and !empty($field.validationmessage[0].message)}
                                        <i class="material-icons field_icon" onclick="showErrorLikeInfo('sc_{$field.name}')">error_outline</i>
                                        <div class="apm_error sc_hide" id="error_sc_{$field.name}">
                                            <label>{$field.validationmessage[0].message}</label>
                                        </div>
                                    {/if}
                                </div>
                            {/foreach}
                        </div>
                    {/if}
                </li>
            {/foreach}
        </ul>
        <br/>
    {/if}

    <input type="hidden" name="lst" id="sc_lst" value="{$sessionToken}" />
    <input type="hidden" name="sc_transaction_id" id="sc_transaction_id" value="" />
</form>

<script type="text/javascript">
    var selectedPM  = "";
    var payloadURL  = "";
    var ooAjaxUrl  = "{$ooAjaxUrl}";

    // for the fields
    var sfc             = null;
    var sfcFirstField   = null;
    var cardNumber		= null;
    var cardExpiry		= null;
    var cardCvc			= null;
    var scFields		= null;
//    var card            = null;
	
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

        var formValid	= true;
		var reloadForm	= false;
        selectedPM = $('input[name="sc_payment_method"]:checked').val();

        if(typeof selectedPM != 'undefined' && selectedPM != '') {
            // use cards
            if(selectedPM == 'cc_card' || selectedPM == 'dc_card') {
				if(jQuery('#sc_card_holder_name').val() === '') {
					scFormFalse("{l s='Please fill Card holder name field!' mod='safecharge'}");
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
				
                // create payment with WebSDK
                sfc.createPayment({
                    sessionToken    : "{$sessionToken}",
                    merchantId      : "{$merchantId}",
                    merchantSiteId  : "{$merchantSiteId}",
                    currency        : "{$currency}",
                    amount          : "{$amount}",
                    cardHolderName  : document.getElementById('sc_card_holder_name').value,
                //    paymentOption   : card,
                    paymentOption   : sfcFirstField,
					webMasterId		: "{$webMasterId}"
                }, function(resp){
                    console.log(resp);

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
					
						scFormFalse("{l s='Unexpected error, please try again later!' mod='safecharge'}");
                        console.error('Error with SDK response: ' + resp);
                        return;
                    }

					if(reloadForm) {
						reCreateSCFields();
					}
					else {
						$('#payment-confirmation button.btn.btn-primary').prop('disabled', false);
						$('#payment-confirmation button.btn.btn-primary .fast-right-spinner').addClass('sc_hide');
					}
                });
            }
            // use APM data
            else if(isNaN(parseInt(selectedPM))) {
                var checkId = 'sc_payment_method_' + selectedPM;

                // iterate over payment fields
                $('#' + checkId).closest('li.apm_container').find('.apm_fields input').each(function(){
                    var apmField = $(this);

                    if (
                        typeof apmField.attr('pattern') != 'undefined'
                        && apmField.attr('pattern') !== false
                        && apmField.attr('pattern') != ''
                    ) {
                        var regex = new RegExp(apmField.attr('pattern'), "i");

                        // SHOW error
                        if(apmField.val() == '' || regex.test(apmField.val()) == false) {
                            apmField.parent('.apm_field').find('.apm_error')
                                .removeClass('error_info sc_hide');

                            formValid = false;
                        }
                        else {
                            apmField.parent('.apm_field').find('.apm_error').addClass('sc_hide');
                        }
                    }
                    else if(apmField.val() == '') {
                        formValid = false;
                    }
                });

                if(!formValid) {
                    scFormFalse(scDefaultErrorMsg);
                    return;
                }

                $('form#scForm').submit();
            }
        }
        else {
            scFormFalse(scDefaultErrorMsg);
            return;
        }
    } // end of scValidateAPMFields()

    function scFormFalse(_text) {
        $('#payment-confirmation button.btn.btn-primary').prop('disabled', false);
        $('#payment-confirmation button.btn.btn-primary .fast-right-spinner').addClass('sc_hide');

        $('#sc_error_msg').html(_text);
        $('#sc_pm_error').show();
        window.location.hash = 'sc_pm_error';
        window.location.hash;
    }

    function showErrorLikeInfo(elemId) {
        $('#error_'+elemId).addClass('error_info');

        if($('#error_'+elemId).hasClass('sc_hide')) {
            $('#error_'+elemId).removeClass('sc_hide');
        }
        else {
            $('#error_'+elemId).addClass('sc_hide');
        }
    }

    /**
     * Function createSCFields
     * Call SafeCharge method and pass the parameters
     */
    function createSCFields() {
		console.log('createSCFields sessionToken', scData.sessionToken);
		
        sfc = SafeCharge(scData);

        // prepare fields
//        var fields = sfc.fields({
        scFields = sfc.fields({
            locale: "{$languageCode}"
        });
		
		cardNumber = sfcFirstField = scFields.create('ccNumber', {
			classes: scElementClasses
			,style: scFieldsStyle
		});
		cardNumber.attach('#sc_card_number');

		cardExpiry = scFields.create('ccExpiration', {
			classes: scElementClasses
			,style: scFieldsStyle
		});
		cardExpiry.attach('#sc_card_expiry');

		cardCvc = scFields.create('ccCvc', {
			classes: scElementClasses
			,style: scFieldsStyle
		});
		cardCvc.attach('#sc_card_cvc');

		/*
        card = fields.create('card', {
            iconStyle: 'solid',
            style: {
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
            },
            classes: elementClasses
        });

        card.attach('#card-field-placeholder');
		*/
    }
	
	/**
	 * Function reCreateSCFields
	 * use it after DECLINED payment try
	*/
	function reCreateSCFields() {
		console.log('reCreateSCFields');
		console.log('reCreateSCFields url', ooAjaxUrl);
	
		sfc				= null;
	//	card	= null;
		sfcFirstField	= null;
	
		$('#cc_load_spinner, #cc_load_spinner i').removeClass('sc_hide');
		$('#sc_apms_list').addClass('sc_hide');
		//$('#card-field-placeholder').html(''); // clear card container
		$('#sc_card_number, #sc_card_expiry, #sc_card_cvc').html(''); // clear card container
		
		$.ajax({
			dataType: "json",
			url: ooAjaxUrl,
			data: {}
		})
		.done(function(res) {
			console.log('reCreate response', res);
			
			if(typeof res.session_token != 'undefined' && '' != res.session_token) {
				scData.sessionToken = res.session_token;
				createSCFields();
				
				$('#cc_load_spinner').addClass('sc_hide');
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

    window.onload = function() {
        createSCFields();

		$('#payment-confirmation button')
			.on('click', function(e) {
				if($('input[name=payment-option]:checked').attr('data-module-name') == 'safecharge') {
					e.stopPropagation();
			
					$(this).prepend('<i class="material-icons fast-right-spinner sc_hide">sync</i>');
					scValidateAPMFields();
					return false;
				}
		
                {*scValidateAPMFields();

                return false;*}
            });


        {*$('#payment-confirmation button')
            .prop('type', 'button')
            .prepend('<i class="material-icons fast-right-spinner sc_hide">sync</i>')
            .on('click', function(e) {
                e.stopPropagation();
                scValidateAPMFields();

                return false;
            });*}

        $('#scForm .apm_title').on('click', function(){
            var apmCont = $(this).parent('.apm_container');
            // check current radio input
            $('input[type="radio"]', apmCont).prop('checked', true);

            // clear all upo cvv fields
            $('#scForm .upo_cvv_field').val('');

            // hide all check icons
            $('#scForm .material-icons').addClass('sc_hide');

            // show current icon
            $('.material-icons', apmCont).removeClass('sc_hide');

            // hide all apm_fields
            $('#scForm .apm_fields').fadeOut("fast");

            // show current apm_fields
            $('.apm_fields', apmCont).toggle('slow');

        });
    }
</script>