<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

if(!isset($_SESSION)) {
	session_start();
}

require_once _PS_MODULE_DIR_ . 'nuvei' . DIRECTORY_SEPARATOR . 'sc_config.php';
require_once _PS_MODULE_DIR_ . 'nuvei' . DIRECTORY_SEPARATOR . 'SC_CLASS.php';
require_once _PS_MODULE_DIR_ . 'nuvei' . DIRECTORY_SEPARATOR . 'sc_versions_resolver.php';

class Nuvei extends PaymentModule
{
    private $_html = '';
    
    public function __construct()
    {
        $this->name						= 'nuvei';
        $this->tab						= SafeChargeVersionResolver::set_tab();
        $this->version					= '2.2';
        $this->author					= 'Nuvei';
        $this->need_instance			= 1;
        $this->ps_versions_compliancy	= array('min' => '1.7', 'max' => '1.7.6.4'); // curent version - _PS_VERSION_
        $this->bootstrap				= true;
        $this->controllers				= array('payment', 'validation');
        $this->is_eu_compatible			= 1;
        
        $this->currencies				= true; // ?
        $this->currencies_mode			= 'checkbox'; // for the Payment > Preferences menu

        parent::__construct();

        $this->page				= basename(__FILE__, '.php'); // ?
        $this->displayName		= 'Nuvei Payments'; // we see this in Prestashop Modules list
        $this->description		= $this->l('Accepts payments by Nuvei.');
        $this->confirmUninstall	= $this->l('Are you sure you want to delete your details?');
        
        if (!isset($this->owner) || !isset($this->details) || !isset($this->address)) {
            $this->warning = $this->l('Merchant account details must be configured before using this module.');
        }
        
        global $smarty;
        
        $smarty->assign('ajaxUrl', $this->context->link->getAdminLink("AdminSafeChargeAjax"));
//        $_SESSION['sc_create_logs'] = Configuration::get('SC_CREATE_LOGS');
    }
	
