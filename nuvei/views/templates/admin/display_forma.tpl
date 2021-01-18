<br/>
<div class="alert alert-info">
    <img src="/modules/nuvei/logo.png" style="float:left; margin-right:15px;" height="60" />
    <p><a target="_blank" href="http://www.nuvei.com/"><strong>Nuvei</strong></a></p>
    <p>{l s='Nuvei provides secure and reliable turnkey solutions for small to medium sized e-commerce businesses. Powered by Nuvei Technologies and backed by more than a decade of experience in the e-commerce industry, with expert international staff, Nuvei has the skills, tools, technology, and expertise to accept software vendors and digital service providers and help them succeed online with confidence in a secure and reliable environment. It also helps them promote their software and enjoy increased sales volumes.' mod='nuvei'}</p>
</div>

<form class="defaultForm form-horizontal" action="{$smarty.server['REQUEST_URI']}" method="post" enctype="multipart/form-data">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Settings' mod='sc'}
        </div>
								
        <div class="form-wrapper">
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Default title' mod='nuvei'}</label>
                <div class="col-lg-9">
                    <input type="text" name="SC_FRONTEND_NAME" value="{if Configuration::get('SC_FRONTEND_NAME')}{Configuration::get('SC_FRONTEND_NAME')}{else}{l s='Secure Payment with Nuvei' mod='nuvei'}{/if}" />
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3 required"> {l s='Merchant Id' mod='nuvei'}</label>
                <div class="col-lg-9">
                    <input type="text" name="SC_MERCHANT_ID" value="{Configuration::get('SC_MERCHANT_ID')}" required="" />
                </div>
            </div>
                
            <div class="form-group">
                <label class="control-label col-lg-3 required"> {l s='Merchant Site Id' mod='nuvei'}</label>
                <div class="col-lg-9">
                    <input type="text" name="SC_MERCHANT_SITE_ID" value="{Configuration::get('SC_MERCHANT_SITE_ID')}" required="" />
                </div>
            </div>
                
            <div class="form-group">
                <label class="control-label col-lg-3 required"> {l s='Merchant Secret Key' mod='nuvei'}</label>
                <div class="col-lg-9">
                    <input type="text" name="SC_SECRET_KEY" value="{Configuration::get('SC_SECRET_KEY')}" required="" />
                </div>
            </div>
                
            <div class="form-group">
                <label class="control-label col-lg-3 required"> {l s='Hash type' mod='nuvei'}</label>
                <div class="col-lg-9">
                    <select name="SC_HASH_TYPE" required="">
                        <option value="">{l s='Please, select an option...' mod='nuvei'}</option>
                        <option value="sha256" {if Configuration::get('SC_HASH_TYPE') eq 'sha256'}selected{/if}>sha256</option>
                        <option value="md5" {if Configuration::get('SC_HASH_TYPE') eq 'md5'}selected{/if}>md5</option>
                    </select>
                </div>
            </div>
					
            <div class="form-group">
                <label class="control-label col-lg-3 required"> {l s='Payment Action' mod='nuvei'}</label>
                <div class="col-lg-9">
                    <select name="SC_PAYMENT_ACTION" required="">
						<option value="">{l s='Please, select an option...' mod='nuvei'}</option>
                        <option value="Sale" {if Configuration::get('SC_PAYMENT_ACTION') eq 'Sale'}selected{/if}>{l s='Authorize and Capture' mod='nuvei'}</option>
                        <option value="Auth" {if Configuration::get('SC_PAYMENT_ACTION') eq 'Auth'}selected{/if}>{l s='Authorize' mod='nuvei'}</option>
                    </select>
                </div>
            </div>
					
            <div class="form-group">
                <label class="control-label col-lg-3"> {l s='Save Order after the APM payment' mod='nuvei'}</label>
                <div class="col-lg-9">
                    <select name="NUVEI_SAVE_ORDER_AFTER_APM_PAYMENT">
                        <option value="0" {if Configuration::get('NUVEI_SAVE_ORDER_AFTER_APM_PAYMENT') eq 0}selected{/if}>{l s='NO (Default Prestashop flow, with better security)' mod='nuvei'}</option>
                        <option value="1" {if Configuration::get('NUVEI_SAVE_ORDER_AFTER_APM_PAYMENT') eq 1}selected{/if}>{l s='YES (Less secure, better user experience in case of cancel the Order.)' mod='nuvei'}</option>
                    </select>
                </div>
            </div>
					
            <div class="form-group">
                <label class="control-label col-lg-3"> {l s='Enable UPOs' mod='nuvei'}</label>
                <div class="col-lg-9">
                    <select name="SC_USE_UPOS">
						<option value="">{l s='Please, select an option...' mod='nuvei'}</option>
                        <option value="1" {if Configuration::get('SC_USE_UPOS') eq 1}selected{/if}>{l s='Use UPOs' mod='nuvei'}</option>
                        <option value="0" {if Configuration::get('SC_USE_UPOS') eq 0}selected{/if}>{l s='Do NOT use UPOs' mod='nuvei'}</option>
                    </select>
                </div>
            </div>
					
            <div class="form-group">
                <label class="control-label col-lg-3"> {l s='Preselect CC payment method' mod='nuvei'}</label>
                <div class="col-lg-9">
                    <select name="NUVEI_PRESELECT_CC">
						<option value="">{l s='Please, select an option...' mod='nuvei'}</option>
                        <option value="1" {if Configuration::get('NUVEI_PRESELECT_CC') eq 1}selected{/if}>{l s='Yes' mod='nuvei'}</option>
                        <option value="0" {if Configuration::get('NUVEI_PRESELECT_CC') eq 0}selected{/if}>{l s='No' mod='nuvei'}</option>
                    </select>
                </div>
            </div>
                    
            <div class="form-group">
                <label class="control-label col-lg-3 required"> {l s='Test mode' mod='nuvei'}</label>
                <div class="col-lg-9">
                    <select name="SC_TEST_MODE" required="">
						<option value="">{l s='Please, select an option...' mod='nuvei'}</option>
                        <option value="yes" {if Configuration::get('SC_TEST_MODE') eq 'yes'}selected{/if}>{l s='Yes' mod='nuvei'}</option>
                        <option value="no" {if Configuration::get('SC_TEST_MODE') eq 'no'}selected{/if}>{l s='No' mod='nuvei'}</option>
                    </select>
                </div>
            </div>
                
            <div class="form-group">
                <label class="control-label col-lg-3" for="SC_HTTP_NOTIFY">{l s='Force HTTP notify URLs' mod='nuvei'}</label>
                <div class="col-lg-9">
                    <select name="SC_HTTP_NOTIFY">
						<option value="">{l s='Please, select an option...' mod='nuvei'}</option>
                        <option value="yes" {if Configuration::get('SC_HTTP_NOTIFY') eq 'yes'}selected{/if}>{l s='Yes' mod='nuvei'}</option>
                        <option value="no" {if Configuration::get('SC_HTTP_NOTIFY') eq 'no'}selected{/if}>{l s='No' mod='nuvei'}</option>
                    </select>
                </div>
            </div>
                
            <div class="form-group">
                <label class="control-label col-lg-3" for="SC_CREATE_LOGS">{l s='Save logs' mod='nuvei'}</label>
                <div class="col-lg-9">
                    <select name="SC_CREATE_LOGS">
                        <option value="yes" {if Configuration::get('SC_CREATE_LOGS') eq 'yes'}selected{/if}>{l s='Yes' mod='nuvei'}</option>
                        <option value="no" {if Configuration::get('SC_CREATE_LOGS') eq 'no'}selected{/if}>{l s='No' mod='nuvei'}</option>
                    </select>
                </div>
            </div>
					
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Show APMs names' mod='nuvei'}</label>
                <div class="col-lg-9">
                    <select name="NUVEI_SHOW_APMS_NAMES">
						<option value="">{l s='Please, select an option...' mod='nuvei'}</option>
                        <option value="1" {if Configuration::get('NUVEI_SHOW_APMS_NAMES') eq 1}selected{/if}>{l s='Yes' mod='nuvei'}</option>
                        <option value="0" {if Configuration::get('NUVEI_SHOW_APMS_NAMES') eq 0}selected{/if}>{l s='No' mod='nuvei'}</option>
                    </select>
                </div>
            </div>
					
			<div class="form-group">
                <label class="control-label col-lg-3">{l s='The Payment method text on the checkout' mod='nuvei'}</label>
                <div class="col-lg-9">
                    <input type="text" name="NUVEI_CHECKOUT_MSG" value="{Configuration::get('NUVEI_CHECKOUT_MSG')}" />
                </div>
            </div>
					
			<div class="form-group">
                <label class="control-label col-lg-3">{l s='Show help message over each Payment method' mod='nuvei'}</label>
                <div class="col-lg-9">
                    <input type="text" name="NUVEI_APMS_NOTE" value="{Configuration::get('NUVEI_APMS_NOTE')}" />
                </div>
            </div>
					
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Payment methods style' mod='nuvei'}</label>
                <div class="col-lg-9">
                    <textarea name="NUVEI_PMS_STYLE" rows="10">{Configuration::get('NUVEI_PMS_STYLE')}</textarea>
					<span class="help-block">{l s='Override predefined style of the Nuvei elements.' mod='nuvei'}</span>
                </div>
            </div>
				
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Use Additional Checkout Step' mod='nuvei'}</label>
                <div class="col-lg-9">
                    <select name="NUVEI_ADD_CHECKOUT_STEP">
                        <option value="0" {if Configuration::get('NUVEI_ADD_CHECKOUT_STEP') eq 0}selected{/if}>{l s='No' mod='nuvei'}</option>
                        <option value="1" {if Configuration::get('NUVEI_ADD_CHECKOUT_STEP') eq 1}selected{/if}>{l s='Yes' mod='nuvei'}</option>
                    </select>
					
					<span class="help-block">{l s='Please enable /Yes/ only when using a Once Step Checkout module!' mod='nuvei'}</span>
                </div>
            </div>
				
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Notification (DMN) URL'}</label>
                <div class="col-lg-9">
                    <input type="text" name="NUVEI_DMN_URL" readonly="" value="{if Configuration::get('NUVEI_DMN_URL') neq ''}{Configuration::get('NUVEI_DMN_URL')}{/if}" placeholder="{$defaultDmnUrl}" style="display: inline-block; width: 80%;" />
					
					&nbsp;<label><input type="checkbox" id="sc_edit_dmn_url" />&nbsp;Enable edit</label>
					<span class="help-block">{l s='Please DO NOT change this URL unless you really must. Overriding this value may break the normal behavior of the plugin!' mod='nuvei'}</span>
					
					<script>
						jQuery(function(){
							jQuery('#sc_edit_dmn_url').on('click', function(){
								jQuery('input[name="NUVEI_DMN_URL"]').prop('readonly', jQuery(this).is(':checked') ? false : true);
							});
						});
					</script>
                </div>
            </div>
        </div><!-- /.form-wrapper -->
        
        <div class="panel-footer">
            <button type="submit" value="1" name="submitUpdate" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Save' mod='nuvei'}
            </button>
        </div>
    </div>
</form>
