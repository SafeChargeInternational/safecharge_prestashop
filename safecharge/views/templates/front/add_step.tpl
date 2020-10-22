{extends file='page.tpl'}
{block name='page_content'}
	<style>
		.cc_load_spinner img {
			-webkit-animation: sc_spin 1s infinite linear;
			animation: sc_spin 1s infinite linear;
		}

		@-webkit-keyframes sc_spin {
			0% {
				-webkit-transform: rotate(0deg);
				transform: rotate(0deg);
			}

			100% {
				-webkit-transform: rotate(359deg);
				transform: rotate(359deg);
			}
		}

		@keyframes sc_spin {
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
	
	<div class="cc_load_spinner" style="margin: 0 auto; text-align: center;">
		<img class="sc_rotate_img" src="/modules/safecharge/views/img/loading.png" alt="loading..." />
	</div>
	
	{if !empty($paymentMethods)}
		{include file="module:safecharge/views/templates/front/apms.tpl"}
	{else}
		<div id="sc_pm_error" class="alert alert-warning">
			<span class="sc_error_msg">{l s='ERROR - there are no any Payment Methods. Please, go back to the cart and try again!' mod='safecharge'}</span>
			<span class="close" onclick="$('#sc_pm_error').hide();">&times;</span>
		</div>
			
		<script>
			document.addEventListener('DOMContentLoaded', function(event) {
				//$('.cc_load_spinner').addClass('sc_hide');
			});
		</script>
	{/if}
	
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