{extends file='page.tpl'}
{block name='page_content'}
	<div class="alert alert-danger" style="font-size:20px;">
		<div>{l s='Your Order is FAILED, DECLINED or was CANCELD.' mod='safecharge'}</div>
		{if $customer['is_logged']}
			<br/>
			<div>{l s='Please, go to your Orders Hisory page, to check your Order!' mod='safecharge'}</div>
		{/if}
	</div>
{/block}