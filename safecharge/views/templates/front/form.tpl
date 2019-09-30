{extends file='page.tpl'}
{block name='page_content'}
    <img src="/img/loader.gif" alt="Loading..." width="30" />&nbsp;&nbsp;
    <span style="font-size:20px;">{l s='Processing Order, please wait!' mod='Modules.safecharge'}</span>
    
    {if isset($redirectURL)}
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
    
    <script type="text/javascript">
        window.onload = function(){
            $('#scForm').submit();
        };
    </script>
{/block}