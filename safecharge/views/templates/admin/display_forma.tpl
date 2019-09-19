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
                <label class="control-label col-lg-3"> {l s='Payment solution' mod='Modules.safecharge'}</label>
                <div class="col-lg-9">
                    <select name="SC_PAYMENT_METHOD">
                        <option value="rest" {if Configuration::get('SC_PAYMENT_METHOD') eq 'rest'}selected{/if}>{l s='SafeCharge API' mod='Modules.safecharge'}</option>
                        <option value="cashier" {if Configuration::get('SC_PAYMENT_METHOD') eq 'cashier'}selected{/if}>{l s='Hosted payment page' mod='Modules.safecharge'}</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3"> {l s='Payment action' mod='Modules.safecharge'}</label>
                <div class="col-lg-9">
                    <select name="SC_PAYMENT_ACTION">
                        <option value="auth" {if Configuration::get('SC_PAYMENT_ACTION') eq 'auth'}selected{/if}>{l s='Authorize' mod='Modules.safecharge'}</option>
                        <option value="sale" {if Configuration::get('SC_PAYMENT_ACTION') eq 'sale'}selected{/if}>{l s='Authorize & Capture' mod='Modules.safecharge'}</option>
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
                    
            <div class="form-group">
                <label class="control-label col-lg-3" for="SC_CREATE_LOGS">{l s='Remove oldest logs' mod='Modules.safecharge'}</label>
                <div class="col-lg-9">
                    <button type="button" class="btn btn-primary" onclick="scRemoveLogs()">
                        <i class="icon-trash"></i> {l s='Remove' mod='Modules.safecharge'}
                    </button>
                </div>
            </div>
                
            {*<div class="form-group">
                <label class="control-label col-lg-3" for="SC_SAVE_ORDER_BEFORE_REDIRECT">{l s='Save order before redirect to payment page?' mod='sc'}</label>
                <div class="col-lg-9">
                    <input type="checkbox" name="SC_SAVE_ORDER_BEFORE_REDIRECT" id="SC_SAVE_ORDER_BEFORE_REDIRECT" {if Configuration::get("SC_SAVE_ORDER_BEFORE_REDIRECT") eq 1}value="1" checked=""{else}value="0"{/if} class="form-control" style="width: 1px;" />
                </div>
            </div>*}
        </div><!-- /.form-wrapper -->
        
        <div class="panel-footer">
            <button type="submit" value="1" name="submitUpdate" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> Save
            </button>
        </div>
    </div>
</form>
        
<script type="text/javascript">
    function scRemoveLogs() {
        if(confirm("{l s='Are you sure you want to delete the logs?' mod='Modules.safecharge'}")) {
            var ajax = new XMLHttpRequest();
            var params = 'scAction=deleteLogs';
            ajax.open("POST", "{$ajaxUrl}", true);
            ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            
            ajax.onreadystatechange = function(){
                if (ajax.readyState == 4 && ajax.status == 200) {
                    var resp = JSON.parse(this.responseText);
                    
                    if(resp.status == 1) {
                        alert("{l s='Done!' mod='Modules.safecharge'}");
                    }
                    else {
                        try {
                            if(typeof resp.msg != 'undefined') {
                                alert(resp.msg);
                            }
                            else if(typeof resp.data.gwErrorReason != 'undefined') {
                                alert(resp.data.gwErrorReason);
                            }
                            else if(typeof resp.data.reason != 'undefined') {
                                alert(resp.data.reason);
                            }
                        }
                        catch (exception) {
                            alert("Error during AJAX call");
                        }
                    }
                }
            }
            
            //If an error occur during the ajax call.
            if (ajax.readyState == 4 && ajax.status == 404) {
                alert("Error during AJAX call");
            }
            
            ajax.send(params);
        }
    }
</script>
