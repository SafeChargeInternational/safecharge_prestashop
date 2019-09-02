<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'safecharge' . DIRECTORY_SEPARATOR . 'sc_config.php';
require_once _PS_MODULE_DIR_ . 'safecharge' . DIRECTORY_SEPARATOR . 'SC_HELPER.php';
require_once _PS_MODULE_DIR_ . 'safecharge' . DIRECTORY_SEPARATOR . 'sc_versions_resolver.php';

class SafeCharge extends PaymentModule
{
    private $_html = '';
    
    public function __construct()
    {
        $this->name = 'safecharge';
        $this->tab = SafeChargeVersionResolver::set_tab();
        $this->version = '1.1';
        $this->author = 'SafeCharge';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->controllers = array('payment', 'validation');
        $this->is_eu_compatible = 1;
        
        $this->currencies = true; // ?
        $this->currencies_mode = 'checkbox'; // for the Payment > Preferences menu

        parent::__construct();

        $this->page = basename(__FILE__, '.php'); // ?
        $this->displayName = $this->l('SafeCharge');
        $this->description = $this->l('Accepts payments by Safecharge.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');
        
        if (!isset($this->owner) || !isset($this->details) || !isset($this->address)) {
            $this->warning = $this->l('Merchant account details must be configured before using this module.');
        }
        
        global $smarty;
        
        $smarty->assign('ajaxUrl', $this->context->link->getAdminLink("AdminSafeChargeAjax"));
        $_SESSION['sc_create_logs'] = Configuration::get('SC_CREATE_LOGS');
    }
	
