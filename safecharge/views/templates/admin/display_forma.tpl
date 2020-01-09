<br/>
<div class="alert alert-info">
    <img src="/modules/safecharge/logo.png" style="float:left; margin-right:15px;" height="60" />
    <p><a target="_blank" href="http://www.safecharge.com/"><strong>Safecharge</strong></a></p>
    <p>{l s='Safecharge provides secure and reliable turnkey solutions for small to medium sized e-commerce businesses. Powered by SafeCharge Technologies and backed by more than a decade of experience in the e-commerce industry, with expert international staff, Safecharge has the skills, tools, technology, and expertise to accept software vendors and digital service providers and help them succeed online with confidence in a secure and reliable environment. It also helps them promote their software and enjoy increased sales volumes.' mod='Modules.safecharge'}</p>
</div>

<form class="defaultForm form-horizontal" action="{$smarty.server['REQUEST_URI']}" method="post" enctype="multipart/form-data">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Settings' mod='sc'}
        </div>
								
        <div class="form-wrapper">
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Default title' mod='Modules.safecharge'}</label>
                <div class="col-lg-9">
                    <input type="text" name="SC_FRONTEND_NAME" value="{if Configuration::get('SC_FRONTEND_NAME')}{Configuration::get('SC_FRONTEND_NAME')}{else}{l s='Secure Payment with SafeCharge' mod='Modules.safecharge'}{/if}" />
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3 required"> {l s='Merchant Id' mod='Modules.safecharge'}</label>
                <div class="col-lg-9">
                    <input type="text" name="SC_MERCHANT_ID" value="{Configuration::get('SC_MERCHANT_ID')}" required="" />
                </div>
            </div>
                
            <div class="form-group">
                <label class="control-label col-lg-3 required"> {l s='Merchant Site Id' mod='Modules.safecharge'}</label>
                <div class="col-lg-9">
                    <input type="text" name="SC_MERCHANT_SITE_ID" value="{Configuration::get('SC_MERCHANT_SITE_ID')}" required="" />
                </div>
            </div>
                
            <div class="form-group">
                <label class="control-label col-lg-3 required"> {l s='Merchant Secret Key' mod='Modules.safecharge'}</label>
                <div class="col-lg-9">
                    <input type="text" name="SC_SECRET_KEY" value="{Configuration::get('SC_SECRET_KEY')}" required="" />
                </div>
            </div>
                
            <div class="form-group">
                <label class="control-label col-lg-3"> {l s='Hash type' mod='Modules.safecharge'}</label>
                <div class="col-lg-9">
                    <select name="SC_HASH_TYPE">
                        <option value="sha256" {if Configuration::get('SC_HASH_TYPE') eq 'sha256'}selected{/if}>sha256</option>
                        <option value="md5" {if Configuration::get('SC_HASH_TYPE') eq 'md5'}selected{/if}>md5</option>
                    </select>
                </div>
            </div>
					
            <div class="form-group">
                <label class="control-label col-lg-3"> {l s='Payment Action' mod='Modules.safecharge'}</label>
                <div class="col-lg-9">
                    <select name="SC_PAYMENT_ACTION">
                        <option value="Sale" {if Configuration::get('SC_PAYMENT_ACTION') eq 'Sale'}selected{/if}>Authorize and Capture</option>
                        <option value="Auth" {if Configuration::get('SC_PAYMENT_ACTION') eq 'Auth'}selected{/if}>Authorize</option>
                    </select>
                </div>
            </div>
                    
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Test mode' mod='Modules.safecharge'}</label>
                <div class="col-lg-9">
                    <select name="SC_TEST_MODE">
                        <option value="yes" {if Configuration::get('SC_TEST_MODE') eq 'yes'}selected{/if}>{l s='Yes' mod='Modules.safecharge'}</option>
                        <option value="no" {if Configuration::get('SC_TEST_MODE') eq 'no'}selected{/if}>{l s='No' mod='Modules.safecharge'}</option>
                    </select>
                </div>
            </div>
                
            <div class="form-group">
                <label class="control-label col-lg-3" for="SC_HTTP_NOTIFY">{l s='Force HTTP notify URLs' mod='Modules.safecharge'}</label>
                <div class="col-lg-9">
                    <select name="SC_HTTP_NOTIFY">
                        <option value="yes" {if Configuration::get('SC_HTTP_NOTIFY') eq 'yes'}selected{/if}>{l s='Yes' mod='Modules.safecharge'}</option>
                        <option value="no" {if Configuration::get('SC_HTTP_NOTIFY') eq 'no'}selected{/if}>{l s='No' mod='Modules.safecharge'}</option>
                    </select>
                </div>
            </div>
                
            <div class="form-group">
                <label class="control-label col-lg-3" for="SC_CREATE_LOGS">{l s='Save logs' mod='Modules.safecharge'}</label>
                <div class="col-lg-9">
                    <select name="SC_CREATE_LOGS">
                        <option value="yes" {if Configuration::get('SC_CREATE_LOGS') eq 'yes'}selected{/if}>{l s='Yes' mod='Modules.safecharge'}</option>
                        <option value="no" {if Configuration::get('SC_CREATE_LOGS') eq 'no'}selected{/if}>{l s='No' mod='Modules.safecharge'}</option>
                    </select>
                </div>
            </div>
        </div><!-- /.form-wrapper -->
        
        <div class="panel-footer">
            <button type="submit" value="1" name="submitUpdate" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> Save
            </button>
        </div>
    </div>
</form>
