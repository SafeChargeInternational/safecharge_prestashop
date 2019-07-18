{if $scApi neq 'rest'}
    {l s='Pay secure via credit / debit card.' mod='Modules.safecharge'}
{else}
    <script type="text/javascript" src="https://dev-mobile.safecharge.com/cdn/WebSdk/dist/safecharge.js"></script>
        
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
        }

        #scForm .apm_fields .apm_field {
            padding-left: 0.7em;
            padding-right: 0.7em;
            padding-top: 1em;
            position: relative;
            border-bottom: .1rem solid #9B9B9B;
            margin: 0px 10px 0px 10px;
        }

        #scForm .apm_fields .apm_field:last-child, #sc_card_cvc {
            border-bottom: 0px !important;
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
            color: inherit !important;
        }

        .apm_field input, .SfcField iframe:focus {
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
        {l s='Please, select a payment method, and fill all of its fileds!' mod='Modules.safecharge'}
        <span class="close" onclick="$('#sc_pm_error').hide();">&times;</span>
    </div>

    <form method="post" id="scForm" action="{$formAction}">
        {if $upos}
            <h3>{l s='Choose from you prefered payment methods:' mod='Modules.safecharge'}</h3>
            <ul id="sc_upos_list" class="">
                {foreach $upos as $upo}
                    <li class="apm_container" style="height: auto;">
                        <div class="apm_title">
                            <i class="material-icons sc_hide">check</i>

                            {if isset($upo.upoData.brand, $icons[$upo.upoData.brand])}
                                <img src="{$icons[$upo.upoData.brand]|replace:'/svg/':'/svg/solid-white/'}" />
                                {if isset($upo.upoData.ccCardNumber)}
                                    <span>{$upo.upoData.ccCardNumber}</span>
                                {/if}
                            {else}
                                <img src="{$icons[$upo.paymentMethodName]|replace:'/svg/':'/svg/solid-white/'}" />
                            {/if}

                            <input type="radio" class="sc_hide" name="sc_payment_method" value="{$upo.userPaymentOptionId}" />
                        </div>

                        {if in_array($upo.paymentMethodName, array("cc_card", "dc_card"))}
                            <div class="apm_fields">
                                <div class="apm_field">
                                    <input id="upo_cvv_field_{$upo.userPaymentOptionId}" class="upo_cvv_field" name="upo_cvv_field_{$upo.userPaymentOptionId}" type="text" pattern="^[0-9]{literal}{3,4}{/literal}$" placeholder="CVV Number">
                                </div>
                            </div>
                        {/if}
                    </li>
                {/foreach}
            </ul>
            <br/>
        {/if}

        {if $paymentMethods}
            <h3>{l s='Choose from the other payment methods:' mod='Modules.safecharge'}</h3>
            <ul id="sc_apms_list" class="">
                {foreach $paymentMethods as $pm}
                    <li class="apm_container" style="height: auto;">
                        <div class="apm_title">
                            <i class="material-icons sc_hide">check</i>

                            <img src="{$pm.logoURL|replace:'/svg/':'/svg/solid-white/'}" alt="{$pm.paymentMethodDisplayName[0].message}" />
                            <input type="radio" id="sc_payment_method_{$pm.paymentMethod}" class="sc_hide" name="sc_payment_method" value="{$pm.paymentMethod}" />
                        </div>
                        
                        {if in_array($pm.paymentMethod, array('cc_card', 'dc_card', 'paydotcom'))}
                            <div class="apm_fields" id="sc_{$pm.paymentMethod}">
                                <div id="sc_card_number" class="apm_field"></div>
                                <div id="sc_card_expiry" class="apm_field"></div>
                                <div id="sc_card_cvc" class="apm_field"></div>
                                <input type="hidden" id="{$pm.paymentMethod}_ccTempToken" name="{$pm.paymentMethod}[ccTempToken]" />
                            </div>
                        {else}
                            <div class="apm_fields">
                                {foreach $pm.fields as $field}
                                    <div class="apm_field">
                                        <input id="{$pm.paymentMethod}_{$field.name}" name="{$pm.paymentMethod}[{$field.name}]" type="{$field.type}" {if isset($field.regex) and $field.regex}pattern="{$field.regex}"{/if} {if isset($field.caption[0].message)}placeholder="{$field.caption[0].message}"{/if} />

                                        {if isset($field.regex) and $field.regex}
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
    </form>

    <script type="text/javascript">
        var selectedPM  = "";
        var payloadURL  = "";
        var tokAPMs     = ['cc_card', 'dc_card', 'paydotcom'];
        
        // for the fields
        var sfc = null;
        var sfcFirstField = null;
        var scData = {
            merchantSiteId: {$merchantSideId}
            ,sessionToken: "{$sessionToken}"
        };

        {if $isTestEnv eq 'yes'}
            scData.env = 'test';
        {/if}
        // for the fields END

        function scValidateAPMFields() {
            $('#payment-confirmation button.btn.btn-primary').prop('disabled', true);
            $('#payment-confirmation button.btn.btn-primary .fast-right-spinner').removeClass('sc_hide');

            var formValid = true;
            selectedPM = $('input[name="sc_payment_method"]:checked').val();

            if(typeof selectedPM != 'undefined' && selectedPM != '') {
                // use cards
                if(selectedPM == 'cc_card' || selectedPM == 'dc_card' || selectedPM == 'paydotcom') {
                    sfc.getToken(sfcFirstField).then(function(result) {
                        if (result.status !== 'SUCCESS') {
                            try {
                                if(result.reason) {
                                    alert(result.reason);
                                }
                                else if(result.error.message) {
                                    alert(result.error.message);
                                }
                            }
                            catch (exception) {
                                console.log(exception);
                                alert("{l s='Unexpected error, please try again later!' mod='Modules.safecharge'}");
                            }

                            $('#payment-confirmation button.btn.btn-primary').prop('disabled', false);
                            $('#payment-confirmation button.btn.btn-primary .fast-right-spinner').addClass('sc_hide');
                        }
                        else {
                            jQuery('#' + selectedPM + '_ccTempToken').val(result.ccTempToken);
                            jQuery('#sc_lst').val(result.sessionToken);
                            jQuery('#scForm').submit();
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
                        scFormFalse();
                        return;
                    }

                    $('form#scForm').submit();
                }
                // use UPO data
                else {
                    if(
                        $('#upo_cvv_field_' + selectedPM).length > 0
                        && $('#upo_cvv_field_' + selectedPM).val() == ''
                    ) {
                        scFormFalse();
                        return;
                    }
                
                    jQuery('#scForm').submit();
                }
            }
            else {
                scFormFalse();
                return;
            }
        } // end of scValidateAPMFields()

        function scFormFalse() {
            $('#payment-confirmation button.btn.btn-primary').prop('disabled', false);
            $('#payment-confirmation button.btn.btn-primary .fast-right-spinner').addClass('sc_hide');

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
            sfc = SafeCharge(scData);

            // prepare fields
            var fields = sfc.fields({
                locale: "{$languageCode}"
            });

            // set some classes
            var elementClasses = {
                focus: 'focus',
                empty: 'empty',
                invalid: 'invalid',
            };

            // describe fields
            var cardNumber = sfcFirstField = fields.create('ccNumber', {
                classes: elementClasses
            });
            cardNumber.attach('#sc_card_number');

            var cardExpiry = fields.create('ccExpiration', {
                classes: elementClasses
            });
            cardExpiry.attach('#sc_card_expiry');

            var cardCvc = fields.create('ccCvc', {
                classes: elementClasses
            });
            cardCvc.attach('#sc_card_cvc'); 
        }

        window.onload = function() {
            createSCFields();
            
            $('#payment-confirmation button')
                .prop('type', 'button')
                .prepend('<i class="material-icons fast-right-spinner sc_hide">sync</i>')
                .on('click', function(e) {
                    e.stopPropagation();
                    scValidateAPMFields();

                    return false;
                });
            

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
{/if}