    public function install()
    {
        if (
            !parent::install()
            || !Configuration::updateValue('SC_MERCHANT_SITE_ID', '')
            || !Configuration::updateValue('SC_MERCHANT_ID', '')
            || !Configuration::updateValue('SC_SECRET_KEY', '')
            || !$this->registerHook('payment')
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('paymentOptions')
            || !$this->registerHook('displayBackOfficeOrderActions')
            || !$this->registerHook('displayAdminOrderLeft')
            || !$this->registerHook('actionOrderSlipAdd')
            || !$this->installTab('AdminCatalog', 'AdminSafeChargeAjax', 'SafeChargeAjax')
        ) {
            return false;
        }
        
        // safecharge_order_data table
        $sql = $q =
            "CREATE TABLE IF NOT EXISTS `safecharge_order_data` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `order_id` int(11) unsigned NOT NULL,
                `auth_code` varchar(20) NOT NULL,
                `related_transaction_id` varchar(20) NOT NULL,
                `resp_transaction_type` varchar(20) NOT NULL,
                `payment_method` varchar(50) NOT NULL,
                
                PRIMARY KEY (`id`),
                KEY `order_id` (`order_id`),
                UNIQUE KEY `un_order_id` (`order_id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        
        Db::getInstance()->execute($sql);
        
        // create tab for the admin module
        $invisible_tab = new Tab();
        
        $invisible_tab->active      = 1;
        $invisible_tab->class_name  = 'AdminSafeChargeAjax';
        $invisible_tab->name        = array();
        
        foreach (Language::getLanguages(true) as $lang) {
            $invisible_tab->name[$lang['id_lang']] = 'AdminSafeChargeAjax';
        }
        
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
        
        $tab->class_name = $class_name;
        $tab->module = $this->name;
        $tab->active = 1;
        return $tab->add();
    }
    
    public function uninstall()
    {
        if (
            !Configuration::deleteByName('SC_MERCHANT_SITE_ID') || 
            !Configuration::deleteByName('SC_MERCHANT_ID') ||
            !Configuration::deleteByName('SC_SECRET_KEY') ||      
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
            Configuration::updateValue('SC_FRONTEND_NAME',      Tools::getValue('SC_FRONTEND_NAME'));
            Configuration::updateValue('SC_MERCHANT_ID',        Tools::getValue('SC_MERCHANT_ID'));
            Configuration::updateValue('SC_MERCHANT_SITE_ID',   Tools::getValue('SC_MERCHANT_SITE_ID'));
            Configuration::updateValue('SC_SECRET_KEY',         Tools::getValue('SC_SECRET_KEY'));
            Configuration::updateValue('SC_HASH_TYPE',          Tools::getValue('SC_HASH_TYPE'));
            Configuration::updateValue('SC_PAYMENT_METHOD',     Tools::getValue('SC_PAYMENT_METHOD'));
            Configuration::updateValue('SC_PAYMENT_ACTION',     Tools::getValue('SC_PAYMENT_ACTION'));
            Configuration::updateValue('SC_TEST_MODE',          Tools::getValue('SC_TEST_MODE'));
            Configuration::updateValue('SC_HTTP_NOTIFY',        Tools::getValue('SC_HTTP_NOTIFY'));
            Configuration::updateValue('SC_CREATE_LOGS',        Tools::getValue('SC_CREATE_LOGS'));
            
            Configuration::updateValue('SC_SAVE_ORDER_BEFORE_REDIRECT', (int)Tools::getValue('SC_SAVE_ORDER_BEFORE_REDIRECT')); // ?
        }

        $this->_postValidation();
        
        if (isset($this->_postErrors) && sizeof($this->_postErrors)) {
            foreach ($this->_postErrors as $err){
                $this->_html .= '<div class="alert error">'. $err .'</div>';
            }
        }
        
        $this->smarty->assign('img_path', '/modules/safecharge/views/img/');

        return $this->display(__FILE__, 'views/templates/admin/display_form.tpl');
    }
    
    public function hookPaymentOptions($params)
    {
        if($this->isPayment() !== true){
            SC_HELPER::create_log('hookPaymentOptions isPayment not true.');
            return false;
        }
        
        global $smarty;
        
        // check and prepare the data for the APMs
        $sc_api = Configuration::get('SC_PAYMENT_METHOD');
        $smarty->assign('scApi', $sc_api);
        
        if($sc_api == 'rest') {
            try {
                $cart               = $this->context->cart;
                $currency           = new Currency((int)($cart->id_currency));
                $customer           = new Customer($cart->id_customer);
                $address_invoice    = new Address((int)($cart->id_address_invoice));
                $country_inv        = new Country((int)($address_invoice->id_country), Configuration::get('PS_LANG_DEFAULT'));
                $is_user_logged     = (bool)$this->context->customer->isLogged();
                $time               = date('YmdHis', time());
                $test_mode          = Configuration::get('SC_TEST_MODE');
                $hash               = Configuration::get('SC_HASH_TYPE');
                $secret             = Configuration::get('SC_SECRET_KEY');

                $error_url          = $this->context->link->getModuleLink(
                    'safecharge',
                    'payment',
                    array('prestaShopAction' => 'showError')
                );
                
                # get session token
                $st_endpoint_url = $test_mode == 'yes' ? SC_TEST_SESSION_TOKEN_URL : SC_LIVE_SESSION_TOKEN_URL;
                
                $st_params = array(
                    'merchantId'        => Configuration::get('SC_MERCHANT_ID'),
                    'merchantSiteId'    => Configuration::get('SC_MERCHANT_SITE_ID'),
                    'clientRequestId'   => $time. '_' .uniqid(),
                    'timeStamp'         => $time,
                );
                
                $st_params['checksum'] = hash($hash, implode('', $st_params) . $secret);
                
                $session_token_data = SC_HELPER::call_rest_api($st_endpoint_url, $st_params);
                
                if(@$session_token_data['status'] != 'SUCCESS') {
                    SC_HELPER::create_log($session_token_data, 'getting getSessionToken error: ');
                    return false;
                }
                # get session token END
                
                 # get APMs
                $apms_params = array(
                    'merchantId'        => $st_params['merchantId'],
                    'merchantSiteId'    => $st_params['merchantSiteId'],
                    'clientRequestId'   => $time. '_' .uniqid(),
                    'timeStamp'         => $time,
                );
                
                $apms_params['checksum']        = hash($hash, implode('', $apms_params) . $secret);
                $apms_params['sessionToken']    = $session_token_data['sessionToken'];
                $apms_params['currencyCode']    = $currency->iso_code;
                $apms_params['countryCode']     = $country_inv->iso_code;
                $apms_params['languageCode']    = substr($this->context->language->locale, 0, 2);
                
                $endpoint_url = $test_mode == 'yes' ? SC_TEST_REST_PAYMENT_METHODS_URL : SC_LIVE_REST_PAYMENT_METHODS_URL;
                
                $res = SC_HELPER::call_rest_api($endpoint_url, $apms_params);
                
                if(!is_array($res) || !isset($res['paymentMethods']) || empty($res['paymentMethods'])) {
                    SC_HELPER::create_log($res, 'No APMs, response is:');
                    return false;
                }
                
                $payment_methods = $res['paymentMethods'];
                # get APMs END
                
                # get UPOs
                $upos = array();
                $icons = array();

                if($is_user_logged) {
                    $upos_params = array(
                        'merchantId'        => $st_params['merchantId'],
                        'merchantSiteId'    => $st_params['merchantSiteId'],
                        'userTokenId'       => $customer->email,
                        'clientRequestId'   => $cart->id,
                        'timeStamp'         => $time,
                    );
                    
                    $upos_params['checksum']    = hash($hash, implode('', $upos_params) . $secret);
                    $endpoint_url = $test_mode == 'yes' ? SC_TEST_USER_UPOS_URL : SC_LIVE_USER_UPOS_URL;
                    
                    $upos_data = SC_HELPER::call_rest_api($endpoint_url, $upos_params);
                    
                    if(isset($upos_data['paymentMethods']) && $upos_data['paymentMethods']) {
                        // add icons for the upos and remove UPOs not present in the APMs
                        foreach($upos_data['paymentMethods'] as $upo_key => $upo) {
                            if(
                                @$upo['upoStatus'] != 'enabled'
                                || (isset($upo['upoData']['ccCardNumber'])
                                    && empty($upo['upoData']['ccCardNumber']))
                                || (isset($upo['expiryDate'])
                                    && strtotime($upo['expiryDate']) < strtotime(date('Ymd')))
                            ) {
                                continue;
                            }

                            // search in payment methods
                            foreach($payment_methods as $pm) {
                                if(@$pm['paymentMethod'] == @$upo['paymentMethodName']) {
                                    if(
                                        in_array(@$upo['paymentMethodName'], array('cc_card', 'dc_card'))
                                        && @$upo['upoData']['brand']
                                    ) {
                                        $icons[@$upo['upoData']['brand']] = str_replace(
                                            'default_cc_card',
                                            $upo['upoData']['brand'],
                                            $pm['logoURL']
                                        );
                                    }
                                    else {
                                        $icons[$pm['paymentMethod']] = $pm['logoURL'];
                                    }

                                    $upos[] = $upo;
                                    break;
                                }
                            }
                        }
                    }
                }
                # get UPOs END
                
                $this->context->smarty->assign('sessionToken', $session_token_data['sessionToken']);
                $this->context->smarty->assign('languageCode', $apms_params['languageCode']);
                $this->context->smarty->assign('upos', $upos);
                $this->context->smarty->assign('paymentMethods', $payment_methods);
                $this->context->smarty->assign('icons', $icons);
                $this->context->smarty->assign('isTestEnv', $test_mode);
                $this->context->smarty->assign('merchantSideId', $apms_params['merchantSiteId']);
                $this->context->smarty->assign(
                    'formAction',
                    $this->context->link->getModuleLink('safecharge', 'payment')
                );
            }
            catch(Exception $e) {
                echo $e->getMessage();
                SC_HELPER::create_log($e->getMessage(), 'hookPaymentOptions Exception: ');
            }
        }
        
        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
            ->setCallToActionText($this->trans('Pay by SafeCharge', array(), 'Modules.safecharge'))
            ->setAction($this->context->link->getModuleLink($this->name, 'payment'))
            ->setAdditionalInformation($smarty->fetch('module:safecharge/views/templates/front/apms.tpl'));
        
        return [$newOption];
    }
    
    /**
     * Function hookDisplayBackOfficeOrderActions
     * Hook to display SC specific order actions
     * 
     * @param array $params
     * @return template
     */
    public function hookDisplayBackOfficeOrderActions($params)
    {
        if($this->isPayment() !== true){
            SC_HELPER::create_log('hookDisplayBackOfficeOrderActions isPayment not true.');
            return false;
        }
        
        global $smarty;
        
        $order_id = intval($_GET['id_order']);
        $order_data = new Order($order_id);
        
        $smarty->assign('orderId', $_GET['id_order']);
    //    $smarty->assign('ajaxUrl', $this->context->link->getAdminLink("AdminSafeChargeAjax"));
        
        $sc_data = Db::getInstance()->getRow('SELECT * FROM safecharge_order_data WHERE order_id = ' . $order_id);
        
        if(!$sc_data) {
            SC_HELPER::create_log('Missing safecharge_order_data for order ' . $order_id);
            return;
        }
        
        $sc_data['plugin_tr_type']  = Configuration::get('SC_PAYMENT_ACTION');
        $sc_data['order_state']     = $order_data->current_state;
        
        $smarty->assign('scData', $sc_data);
        $smarty->assign('state_pending', Configuration::get('PS_OS_PREPARATION'));
        $smarty->assign('state_completed', Configuration::get('PS_OS_PAYMENT'));
        
        // check for refunds
        $rows = Db::getInstance()->getRow('SELECT id_order_slip FROM '. _DB_PREFIX_
            .'order_slip WHERE id_order = ' . $order_id . ' AND amount > 0');
        $smarty->assign('isRefunded', $rows ? 1 : 0);
        
        return $this->display(__FILE__, 'views/templates/admin/sc_order_actions.tpl');
    }
    
    /**
     * Function hookDisplayAdminOrderLeft
     * At the bottom of the left column we will print Notes and other
     * SafeCharge information.
     * 
     * @return template
     */
    public function hookDisplayAdminOrderLeft()
    {
        if($this->isPayment() !== true){
            SC_HELPER::create_log('hookDisplayAdminOrderLeft isPayment not true.');
            return false;
        }
        
        global $smarty;
        
        $messages = MessageCore::getMessagesByOrderId($_GET['id_order'], true);
        $smarty->assign('messages', $messages);
        
        return $this->display(__FILE__, 'views/templates/admin/sc_order_notes.tpl');
    }

    /**
     * Function hookPaymentReturn
     * This hook is executed when the order goes to order-confirmation page.
     * 
     * @global type $smarty
     * @param Order $params
     * @return boolean
     */
    public function hookPaymentReturn($params)
    {
        if($this->isPayment() !== true){
            SC_HELPER::create_log('hookPaymentReturn isPayment not true.');
            return false;
        }
        
        # Cashier
        if(@$_REQUEST['invoice_id'] && @$_REQUEST['ppp_status'] && $this->checkAdvancedCheckSum()) {
            $cart_id = intval(current(explode('_', $_REQUEST['invoice_id'])));
            $order_id = Order::getOrderByCartId($cart_id);
            
            $message = new MessageCore();
            $message->id_order = $order_id;
            $message->private = true;
            
            if (strtolower($_REQUEST['ppp_status']) == 'fail') {
                $message->message = $this->l('User order failed.');
                $message->add();
            }
            else {
                $transactionId = "TransactionId = "
                    . (isset($_REQUEST['TransactionID']) ? $_REQUEST['TransactionID'] : "");

                $pppTransactionId = "; PPPTransactionId = "
                    . (isset($_REQUEST['PPP_TransactionID']) ? $_REQUEST['PPP_TransactionID'] : "");

                $message->message = $this->l("User returned from Safecharge Payment page; ". $transactionId. $pppTransactionId);
            }
            
            $message->add();
        }
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
        if(
            $this->isPayment() !== true
            || !isset($_REQUEST['partialRefund'], $_REQUEST['partialRefundProduct'])
            || !is_array($_REQUEST['partialRefundProduct'])
        ) {
            SC_HELPER::create_log('hookActionOrderSlipAdd isPayment not true or missing request parameters.');
            return false;
        }
        
        $request_amoutn = 0;
        
        try {
        //    require_once _PS_MODULE_DIR_ . 'safecharge' . DIRECTORY_SEPARATOR . 'SC_REST_API.php';
            
            foreach ($_REQUEST['partialRefundProduct'] as $id => $am) {
                $request_amoutn += floatval($am);
            }

            if($request_amoutn <= 0) {
                $this->context->controller->errors[] = $this->l('Your refund amount must be bigger than 0 !');
                return false;
            }

            $request_amoutn = number_format($request_amoutn, 2, '.', '');
            $order_id = intval($_REQUEST['id_order']);
            
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
                
            SC_HELPER::create_log($last_slip_id, '$last_slip_id: ');
                
            $notify_url = $this->context->link
                ->getModuleLink('safecharge', 'payment', array(
                    'prestaShopAction'  => 'getDMN',
                    'prestaShopOrderID' => $order_id,
                    'sc_create_logs'    => $_SESSION['sc_create_logs'],
                ));
            
            if(Configuration::get('SC_HTTP_NOTIFY') == 'yes') {
                $notify_url = str_repeat('https://', 'http://', $notify_url);
            }
            
            $time = date('YmdHis', time());
            $test_mode = Configuration::get('SC_TEST_MODE');
            
            $ref_parameters = array(
                'merchantId'            => Configuration::get('SC_MERCHANT_ID'),
                'merchantSiteId'        => Configuration::get('SC_MERCHANT_SITE_ID'),
                'clientRequestId'       => $time . '_' . $sc_order_info['related_transaction_id'],
                'clientUniqueId'        => $last_slip_id,
                'amount'                => number_format($request_amoutn, 2, '.', ''),
                'currency'              => $currency->iso_code,
                'relatedTransactionId'  => $sc_order_info['related_transaction_id'], // GW Transaction ID
                'authCode'              => $sc_order_info['auth_code'],
                'url'                   => $notify_url,
                'timeStamp'             => $time,
            );
            
            $checksum_str = implode('', $ref_parameters);
            
            $checksum = hash(
                Configuration::get('SC_HASH_TYPE'),
                $checksum_str . Configuration::get('SC_SECRET_KEY')
            );
            
            $ref_parameters['checksum']     = $checksum;
            $ref_parameters['urlDetails']   = array('notificationUrl' => $notify_url);
            $ref_parameters['webMasterId']  = 'PreastaShop ' . _PS_VERSION_;
            
            $refund_url = $test_mode == 'yes' ? SC_TEST_REFUND_URL : SC_LIVE_REFUND_URL;
            
            $json_arr = SC_HELPER::call_rest_api($refund_url, $ref_parameters);
        }
        catch(Exception $e) {
            SC_HELPER::create_log($e->getMessage(), 'hookActionOrderSlipAdd Exception: ');
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
            $msg = $this->l('Request Refund #' . $last_slip_id . ' problem: ' . $json_arr['msg']);
            $this->context->controller->errors[] = $msg;
            
            $message->message = $msg;
            $message->add();
            
            return false;
        }
        
        $cpanel_url = $test_mode == 'yes' ? SC_TEST_CPANEL_URL : SC_LIVE_CPANEL_URL;
        
        $msg = '';
        $error_note = $this->l('Request Refund #' . $last_slip_id . ' fail, if you want login into <i>' . $cpanel_url
            . '</i> and refund Transaction ID ' . $payment_custom_fields[SC_GW_TRANS_ID_KEY]);
        
        if($json_arr === false) {
            $msg = $this->l('The REST API retun false. ' . $error_note);
            $this->context->controller->errors[] = $msg;

            $message->message = $msg;
            $message->add();
            
            return false;
        }
        
        if(!is_array($json_arr)) {
            $msg = $this->l('Invalid API response. ' . $error_note);
            $this->context->controller->errors[] = $msg;

            $message->message = $msg;
            $message->add();
            
            return false;
        }
        
        // the status of the request is ERROR
        if(@$json_arr['status'] == 'ERROR') {
            $msg = $this->l('Request ERROR - "' . $json_arr['reason'] .'" '. $error_note);
            $this->context->controller->errors[] = $msg;

            $message->message = $msg;
            $message->add();
            
            return false;
        }
        
        // if request is success, we will wait for DMN
        $msg = $this->l('Request Refund #' . $last_slip_id . ', was sent. Please, wait for DMN!');
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
            SC_HELPER::create_log('Error: (invalid or undefined site id)');
            return $this->l($this->displayName.' Error: (invalid or undefined site id)');
        }
          
        if (!Configuration::get('SC_MERCHANT_ID')) {
            SC_HELPER::create_log('Error: (invalid or undefined merchant id)');
            return $this->l($this->displayName.' Error: (invalid or undefined merchant id)');
        }
        
        if (!Configuration::get('SC_SECRET_KEY')) {
            SC_HELPER::create_log('Error: (invalid or undefined secure key)');
            return $this->l($this->displayName.' Error: (invalid or undefined secure key)');
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
            SC_HELPER::create_log($e->getMessage(), 'checkAdvancedCheckSum Exception: ');
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
                $this->_postErrors[] = $this->l('SafeCharge "Merchant site ID" is required.');
            }
            
            if (!Tools::getValue('SC_MERCHANT_ID')) {
                $this->_postErrors[] = $this->l('SafeCharge "Merchant ID" is required.');
            }
            
            if (!Tools::getValue('SC_SECRET_KEY')) {
                $this->_postErrors[] = $this->l('SafeCharge "Secret key" is required.');
            }
        }
    }
    
}
