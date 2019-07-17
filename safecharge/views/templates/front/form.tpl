{extends file='page.tpl'}
{block name='page_content'}
    <img src="/img/loader.gif" alt="Loading..." width="30" />&nbsp;&nbsp;
    <span style="font-size:20px;">{l s='Processing Order, please wait!' mod='Modules.safecharge'}</span>
    
    {if $scApi eq "cashier"}
        <form action="{$action_url}" method="post" id="scForm">
            {foreach $order_params as $name => $value}
                <input type="hidden" name="{$name}" value="{$value}" />
            {/foreach}

            <noscript>
                <button class="btn btn-submit" type="submit" style="float: right;">{l s='Submit order' mod='Modules.safecharge'}</button>
            </noscript>
        </form>
    {elseif $scApi eq "rest"}
        {if isset($acsUrl)}
            <form action="{$finalUrl}" method="post" id="scForm">
                <input type="hidden" name="{$PaReq}" value="{$PaReq}" />
                <input type="hidden" name="{$TermUrl}" value="{$TermUrl}" />

                <noscript>
                    <button class="btn btn-submit" type="submit" style="float: right;">{l s='Submit order' mod='Modules.safecharge'}</button>
                </noscript>
            </form>
        {elseif isset($redirectURL)}
            <form action="{$finalUrl}" method="post" id="scForm">
                <noscript>
                    <button class="btn btn-submit" type="submit" style="float: right;">{l s='Continue' mod='Modules.safecharge'}</button>
                </noscript>
            </form>
        {else}
            <form action="{$finalUrl}" method="post" id="scForm">
                <noscript>
                    <button class="btn btn-submit" type="submit" style="float: right;">{l s='Continue' mod='Modules.safecharge'}</button>
                </noscript>
            </form>
        {/if}
    {/if}
    
    <script type="text/javascript">
        window.onload = function(){
            $('#scForm').submit();
        };
    </script>
{/block}