    public function install()
    {
        if (
            !parent::install()
            || !Configuration::updateValue('SC_MERCHANT_SITE_ID', '')
            || !Configuration::updateValue('SC_MERCHANT_ID', '')
            || !Configuration::updateValue('SC_SECRET_KEY', '')
            || !$this->registerHook('payment')
            || !$this->registerHook('paymentOptions')
            || !$this->registerHook('displayBackOfficeOrderActions')
            || !$this->registerHook('displayAdminOrderLeft')
            || !$this->registerHook('actionOrderSlipAdd')
            || !$this->registerHook('actionModuleInstallBefore')
            || !$this->installTab('AdminCatalog', 'AdminSafeChargeAjax', 'SafeChargeAjax')
            || !$this->addOrderState()
        ) {
            return false;
        }
        
        # safecharge_order_data table
		$db = Db::getInstance();
		
        $sql =
            "CREATE TABLE IF NOT EXISTS `safecharge_order_data` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `order_id` int(11) unsigned NOT NULL,
                `auth_code` varchar(20) NOT NULL,
                `related_transaction_id` varchar(20) NOT NULL,
                `resp_transaction_type` varchar(20) NOT NULL,
                `payment_method` varchar(50) NOT NULL,
				`error_msg` text,
                
                PRIMARY KEY (`id`),
                KEY `order_id` (`order_id`),
                UNIQUE KEY `un_order_id` (`order_id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        
        $res = $db->execute($sql);
		
		if(!$res) {
			$this->createLog($res, 'On Install create SC table response');
			$this->createLog($db->getMsgError(), 'getMsgError');
			$this->createLog($db->getNumberError(), 'getNumberError');
		}
		
		// for the old versions try to add the session_token column only
//		$sql =
//			"SELECT COUNT(COLUMN_NAME)"
//			. "FROM INFORMATION_SCHEMA.columns "
//			. "WHERE TABLE_NAME = 'safecharge_order_data' "
//			. "AND COLUMN_NAME = 'session_token';";
//		
//		$result = intval(current($db->getRow($sql)));
//		
//		if($result == 0) {
//			$sql = "ALTER TABLE `safecharge_order_data` ADD `session_token` VARCHAR(36) NOT NULL DEFAULT '';";
//			$db->execute($sql);
//		}
		# safecharge_order_data table END
        
        // create tab for the admin module
        $invisible_tab = new Tab();
        
        $invisible_tab->active      = 1;
        $invisible_tab->class_name  = 'AdminSafeChargeAjax';
        $invisible_tab->name        = array();
        
        foreach (Language::getLanguages(true) as $lang) {
            $invisible_tab->name[$lang['id_lang']] = 'AdminSafeChargeAjax';
        }
		
		$this->createLog('Finish install');
        
        return true;
    }
    
    public function installTab($parent, $class_name, $name)
    {
        // Create new admin tab
        $tab = new Tab();
//        $tab->id_parent = (int)Tab::getIdFromClassName($parent); // will show link in the Catalog menu on left
        $tab->id_parent = -1;
        $tab->name = array();
        
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $name;
        }
        
        $tab->class_name	= $class_name;
        $tab->module		= $this->name;
        $tab->active		= 1;
		
        return $tab->add();
    }
    
    public function uninstall()
    {
        if (
            !Configuration::deleteByName('SC_MERCHANT_SITE_ID') || 
            !Configuration::deleteByName('SC_MERCHANT_ID') || 
            !Configuration::deleteByName('SC_SECRET_KEY') || 
			!Configuration::deleteByName('SC_OS_AWAITING_PAIMENT') ||
            !parent::uninstall()
        ) {
            return false;
        }
        
        return true;
    }
    
    public function getContent()
    {
        $this->_html .= '<h2>'.$this->displayName.'</h2>';
        
        if (Tools::isSubmit('submitUpdate')) {
            Configuration::updateValue('SC_FRONTEND_NAME',			Tools::getValue('SC_FRONTEND_NAME'));
            Configuration::updateValue('SC_MERCHANT_ID',			Tools::getValue('SC_MERCHANT_ID'));
            Configuration::updateValue('SC_MERCHANT_SITE_ID',		Tools::getValue('SC_MERCHANT_SITE_ID'));
            Configuration::updateValue('SC_SECRET_KEY',				Tools::getValue('SC_SECRET_KEY'));
            Configuration::updateValue('SC_HASH_TYPE',				Tools::getValue('SC_HASH_TYPE'));
            Configuration::updateValue('SC_PAYMENT_ACTION',			Tools::getValue('SC_PAYMENT_ACTION'));
            Configuration::updateValue('SC_USE_UPOS',				Tools::getValue('SC_USE_UPOS'));
            Configuration::updateValue('SC_TEST_MODE',				Tools::getValue('SC_TEST_MODE'));
            Configuration::updateValue('SC_HTTP_NOTIFY',			Tools::getValue('SC_HTTP_NOTIFY'));
            Configuration::updateValue('SC_CREATE_LOGS',			Tools::getValue('SC_CREATE_LOGS'));
            Configuration::updateValue('NUVEI_PRESELECT_CC',		Tools::getValue('NUVEI_PRESELECT_CC'));
            Configuration::updateValue('NUVEI_SHOW_APMS_NAMES',		Tools::getValue('NUVEI_SHOW_APMS_NAMES'));
            Configuration::updateValue('NUVEI_APMS_NOTE',			Tools::getValue('NUVEI_APMS_NOTE'));
            Configuration::updateValue('NUVEI_PMS_STYLE',			Tools::getValue('NUVEI_PMS_STYLE'));
            Configuration::updateValue('NUVEI_ADD_CHECKOUT_STEP',	Tools::getValue('NUVEI_ADD_CHECKOUT_STEP'));
            Configuration::updateValue('NUVEI_DMN_URL',				Tools::getValue('NUVEI_DMN_URL'));
            Configuration::updateValue('NUVEI_CHECKOUT_MSG',		Tools::getValue('NUVEI_CHECKOUT_MSG'));
            Configuration::updateValue('NUVEI_PRESELECT_PAYMENT',	Tools::getValue('NUVEI_PRESELECT_PAYMENT'));
            
			Configuration::updateValue(
				'NUVEI_SAVE_ORDER_AFTER_APM_PAYMENT',
				Tools::getValue('NUVEI_SAVE_ORDER_AFTER_APM_PAYMENT')
			);
        }

        $this->_postValidation();
        
        if (isset($this->_postErrors) && sizeof($this->_postErrors)) {
            foreach ($this->_postErrors as $err){
                $this->_html .= '<div class="alert error">'. $err .'</div>';
            }
        }
        
        $this->smarty->assign('img_path', '/modules/nuvei/views/img/');
        
		$this->smarty->assign(
			'defaultDmnUrl',
			$this->getNotifyUrl()
		);

        return $this->display(__FILE__, 'views/templates/admin/display_forma.tpl');
    }
	
	public function getNotifyUrl() {
		$url = trim(Configuration::get('NUVEI_DMN_URL'));
		
		if(empty($url)) {
			$url = $this->context->link
				->getModuleLink('nuvei', 'payment', array(
					'prestaShopAction'  => 'getDMN',
//					'sc_create_logs'    => $_SESSION['sc_create_logs'],
					'sc_stop_dmn'       => SC_STOP_DMN,
				));
		}
		
		if(
			Configuration::get('SC_HTTP_NOTIFY') == 'yes'
			&& false !== strpos($url, 'https://')
		) {
			$url = str_replace('https://', 'http://', $url);
		}
		
		return $url;
	}
	
    public function hookPaymentOptions($params)
    {
		if($this->isPayment() !== true){
            $this->createLog('hookPaymentOptions isPayment not true.');
            return false;
        }
		
		if(empty($params['cart']->delivery_option)) {
			return array();
		}
		
		$this->createLog('hookPaymentOptions');
		$this->getPaymentMethods();
		
		global $smarty;
		
        $newOption = new PaymentOption();
		
		$option_text = Configuration::get('NUVEI_CHECKOUT_MSG');
		if(!$option_text || empty($option_text)) {
			$option_text = $this->trans('Pay by Nuvei', array(), 'Modules.nuvei');
		}
		
        $newOption
			->setModuleName($this->name)
            ->setCallToActionText($option_text)
			->setLogo(_MODULE_DIR_ . 'nuvei/views/img/nuvei-v2.gif');
            
		if(Configuration::get('NUVEI_ADD_CHECKOUT_STEP') == 0) {
            $newOption
				->setAction($this->context->link->getModuleLink($this->name, 'payment'))
				->setAdditionalInformation($smarty->fetch('module:nuvei/views/templates/front/apms.tpl'));
		}
		else {
			$newOption->setAction($this->context->link->getModuleLink($this->name, 'addStep', array(
				'cartId' => $params['cart']->id,
				'key' => $params['cart']->secure_key,
				'amount' => number_format($params['cart']->getOrderTotal(), 2, '.', ''),
			)));
		}
        
        return [$newOption];
    }
	
    /**
     * Function hookDisplayBackOfficeOrderActions
     * Hook to display SC specific order actions
     * 
     * @param array $params
     * @return template
	 * 
	 * @deprecated since 1.7.7 -> actionGetAdminOrderButtons
     */
    public function hookDisplayBackOfficeOrderActions($params)
    {
        if($this->isPayment() !== true){
            $this->createLog('hookDisplayBackOfficeOrderActions isPayment not true.');
            return false;
        }
		
		if(empty($order_id = Tools::getValue('id_order'))) {
			return false;
		}
        
        $order_id = intval($order_id);
        $order_data = new Order($order_id);
		
		$payment = strtolower($order_data->payment);
		
		// not SC order
		if(
			strpos($payment, 'safecharge') === false
			&& strpos($payment, 'nuvei') === false
			&& strpos($payment, 'nuvei payments') === false
		) {
			return false;
		}
		
		global $smarty;
		
        $smarty->assign('orderId', $order_id);
        
        $sc_data = Db::getInstance()->getRow('SELECT * FROM safecharge_order_data WHERE order_id = ' . $order_id);
        
        if(empty($sc_data)) {
            $this->createLog('Missing safecharge_order_data for order ' . $order_id);
			$smarty->assign('scDataError', 'Error - The Payment miss specific Nuvei data!');
        }
        
        $sc_data['order_state'] = $order_data->current_state;
		
//		echo '<pre>'.print_r($sc_data, true).'</pre>'; 
		
		$enable_void = false;
		if (!empty($sc_data['payment_method']) && 'cc_card' == $sc_data['payment_method']) {
			if(
				Configuration::get('PS_OS_PAYMENT') == $sc_data['order_state']
				&& in_array($sc_data['resp_transaction_type'], array('Sale', 'Settle'))
			) {
				$enable_void = true;
			}
			elseif(
				Configuration::get('SC_OS_AWAITING_PAIMENT') == $sc_data['order_state']
				&& 'Auth' == $sc_data['resp_transaction_type']
			) {
				$enable_void = true;
			}
			elseif(
				Configuration::get('PS_OS_ERROR') == $sc_data['order_state']
				&& in_array($sc_data['resp_transaction_type'], array('Sale', 'Settle'))
			) {
				$enable_void = true;
			}
		}
		
        $smarty->assign('scData', $sc_data);
        $smarty->assign('enableVoid', $enable_void);
        $smarty->assign('state_completed', Configuration::get('PS_OS_PAYMENT'));
        $smarty->assign('state_refunded', Configuration::get('PS_OS_REFUND'));
        $smarty->assign('state_sc_await_paiment', Configuration::get('SC_OS_AWAITING_PAIMENT'));
		$smarty->assign('ordersListURL', Context::getContext()->link->getAdminLink('AdminOrders', true));
		
        // check for refunds
        $rows = Db::getInstance()->getRow('SELECT id_order_slip FROM '. _DB_PREFIX_
            .'order_slip WHERE id_order = ' . $order_id . ' AND amount > 0');
		
//		$rows2 = Db::getInstance()->getRow('SELECT * FROM '. _DB_PREFIX_
//            .'order_slip WHERE id_order = ' . $order_id . ' AND amount > 0');
//		echo '<pre>'.print_r($rows2, true).'</pre>'; 
		
        $smarty->assign('isRefunded', $rows ? 1 : 0);
        
        return $this->display(__FILE__, 'views/templates/admin/sc_order_actions.tpl');
    }
    
    /**
     * Function hookDisplayAdminOrderLeft
     * At the bottom of the left column we will print Notes and other
     * Nuvei information.
     * 
     * @return template
	 * 
	 * @deprecated (removed) in 1.7.7 -> displayAdminOrderMain
     */
    public function hookDisplayAdminOrderLeft()
    {
        if($this->isPayment() !== true){
            $this->createLog('hookDisplayAdminOrderLeft isPayment not true.');
            return false;
        }
		
		if(empty($_GET['id_order'])) {
			return false;
		}
		
		$order_data	= new Order(intval($_GET['id_order']));
		$payment	= strtolower($order_data->payment);

		// not SC order
		if(
			!empty($order_data->payment)
			&& strpos($payment, 'safecharge') === false
			&& strpos($payment, 'nuvei') === false
			&& strpos($payment, 'nuvei payment') === false
		) {
			return false;
		}
        
        global $smarty;
        
//        $messages = MessageCore::getMessagesByOrderId($_GET['id_order'], true);
        $messages = MessageCore::getMessagesByOrderId(Tools::getValue('id_order'), true);
        $smarty->assign('messages', $messages);
        
        return $this->display(__FILE__, 'views/templates/admin/sc_order_notes.tpl');
    }

    /**
     * Function hookActionOrderSlipAdd
     * This hook is executed after the Slip record is created.
     * We use it to request Refund to SC Gateway
     * 
     * @param array $params - order params
     */
    public function hookActionOrderSlipAdd($params)
    {
		$payment_name = strtolower($params['order']->payment);
		
		if(
			empty($params['order']->payment)
			|| empty($_REQUEST['id_order'])
			|| (
				strpos($payment_name, 'safecharge') === false
				&& strpos($payment_name, 'nuvei') === false
				&& strpos($payment_name, 'nuvei payment') === false
			) // not SC order
		) {
			$this->createLog('hookActionOrderSlipAdd first check fail.');
			return false;
		}
		
        if(
            $this->isPayment() !== true
            || !isset($_REQUEST['partialRefund'])
            || !is_array(Tools::getValue('partialRefundProduct'))
        ) {
            $this->createLog(
				[
					'isPayment' => $this->isPayment(),
					'partialRefund' => Tools::getValue('partialRefund'),
					'partialRefundProduct' => Tools::getValue('partialRefundProduct'),
				], 
				'hookActionOrderSlipAdd isPayment not true or missing request parameters.'
			);
            return false;
        }
        
        $request_amoutn = 0;
        
        try {
            foreach (Tools::getValue('partialRefundProduct') as $id => $am) {
                $request_amoutn += floatval($am);
            }

            if($request_amoutn <= 0) {
                $this->context->controller->errors[] = $this->l('Your refund amount must be bigger than 0 !');
                return false;
            }

            $request_amoutn = number_format($request_amoutn, 2, '.', '');
            $order_id = intval(Tools::getValue('id_order'));
            
            // save order message
            $message = new MessageCore();
            $message->id_order = $order_id;
            $message->private = true;

            $order_info = new Order($order_id);
            $currency   = new Currency($order_info->id_currency);
            
            $row = Db::getInstance()->getRow(
                "SELECT id_order_slip FROM " . _DB_PREFIX_ . "order_slip "
                . "WHERE id_order = {$order_id} AND amount = {$request_amoutn} "
                . "ORDER BY id_order_slip DESC");
                
            $last_slip_id = $row['id_order_slip'];
                
            $sc_order_info = Db::getInstance()->getRow(
                "SELECT * FROM safecharge_order_data WHERE order_id = {$order_id}");
                
			$notify_url	= $this->getNotifyUrl();
            $time		= date('YmdHis', time());
            $test_mode	= Configuration::get('SC_TEST_MODE');
            
            $ref_parameters = array(
                'merchantId'            => Configuration::get('SC_MERCHANT_ID'),
                'merchantSiteId'        => Configuration::get('SC_MERCHANT_SITE_ID'),
                'clientRequestId'       => $last_slip_id,
                'clientUniqueId'        => $order_id,
                'amount'                => number_format($request_amoutn, 2, '.', ''),
                'currency'              => $currency->iso_code,
                'relatedTransactionId'  => $sc_order_info['related_transaction_id'], // GW Transaction ID
                'authCode'              => $sc_order_info['auth_code'],
                'url'                   => $notify_url,
                'timeStamp'             => $time,
				'sourceApplication'     => SC_SOURCE_APPLICATION,
            );
            
            $checksum_str = implode('', $ref_parameters);
            
            $checksum = hash(
                Configuration::get('SC_HASH_TYPE'),
                $checksum_str . Configuration::get('SC_SECRET_KEY')
            );
            
            $ref_parameters['checksum']     = $checksum;
            $ref_parameters['urlDetails']   = array('notificationUrl' => $notify_url);
            $ref_parameters['webMasterId']  = 'PreastaShop ' . _PS_VERSION_;
            
            $json_arr = $this->callRestApi('refundTransaction', $ref_parameters);
        }
        catch(Exception $e) {
            $this->createLog($e->getMessage(), 'hookActionOrderSlipAdd Exception: ');
            $this->context->controller->errors[] = $this->l('Error while trying to colect refund data.');
            return false;
        }
        
        if(!$json_arr) {
            $this->context->controller->errors[] = $this->l('Empty request response.');
            return false;
        }
        
        // in case we have message but without status
        if(!isset($json_arr['status']) && isset($json_arr['msg'])) {
            // save response message in the History
            $msg = $this->l('Request for Refund #') . $last_slip_id . $this->l(' problem: ') . $json_arr['msg'];
            $this->context->controller->errors[] = $msg;
            
            $message->message = $msg;
            $message->add();
            
            return false;
        }
        
        $cpanel_url = ($test_mode == 'yes' ? 'sandbox' : 'cpanel') . '.safecharge.com';
        
        $msg = '';
        $error_note = $this->l('Request for Refund #') . $last_slip_id 
			. $this->l(' fail, if you want login into') . ' <i>' . $cpanel_url . '</i> '
            . $this->l('and refund Transaction ID ') . $sc_order_info['related_transaction_id'];
        
        if($json_arr === false) {
            $msg = $this->l('The REST API retun false. ') . $error_note;
            $this->context->controller->errors[] = $msg;

            $message->message = $msg;
            $message->add();
            
            return false;
        }
        
        if(!is_array($json_arr)) {
            $msg = $this->l('Invalid API response. ') . $error_note;
            $this->context->controller->errors[] = $msg;

            $message->message = $msg;
            $message->add();
            
            return false;
        }
        
        // the status of the request is ERROR
        if(@$json_arr['status'] == 'ERROR') {
            $msg = $this->l('Request ERROR - ') . $json_arr['reason'] .'" '. $error_note;
            $this->context->controller->errors[] = $msg;

            $message->message = $msg;
            $message->add();
            
            return false;
        }
        
        // if request is success, we will wait for DMN
        $msg = $this->l('Request for Refund #') . $last_slip_id . $this->l(', was sent. Please, wait for DMN!');
        $this->context->controller->success[] = $msg;
        
        $message->message = $msg;
        $message->add();
        
        return true;
    }

    /**
     * Function isPayment
     * Actually here we check if the SC plugin is active and configured
     * 
     * @return boolean - the result
     */
    public function isPayment()
    {
        if (!$this->active) {
            return false;
        }
        
        if (!Configuration::get('SC_MERCHANT_SITE_ID')) {
            $this->createLog('Error: (invalid or undefined Merchant Site ID)');
            return $this->displayName . $this->l(' Error: (invalid or undefined Merchant Site ID)');
        }
          
        if (!Configuration::get('SC_MERCHANT_ID')) {
            $this->createLog('Error: (invalid or undefined Merchant ID)');
            return $this->displayName . $this->l(' Error: (invalid or undefined Merchant ID)');
        }
        
        if (!Configuration::get('SC_SECRET_KEY')) {
            $this->createLog('Error: (invalid or undefined secure key)');
            return $this->displayName . $this->l(' Error: (invalid or undefined Secure Key)');
        }
          
        return true;
    }
    
    /**
     * Function checkCurrency
     * Check if our payment method is available for order currency
     * 
     * @param Cart $cart - cart object
     * @return boolean
     */
    public function checkCurrency($cart)
	{
		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);

		if (is_array($currencies_module)) {
			foreach ($currencies_module as $currency_module) {
				if ($currency_order->id == $currency_module['id_currency']) {
					return true;
                }
            }
        }
        
		return false;
	}
	
	/**
	 * 
	 * Here we only set template variables
	 */
	public function getPaymentMethods() {
		$this->createLog('getPaymentMethods()');
		
		$params = $this->openOrder();
		
//		$this->createLog($params, 'getPaymentMethods() params');
		
		# get APMs
		$customer			= new Customer($this->context->cart->id_customer);
		$payment_methods	= array();
		$upos				= array();
		$time				= date('YmdHis', time());
		$hash				= Configuration::get('SC_HASH_TYPE');
		$secret				= Configuration::get('SC_SECRET_KEY');
		$ask_save_upo		= false;
			
		$apms_params		= array(
			'merchantId'        => $params['merchantId'],
			'merchantSiteId'    => $params['merchantSiteId'],
			'clientRequestId'   => $time. '_' .uniqid(),
			'timeStamp'         => $time,
		);

		$apms_params['checksum']        = hash($hash, implode('', $apms_params) . $secret);
		$apms_params['sessionToken']    = $params['sessionToken'];
		$apms_params['currencyCode']    = $params['currency'];
		$apms_params['countryCode']     = $params['billingAddress']['country'];
		$apms_params['languageCode']    = substr($this->context->language->locale, 0, 2);

		$res							= $this->callRestApi('getMerchantPaymentMethods', $apms_params);

//		if(!is_array($res) || empty($res['paymentMethods'])) {
//			$this->createLog($res, 'No APMs, response is:');
//			
//			return false;
//		}
//
		if(!empty($res['paymentMethods']) && is_array($res['paymentMethods'])) {
			$payment_methods = $res['paymentMethods'];
		}
		# get APMs END
		
		# get UPOs
		// get them only for registred users when there are APMs
		if(
			Configuration::get('SC_USE_UPOS') == 1
			&& $this->context->customer->isLogged()
			&& !empty($payment_methods)
		) {
			$upo_params = array(
				'merchantId'		=> $apms_params['merchantId'],
				'merchantSiteId'	=> $apms_params['merchantSiteId'],
				'userTokenId'		=> $customer->email,
				'clientRequestId'	=> $apms_params['clientRequestId'],
				'timeStamp'			=> $time,
			);

			$upo_params['checksum'] = hash($hash, implode('', $upo_params) . $secret);
			$upo_res				= $this->callRestApi('getUserUPOs', $upo_params);
			$ask_save_upo			= true;

			if(!empty($upo_res['paymentMethods']) && is_array($upo_res['paymentMethods'])) {
				foreach($upo_res['paymentMethods'] as $data) {
					// chech if it is not expired
					if(!empty($data['expiryDate']) && date('Ymd') > $data['expiryDate']) {
						continue;
					}

					if(empty($data['upoStatus']) || $data['upoStatus'] !== 'enabled') {
						continue;
					}

					// search for same method in APMs, use this UPO only if it is available there
					foreach($payment_methods as $pm_data) {
						// found it
						if($pm_data['paymentMethod'] === $data['paymentMethodName']) {
							$data['logoURL']	= @$pm_data['logoURL'];
							$data['name']		= @$pm_data['paymentMethodDisplayName'][0]['message'];

							$upos[] = $data;
							break;
						}
					}
				}
			}
		}
		# get UPOs END
		
		$this->context->smarty->assign('sessionToken',		$params['sessionToken']);
		$this->context->smarty->assign('userTokenId',		$customer->email);
		$this->context->smarty->assign('askSaveUpo',		$ask_save_upo);
		$this->context->smarty->assign('paymentMethods',	$payment_methods);
		$this->context->smarty->assign('upos',				$upos);
	}

	/**
	 * Function createLog
	 * @param mixed $data
	 * @param string $title
	 * @return
	 */
	public function createLog($data, $title = '') {
		// path is different fore each plugin
        $logs_path = _PS_ROOT_DIR_ . '/var/logs/';
		
		if(!is_dir($logs_path) || Configuration::get('SC_CREATE_LOGS') == 'no') {
			return;
		}
        
		$d		= $data;
		$string	= '';

		if(is_array($data)) {
			// do not log accounts if on prod
			if(Configuration::get('SC_TEST_MODE') == 'no') {
				if(!empty($data['userAccountDetails']) && is_array($data['userAccountDetails'])) {
					$data['userAccountDetails'] = 'userAccountDetails details';
				}
				if(!empty($data['userPaymentOption']) && is_array($data['userPaymentOption'])) {
					$data['userPaymentOption'] = 'userPaymentOption details';
				}
				if(!empty($data['paymentOption']) && is_array($data['paymentOption'])) {
					$data['paymentOption'] = 'paymentOption details';
				}
			}
			// do not log accounts if on prod
			
			if(!empty($data['paymentMethods']) && is_array($data['paymentMethods'])) {
				$data['paymentMethods'] = json_encode($data['paymentMethods']);
			}

			$d = Configuration::get('SC_TEST_MODE') == 'yes' ? print_r($data, true) : json_encode($data);
		}
		elseif(is_object($data)) {
			$d = Configuration::get('SC_TEST_MODE') == 'yes' ? print_r($data, true) : json_encode($data);
		}
		elseif(is_bool($data)) {
			$d = $data ? 'true' : 'false';
		}

		$string .= '[v.' . $this->version . '] | ';

		if(!empty($title)) {
			if(is_string($title)) {
				$string .= $title;
			}
			else {
				$string .= "\r\n" . (Configuration::get('SC_TEST_MODE') == 'yes'
					? json_encode($title, JSON_PRETTY_PRINT) : json_encode($title));
			}
			
			$string .= "\r\n";
		}

		$string .= $d . "\r\n\r\n";

		try {
			file_put_contents(
				$logs_path . 'Nuvei-' . date('Y-m-d', time()) . '.txt',
				date('H:i:s', time()) . ': ' . $string, FILE_APPEND
			);
		}
		catch (Exception $exc) {}
	}
	
	/**
	 * Function callRestApi
	 * Create a Rest Api call and log input and output parameters
	 * 
	 * @param string $method
	 * @param array $params
	 * 
	 * @return mixed $resp
	 */
	public function callRestApi($method, $params) {
		$resp	= '';
		$url	= $this->getEndPointBase() . $method . '.do';
		
		if(empty($method)) {
			$this->createLog($url, 'callRestApi() Error - the passed method can not be empty.');
			return false;
		}
		
		if(!filter_var($url, FILTER_VALIDATE_URL)) {
			$this->createLog($url, 'callRestApi() Error - the passed url is not valid.');
			return false;
		}
		
		if(!is_array($params)) {
			$this->createLog($params, 'callRestApi() Error - the passed params parameter is not array ot object.');
			return false;
		}
		
		$this->createLog(
			array(
				'REST API URL'	=> $url,
				'params'		=> $params
			),
			'REST API call (before validation)'
		);

		$resp = SC_CLASS::call_rest_api($url, $params);

		$this->createLog($resp, 'Rest API response');
		
		return $resp;
	}
	
	/**
	 * Function openOrder
	 * 
	 * @global type $smarty
	 * @param bool $is_ajax
	 * 
	 * @return boolean
	 */
	public function openOrder($is_ajax = false) {
		$this->createLog('openOrder()');
		
		global $smarty;
		
		$session_token		= '';
		$payment_methods	= array();
		$upos				= array();
		$time               = date('YmdHis', time());
		$test_mode			= Configuration::get('SC_TEST_MODE');
		$hash				= Configuration::get('SC_HASH_TYPE');
		$secret				= Configuration::get('SC_SECRET_KEY');
		$call_open_order	= true;
		
		# set some parameters
		$this->context->smarty->assign('merchantId',            Configuration::get('SC_MERCHANT_ID'));
		$this->context->smarty->assign('merchantSiteId',        Configuration::get('SC_MERCHANT_SITE_ID'));
		$this->context->smarty->assign('preselectCC',           Configuration::get('NUVEI_PRESELECT_CC'));
		$this->context->smarty->assign('showAPMsName',          Configuration::get('NUVEI_SHOW_APMS_NAMES'));
		$this->context->smarty->assign('customAPMsNote',        Configuration::get('NUVEI_APMS_NOTE'));
		$this->context->smarty->assign('customStyle',           Configuration::get('NUVEI_PMS_STYLE'));
		$this->context->smarty->assign('preselectNuveiPayment', Configuration::get('NUVEI_PRESELECT_PAYMENT'));
		$this->context->smarty->assign('formAction',            $this->context->link->getModuleLink('nuvei', 'payment'));
		$this->context->smarty->assign('webMasterId',           SC_PRESTA_SHOP . _PS_VERSION_);
		$this->context->smarty->assign('sourceApplication',     SC_SOURCE_APPLICATION);
		$this->context->smarty->assign('languageCode',          substr($this->context->language->locale, 0, 2));
		$this->context->smarty->assign('isTestEnv',             $test_mode);
		$this->context->smarty->assign('scAPMsErrorMsg',        '');
		
		$this->context->smarty->assign('ooAjaxUrl',			$this->context->link->getModuleLink(
			'nuvei',
			'payment',
			array('prestaShopAction' => 'createOpenOrder')
		));

		$this->context->smarty->assign('scDeleteUpoUrl',	$this->context->link->getModuleLink(
			'nuvei',
			'payment',
			array('prestaShopAction' => 'deleteUpo')
		));
		
		try {
			$cart               = $this->context->cart;
			$products			= $cart->getProducts();
			$currency           = new Currency((int)($cart->id_currency));
			$customer           = new Customer($cart->id_customer);
			$address_invoice    = new Address((int)($cart->id_address_invoice));
			$country_inv        = new Country((int)($address_invoice->id_country), Configuration::get('PS_LANG_DEFAULT'));
			$amount				= (string) number_format($cart->getOrderTotal(), 2, '.', '');
			
			$address_delivery	= $address_invoice;
			$country_delivery	= $country_inv;
            
			if(!empty($cart->id_address_delivery) && $cart->id_address_delivery != $cart->id_address_invoice) {
                $address_delivery	= new Address((int)($cart->id_address_delivery));
				$country_delivery   = new Country((int)($address_delivery->id_country), Configuration::get('PS_LANG_DEFAULT'));
            }
			
			# try updateOrder
			$resp = $this->updateOrder(); // this is merged array of response and the session

			if (!empty($resp['status']) && 'SUCCESS' == $resp['status']) {
				if ($is_ajax) {
					echo json_encode(array(
						'status'        => 1,
						'sessionToken'	=> $resp['sessionToken']
					));

					exit;
				}

				$this->context->smarty->assign('sessionToken', $resp['sessionToken']);

				// pass billing country
				$resp['billingAddress']['country'] = $country_inv->iso_code;
				
				return $resp;
			}
			# try updateOrder END
			
			$notify_url     = $this->getNotifyUrl();
			
			$error_url		= $this->context->link->getModuleLink(
				'nuvei',
				'payment',
				array('prestaShopAction' => 'showError')
			);

			$success_url	= $this->context->link->getModuleLink(
				'nuvei',
				'payment',
				array(
					'prestaShopAction'	=> 'showCompleted',
					'id_cart'			=> (int)$cart->id,
					'id_module'			=> $this->id,
					'status'			=> Configuration::get('PS_OS_PREPARATION'),
					'amount'			=> $amount,
					'module'			=> $this->displayName,
					'key'				=> $customer->secure_key,
				)
			);
			
			# Open Order
			$oo_params = array(
				'merchantId'        => Configuration::get('SC_MERCHANT_ID'),
				'merchantSiteId'    => Configuration::get('SC_MERCHANT_SITE_ID'),
				'clientRequestId'   => $time . '_' . uniqid(),
				'clientUniqueId'	=> (int)$cart->id,
				'amount'            => $amount,
				'currency'          => $currency->iso_code,
				'timeStamp'         => $time,

				'urlDetails'        => array(
					'notificationUrl'   => $notify_url,
					'successUrl'		=> $success_url,
					'failureUrl'		=> $error_url,
					'pendingUrl'		=> $success_url,
				),

				'deviceDetails'     => SC_CLASS::get_device_details($this->version),

				'billingAddress'    => array(
					"firstName"	=> $address_invoice->firstname,
					"lastName"	=> $address_invoice->lastname,
					"address"   => $address_invoice->address1,
					"phone"     => $address_invoice->phone,
					"zip"       => $address_invoice->postcode,
					"city"      => $address_invoice->city,
					'country'	=> $country_inv->iso_code,
					'email'		=> $customer->email,
				),

				'shippingAddress'    => array(
					"firstName"	=> $address_delivery->firstname,
					"lastName"	=> $address_delivery->lastname,
					"address"   => $address_delivery->address1,
					"phone"     => $address_delivery->phone,
					"zip"       => $address_delivery->postcode,
					"city"      => $address_delivery->city,
					'country'	=> $country_delivery->iso_code,
					'email'		=> $customer->email,
				),

				'webMasterId'       => SC_PRESTA_SHOP . _PS_VERSION_,
				'paymentOption'		=> ['card' => ['threeD' => ['isDynamic3D' => 1]]],
				'transactionType'	=> Configuration::get('SC_PAYMENT_ACTION'),
				'merchantDetails'	=> array(
					'customField1' => $cart->secure_key,
					'customField2' => 'PrestaShop Plugin v' . $this->version,
					'customField3' => '', // items info
					'customField4' => time(), // time when we create request
				),
			);

			$products_data = array();
			foreach ($products as $product) {
				$products_data[$product['id_product']] = array(
//						'name'		=> $product['name'],
					'quantity'	=> $product['quantity'],
					'total_wt'	=> (string)round(floatval($product['total_wt']), 2)
				);
			}

			$oo_params['merchantDetails']['customField3']	= json_encode($products_data);
			$oo_params['userDetails']						= $oo_params['billingAddress'];
			
			$oo_params['checksum'] = hash(
				$hash,
				$oo_params['merchantId'] . $oo_params['merchantSiteId'] . $oo_params['clientRequestId']
					. $oo_params['amount'] . $oo_params['currency'] . $time . $secret
			);

			$resp = $this->callRestApi('openOrder', $oo_params);
			
			if(
				empty($resp['sessionToken'])
				|| empty($resp['status'])
				|| 'SUCCESS' != $resp['status']
			) {
				if(!empty($resp['message'])) {
					$this->context->smarty->assign('scAPMsErrorMsg',	$resp['message']);
				}

				return false;
			}

			$session_token = $resp['sessionToken'];

			$_SESSION['nuvei_last_open_order_details'] = array(
				'amount'			=> $oo_params['amount'],
				'items'				=> $oo_params['merchantDetails']['customField2'],
				'sessionToken'		=> $resp['sessionToken'],
				'clientRequestId'	=> $oo_params['clientRequestId'],
				'orderId'			=> $resp['orderId'],
				'billingAddress'	=> array('country' => $oo_params['billingAddress']['country']),
			);
			
			// when need session token only
			if($is_ajax) {
				$this->createLog($session_token, 'Session token for Ajax call');

				echo json_encode(array(
					'status'        => 1,
					'sessionToken' => $session_token
				));
				exit;
			}
			
			// pass billing country
//			$resp['billingAddress']['country'] = $country_inv->iso_code;
			
			return array_merge($oo_params, $resp);
		}
		catch (Exception $ex) {
			$this->createLog($e->getMessage(), 'hookPaymentOptions Exception');
			$this->context->smarty->assign('scAPMsErrorMsg',	'Exception ' . $e->getMessage());
			
			return false;
		}
		# create openOrder END
	}
	
	/**
	 * Function getEndPointBase
	 * Get the URL to the endpoint, without the method name, based on the site mode.
	 * 
	 * @return string
	 */
	private function getEndPointBase() {
		if (Configuration::get('SC_TEST_MODE') == 'yes') {
			return 'https://ppp-test.safecharge.com/ppp/api/v1/';
		}
		
		return 'https://secure.safecharge.com/ppp/api/v1/';
	}
	
	/**
	 * Function update_order
	 * 
	 * @return array
	 */
	private function updateOrder() {
		$this->createLog(
			isset($_SESSION['nuvei_last_open_order_details']) ? $_SESSION['nuvei_last_open_order_details'] : '',
			'updateOrder() - session[nuvei_last_open_order_details]'
		);
		
		if (
			empty($_SESSION['nuvei_last_open_order_details'])
			|| empty($_SESSION['nuvei_last_open_order_details']['sessionToken'])
			|| empty($_SESSION['nuvei_last_open_order_details']['orderId'])
			|| empty($_SESSION['nuvei_last_open_order_details']['clientRequestId'])
		) {
			$this->createLog('update_order() - Missing last Order session data.');
			
			return array('status' => 'ERROR');
		}
		
		$time			= date('YmdHis');
		$cart_amount	= (string) round($this->context->cart->getOrderTotal(), 2);
		$cart_items		= array();
		$currency		= new Currency((int)($this->context->cart->id_currency));

		// get items
		foreach ($this->context->cart->getProducts() as $product) {
			$cart_items[$product['id_product']] = array(
//				'name'		=> $product['name'],
				'quantity'	=> $product['quantity'],
				'total_wt'	=> (string)round(floatval($product['total_wt']), 2)
			);
		}
		
		// create Order upgrade
		$params = array(
			'sessionToken'		=> $_SESSION['nuvei_last_open_order_details']['sessionToken'],
			'orderId'			=> $_SESSION['nuvei_last_open_order_details']['orderId'],
			'merchantId'		=> Configuration::get('SC_MERCHANT_ID'),
			'merchantSiteId'	=> Configuration::get('SC_MERCHANT_SITE_ID'),
			'clientRequestId'	=> $_SESSION['nuvei_last_open_order_details']['clientRequestId'],
			'currency'			=> $currency->iso_code,
			'amount'			=> $cart_amount,
			'items'				=> array(
				array(
					'name'		=> 'wc_order',
					'price'		=> $cart_amount,
					'quantity'	=> 1
				)
			),
			'merchantDetails'   => array(
				'customField1' => $this->context->cart->secure_key,
				'customField2' => 'PrestaShop Plugin v' . $this->version,
				'customField3' => json_encode($cart_items),
				'customField4' => time(), // time when we create request
			),
			'timeStamp'			=> $time,
		);
		
		$params['checksum'] = hash(
			Configuration::get('SC_HASH_TYPE'),
			$params['merchantId'] . $params['merchantSiteId'] . $params['clientRequestId']
				. $params['amount'] . $params['currency'] . $time . Configuration::get('SC_SECRET_KEY')
		);
		
		$resp = $this->callRestApi('updateOrder', $params);
		
		# Success
		if (!empty($resp['status']) && 'SUCCESS' == $resp['status']) {
			$_SESSION['nuvei_last_open_order_details']['amount'] = $cart_amount;
			$_SESSION['nuvei_last_open_order_details']['items']  = $params['merchantDetails']['customField3'];
			
			return array_merge($resp, $params);
		}
		
		$this->createLog('update_order() - Order update was not successful.');

		return array('status' => 'ERROR');
	}
	
	private function addOrderState()
	{
		$db = Db::getInstance();
		
		$res = $db->getRow('SELECT * '
			. 'FROM ' . _DB_PREFIX_ . "order_state "
			. "WHERE module_name = 'Nuvei' "
			. "ORDER BY id_order_state DESC;");
		
//		if (
//			!Configuration::get('SC_OS_AWAITING_PAIMENT')
//            || !Validate::isLoadedObject(new OrderState(Configuration::get('SC_OS_AWAITING_PAIMENT')))
//		) {
		// create
		if(empty($res)) {
			// create new order state
			$order_state = new OrderState();

			$order_state->invoice		= false;
			$order_state->send_email	= false;
			$order_state->module_name	= 'Nuvei';
			$order_state->color			= '#4169E1';
			$order_state->hidden		= false;
			$order_state->logable		= true;
			$order_state->delivery		= false;

			$order_state->name	= array();
			$languages			= Language::getLanguages(false);

			// set the name for all lanugaes
			foreach ($languages as $language) {
				$order_state->name[ $language['id_lang'] ] = 'Awaiting Nuvei payment';
			}

			if(!$order_state->add()) {
				return false;
			}
			
			// on success add icon
			$source = _PS_MODULE_DIR_ . 'nuvei/views/img/nuvei.png';
			$destination = _PS_ROOT_DIR_ . '/img/os/' . (int)$order_state->id . '.gif';
			copy($source, $destination);

			// set status in the config
			Configuration::updateValue('SC_OS_AWAITING_PAIMENT', (int) $order_state->id);
		}
		// update if need to
		else {
			Configuration::updateValue('SC_OS_AWAITING_PAIMENT', (int) $res['id_order_state']);
		}
		
		return true;
	}
	
    /**
     * Function getRequestStatus
     * We need this stupid function because as response request variable
     * we get 'Status' or 'status'...
     * 
     * @return string
     */
    private function getRequestStatus()
    {
        if(isset($_REQUEST['Status'])) {
            return $_REQUEST['Status'];
        }

        if(isset($_REQUEST['status'])) {
            return $_REQUEST['status'];
        }
        
        return '';
    }
    
    private function checkAdvancedCheckSum()
    {
        try {
            $str = hash(
                Configuration::get('SC_HASH_TYPE'),
                Configuration::get('SC_SECRET_KEY') . @$_REQUEST['totalAmount']
                    . @$_REQUEST['currency'] . @$_REQUEST['responseTimeStamp']
                    . @$_REQUEST['PPP_TransactionID'] . $this->getRequestStatus()
                    . @$_REQUEST['productId']
            );
        }
        catch(Exception $e) {
            $this->createLog($e->getMessage(), 'checkAdvancedCheckSum Exception: ');
            return false;
        }

        if ($str == @$_REQUEST['advanceResponseChecksum']) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Function _postValidation()
     * Validate mandatory fields.
     */
    private function _postValidation()
    {
        if (Tools::getValue('submitUpdate')) {
            if (!Tools::getValue('SC_MERCHANT_SITE_ID')) {
                $this->_postErrors[] = $this->l('Nuvei "Merchant site ID" is required.');
            }
            
            if (!Tools::getValue('SC_MERCHANT_ID')) {
                $this->_postErrors[] = $this->l('Nuvei "Merchant ID" is required.');
            }
            
            if (!Tools::getValue('SC_SECRET_KEY')) {
                $this->_postErrors[] = $this->l('Nuvei "Secret key" is required.');
            }
        }
    }
    
}
