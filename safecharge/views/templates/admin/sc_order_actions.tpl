<style type="text/css">
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

{if
    $scData.resp_transaction_type eq "Auth"
    and $scData.plugin_tr_type eq "auth"
    and $scData.order_state eq $state_pending
}
    <button type="button" id="sc_settle_btn" class="btn btn-default" onclick="scOrderAction('settle', {$orderId})" title="{l s='You will be redirected to Orders list.' d='Module.safecharge'}">
        <i class="icon-thumbs-up"></i>
        <i class="icon-repeat fast-right-spinner hidden"></i>
        {l s='Settle' d='Module.safecharge'}
    </button>
{/if}

{if
    in_array($scData.order_state, array($state_pending, $state_completed))
    and in_array($scData.payment_method, array('cc_card', 'dc_card'))
    and $isRefunded eq 0
}
<button type="button" id="sc_void_btn" class="btn btn-default" onclick="scOrderAction('void', {$orderId})">
    <i class="icon-retweet"></i>
    <i class="icon-repeat fast-right-spinner hidden"></i>
    {l s='Void' d='Module.safecharge'}
</button>
{/if}

<script type="text/javascript">
    function scOrderAction(action, orderId) {
        var question = '';
        
        switch(action) {
            case 'settle':
                question = '{l s='Are you sure you want to Settle this order?' d='Module.safecharge'}';
                break;
                
            case 'void':
                question = '{l s='Are you sure you want to Cancel this order?' d='Module.safecharge'}';
                break;
            
            default:
                break;
        }
        
        if(confirm(question)) {
            disableScBtns(action);
            
            var ajax = new XMLHttpRequest();
            var params = 'scAction=' + action + '&scOrder=' + orderId;
            ajax.open("POST", "{$ajaxUrl}", true);
            ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            
            ajax.onreadystatechange = function(){
                if (ajax.readyState == 4 && ajax.status == 200) {
                    var resp = JSON.parse(this.responseText);
                    
                    if(resp.status == 1) {
                    //    alert('You will be redirected to the Orders list.');
                        window.location.href = $('#subtab-AdminOrders a.link').attr('href');
                    }
                    else {
                        try {
                            if(typeof resp.msg != 'undefined') {
                                alert(resp.msg);
                                enableScBtns(action);
                            }
                            else if(typeof resp.data.gwErrorReason != 'undefined') {
                                alert(resp.data.gwErrorReason);
                                enableScBtns(action);
                            }
                            else if(typeof resp.data.reason != 'undefined') {
                                alert(resp.data.reason);
                                enableScBtns(action);
                            }
                        }
                        catch (exception) {
                            alert("Error during AJAX call");
                            enableScBtns(action);
                        }
                    }
                }
            }
            
            //If an error occur during the ajax call.
            if (ajax.readyState == 4 && ajax.status == 404) {
                alert("Error during AJAX call");
                enableScBtns(action);
            }
            
            ajax.send(params);
        }
    }
    
    function disableScBtns(action) {
        $('#sc_'+ action +'_btn .icon-repeat').removeClass('hidden');
        $('#sc_'+ action +'_btn').addClass('disabled');
    }
    
    function enableScBtns(action) {
        $('#sc_'+ action +'_btn .icon-repeat').addClass('hidden');
        $('#sc_'+ action +'_btn').removeClass('disabled');
    }
    
    // remove PS refund button
    {if
        $scData.order_state neq $state_completed
        or !in_array($scData.payment_method, array('cc_card', 'dc_card', 'apmgw_expresscheckout'))
    }
        $('#desc-order-partial_refund').remove();
    {/if}
</script>