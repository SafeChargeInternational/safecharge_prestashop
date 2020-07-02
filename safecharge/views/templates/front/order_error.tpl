{extends file='page.tpl'}
{block name='page_content'}
	<div class="alert alert-danger" style="font-size:20px;">
		<p>{l s='Your Order is FAILED, DECLINED or was CANCELD.' mod='safecharge'}</p>
		<p><strong>{l s='If you are a registred user' mod='safecharge'}</strong> {l s='and want to post the order again - click to your user name at the top of the page, select ORDER HISTORY AND DETAILS, find your failed Order and click on Reorder link.' mod='safecharge'}</p>
	</div>
{/block}