{extends file='page.tpl'}
{block name='page_content'}
	<div class="cc_load_spinner" style="margin: 0 auto;">
		<img class="sc_rotate_img" src="/modules/safecharge/views/img/loading.png" alt="loading..." />
	</div>
	
	{include file="module:safecharge/views/templates/front/apms.tpl"}
	
	<style>
		#scForm, .alert {
			max-width: 600px;
			margin: 0 auto;
		}
		
		#scForm .payment-option label span:last-child {
			float: none;
		}
	</style>
{/block}