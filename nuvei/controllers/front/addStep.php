<?php

/**
 * @author Nuvei
 * @year 2020
 */

if (!session_id()) {
    session_start();
}

require_once _PS_MODULE_DIR_ . 'safecharge' . DIRECTORY_SEPARATOR . 'sc_config.php';
require_once _PS_MODULE_DIR_ . 'safecharge' . DIRECTORY_SEPARATOR . 'SC_CLASS.php';

class SafeChargeAddStepModuleFrontController extends ModuleFrontController
{
	public function initContent()
    {
        parent::initContent();
		
		$this->module->createLog('SafeChargeAddStepModuleFrontController initContent()');
		
		$error_url	= $this->context->link->getPageLink('order');
		$cart		= $this->context->cart;
		
		// check parameters
		if($cart->secure_key != Tools::getValue('key')) {
			$this->module->createLog('SafeChargeAddStepModuleFrontController Error - secure key not mutch!');
			
			Tools::redirect($error_url);
		}
		
		if($cart->id != Tools::getValue('cartId')) {
			$this->module->createLog('SafeChargeAddStepModuleFrontController Error - Cart ID not mutch!');
			
			Tools::redirect($error_url);
		}
		
		if(number_format($cart->getOrderTotal(), 2, '.', '') != Tools::getValue('amount')) {
			$this->module->createLog('SafeChargeAddStepModuleFrontController Error - Order amount not mutch!');
			
			Tools::redirect($error_url);
		}
		// check parameters END
		
//		$this->module->prepareOrderData(false, true);
		$this->module->getPaymentMethods();
		$this->context->smarty->assign('scAddStep', true);
		
        $this->setTemplate('module:safecharge/views/templates/front/add_step.tpl');
    }
}