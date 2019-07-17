<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'safecharge' . DIRECTORY_SEPARATOR . 'sc_config.php';
require_once _PS_MODULE_DIR_ . 'safecharge' . DIRECTORY_SEPARATOR . 'sc_logger.php';
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
        
        if (!Configuration::get('safecharge')) {
            $this->warning = $this->l('No name provided');
        }
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

        return $this->display(__FILE__, './views/templates/admin/display_form.tpl');
    }
    
    /**
     * TODO - do we use it ?
     * @global type $smarty
     * @global type $link
     * @param array $params
     * @return boolean
     */
    public function hookPayment($params)
    {
        SC_LOGGER::create_log($params, 'hookPayment params: ');
        
        if($this->isPayment() !== true){
            return false;
        }
        
        global $smarty, $link;
        
        $isPayment = $this->isPayment();
        if($isPayment !== true) {
            if($isPayment === false){
                return false;
            }
            
            $smarty->assign('error', $isPayment);
            return $this->display(__FILE__, './views/templates/front/payment_module.tpl');
        }

        $smarty->assign(
            'payment_l',
            SafeChargeVersionResolver::get_payment_l($link, $this->name)
        );
        
        $smarty->assign('frontend_name', Configuration::get('SC_FRONTEND_NAME'));
        $smarty->assign('module_name', $this->name);

        return $this->display(__FILE__, './views/templates/front/payment_module.tpl');
    }
    
    public function hookPaymentOptions($params)
    {
        if($this->isPayment() !== true){
            return false;
        }
        
        global $smarty;
        
        // check and prepare the data for the APMs
        $sc_api = Configuration::get('SC_PAYMENT_METHOD');
        $smarty->assign('scApi', $sc_api);
        
        if($sc_api == 'rest') {
            try {
                require_once _PS_MODULE_DIR_ . 'safecharge' . DIRECTORY_SEPARATOR . 'SC_REST_API.php';

                $cart               = $this->context->cart;
                $currency           = new Currency((int)($cart->id_currency));
                $customer           = new Customer($cart->id_customer);
                $address_invoice    = new Address((int)($cart->id_address_invoice));
                $country_inv        = new Country((int)($address_invoice->id_country), Configuration::get('PS_LANG_DEFAULT'));
                $is_user_logged     = (bool)$this->context->customer->isLogged();

                $error_url = $this->context->link->getModuleLink(
                    'safecharge',
                    'payment',
                    array('prestaShopAction' => 'showError')
                );
                
                # get UPOs
                $upos = array();

                if($is_user_logged) {
                    $upos_data = SC_REST_API::get_user_upos(
                        array(
                            'merchantId'        => Configuration::get('SC_MERCHANT_ID'),
                            'merchantSiteId'    => Configuration::get('SC_MERCHANT_SITE_ID'),
                            'userTokenId'       => $customer->email,
                            'clientRequestId'   => $cart->id,
                            'timeStamp'         => date('YmdHis', time()),
                        ),
                        array(
                            'hash_type' => Configuration::get('SC_HASH_TYPE'),
                            'secret'    => Configuration::get('SC_SECRET_KEY'),
                            'test'      => Configuration::get('SC_TEST_MODE'),
                        )
                    );
                    
                    if(isset($upos_data['paymentMethods']) && $upos_data['paymentMethods']) {
                        $upos = $upos_data['paymentMethods'];
                    }
                }
                # get UPOs END
            //    echo '<pre>upos: '.print_r($upos,true).'</pre>';

                # get APMs
                $rest_params = array(
                    'secret_key'        => Configuration::get('SC_SECRET_KEY'),
                    'merchantId'        => Configuration::get('SC_MERCHANT_ID'),
                    'merchantSiteId'    => Configuration::get('SC_MERCHANT_SITE_ID'),
                    'currencyCode'      => $currency->iso_code,
                    'languageCode'      => substr($this->context->language->locale, 0, 2),
                    'sc_country'        => $country_inv->iso_code,
                    'payment_api'       => Configuration::get('SC_PAYMENT_METHOD'),
                    'transaction_type'  => Configuration::get('SC_PAYMENT_ACTION'),
                    'test'              => Configuration::get('SC_TEST_MODE'),
                    'hash_type'         => Configuration::get('SC_HASH_TYPE'),
                    'force_http'        => Configuration::get('SC_HTTP_NOTIFY'),
                    'create_logs'       => Configuration::get('SC_CREATE_LOGS'),
                );

                // client request id 1
                $time = date('YmdHis', time());
                $rest_params['cri1'] = $time. '_' .uniqid();

                // checksum 1 - checksum for session token
                $rest_params['cs1'] = hash(
                    $rest_params['hash_type'],
                    $rest_params['merchantId'] . $rest_params['merchantSiteId']
                        . $rest_params['cri1'] . $time . $rest_params['secret_key']
                );

                // client request id 2
                $time = date('YmdHis', time());
                $rest_params['cri2'] = $time. '_' .uniqid();

                // checksum 2 - checksum for get apms
                $rest_params['cs2'] = hash(
                    $rest_params['hash_type'],
                    $rest_params['merchantId'] . $rest_params['merchantSiteId']
                        . $rest_params['cri2'] . $time . $rest_params['secret_key']
                );

                $res = SC_REST_API::get_rest_apms($rest_params);

                if(!is_array($res) || !isset($res['paymentMethods']) || empty($res['paymentMethods'])) {
                    SC_LOGGER::create_log($res, 'API response: ');
                    Tools::redirect($error_url);
                }

                // set template data with the payment methods
                $payment_methods = $res['paymentMethods'];
    //            echo '<pre>apms: '.print_r($payment_methods,true).'</pre>';
                # get APMs END

                // add icons for the upos
                $icons = array();
                
                if($upos && $payment_methods) {
                    foreach($upos as $upo_key => $upo) {
                        if(!@$upo['upoData']['uniqueCC']) {
                            unset($upos[$upo_key]);
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
                                    
                                    break;
                                }
                                else {
                                    $icons[$pm['paymentMethod']] = $pm['logoURL'];
                                    break;
                                }
                            }
                        }
                    }
                }
                
                // get a Session Token for the fields
                $time = date('YmdHis', time());
                $cri1 = $time. '_' .uniqid();

                // checksum 1 - checksum for session token
                $cs1 = hash(
                    $rest_params['hash_type'],
                    $rest_params['merchantId'] . $rest_params['merchantSiteId']
                        . $cri1 . $time . $rest_params['secret_key']
                );
                
                $resp = SC_REST_API::get_session_token(array(
                    'merchantId'        => $rest_params['merchantId'],
                    'merchantSiteId'    => $rest_params['merchantSiteId'],
                    'cri1'              => $cri1,
                    'cs1'               => $cs1,
                    'test'              => $rest_params['test'],
                ));
                
                if(!$resp || !isset($resp['sessionToken']) || !$resp['sessionToken']) {
                    SC_LOGGER::create_log($resp, 'Error when trying to generate Session Token for Fields: ');
                    Tools::redirect($error_url);
                }
                
                $this->context->smarty->assign('sessionToken', $resp['sessionToken']);
                $this->context->smarty->assign('languageCode', $rest_params['languageCode']);
                $this->context->smarty->assign('upos', $upos);
                $this->context->smarty->assign('paymentMethods', $payment_methods);
                $this->context->smarty->assign('icons', $icons);
                $this->context->smarty->assign('isTestEnv', $rest_params['test']);
                $this->context->smarty->assign('merchantSideId', $rest_params['merchantSiteId']);
                $this->context->smarty->assign(
                    'formAction',
                    $this->context->link->getModuleLink('safecharge', 'payment')
                );
            }
            catch(Exception $e) {
                echo $e->getMessage();
                SC_LOGGER::create_log($e->getMessage(), 'hookPaymentOptions Exception: ');
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
            return false;
        }
        
        global $smarty;
        
        $order_id = intval($_GET['id_order']);
        $order_data = new Order($order_id);
        
        $smarty->assign('orderId', $_GET['id_order']);
        $smarty->assign('ajaxUrl', $this->context->link->getAdminLink("AdminSafeChargeAjax"));
        
        $sc_data = Db::getInstance()->getRow('SELECT * FROM safecharge_order_data WHERE order_id = ' . $order_id);
        
        if(!$sc_data) {
            SC_LOGGER::create_log('Missing safecharge_order_data for order ' . $order_id);
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
        
        return $this->display(__FILE__, './views/templates/admin/sc_order_actions.tpl');
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
            return false;
        }
        
        global $smarty;
        
        $messages = MessageCore::getMessagesByOrderId($_GET['id_order'], true);
        $smarty->assign('messages', $messages);
        
        return $this->display(__FILE__, './views/templates/admin/sc_order_notes.tpl');
    }

    /* TODO do we use it? */
    public function success()
    {
        SC_LOGGER::create_log('success()');
        
        include(dirname(__FILE__).'/../../config/config.inc.php');
        include(dirname(__FILE__).'/../../init.php');
        include(dirname(__FILE__).'/payu.php');
        $context = Context::getContext();
        $fc=new Frontcontroller();
        $fc->setmedia();
        include(dirname(__FILE__).'/../../header.php');
    }
    
    public function setOrderStatus($order_id, $status, $id_cart = 0, $total_amount = '', $custom_id = '')
    {
    //    SafeChargeVersionResolver::set_order_status($order_id, $status);
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
        
        
    //    global $smarty;

//        if (!$this->isPayment()){
//            return false;
//        }
        
//        if ($this->generateAdvancedResponseChecksum(Tools::getAllValues) == Tools::getValue("advanceResponseChecksum")) {
//            if (Configuration::get('SC_SAVE_ORDER_BEFORE_REDIRECT') == 'true') {
//                $secure_cart = explode('_', Tools::getValue('invoice_id'));
//                $order_id = (int)$secure_cart[0];
//                $message = new Message();
//                $message->id_order = (int)$order_id;
//                $message->private = false;
//
//                switch (Tools::getValue("Status")) {
//                    case 'APPROVED':
//                        $this->setOrderStatus((int)$order_id,(int)(Configuration::get('PS_OS_PREPARATION')));
//                        $smarty->assign('status', 'ok');
//                    break;
//
//                    case 'DECLINED':
//                        $this->setOrderStatus($order_id,(int)(Configuration::get('PS_OS_ERROR')));
//                        $smarty->assign('status', 'failed');
//                        $message->message = 'Payment failed.'.Tools::getValue("message");
//                        $message->add();
//                    break;
//
//                    case 'ERROR':
//                        $this->setOrderStatus($order_id,(int)(Configuration::get('PS_OS_ERROR')));
//                        $smarty->assign('status', 'failed');
//                        $message->message = 'Payment failed.'.Tools::getValue("message");
//                        $message->add();
//                    break;
//
//                    case 'PENDING':
//                        $this->setOrderStatus($order_id,(int)(Configuration::get('PS_OS_PREPARATION')));
//                        $smarty->assign('status', 'pending');
//                    break;
//
//                    default:
//                        $smarty->assign('status', 'ok');
//                    break;
//
//                }
//            }
//        }
//        else {
//            $smarty->assign('status', 'checksum');
//        }
        
//        $smarty->assign('total_to_pay', Tools::getValue('totalAmount'));
//        
//        if (Configuration::get('SC_SAVE_ORDER_BEFORE_REDIRECT') == 'true') {
//            return $this->display(__FILE__, './views/templates/front/confirmation.tpl');
//        }
//        else{
//            return $this->display(__FILE__, './views/templates/front/confirmation_noorder.tpl');
//        }
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
            return false;
        }
        
        $request_amoutn = 0;
        
        try {
            require_once _PS_MODULE_DIR_ . 'safecharge' . DIRECTORY_SEPARATOR . 'SC_REST_API.php';
            
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
                
            SC_LOGGER::create_log($last_slip_id, '$last_slip_id: ');
                
            $_SESSION['sc_create_logs'] = Configuration::get('SC_CREATE_LOGS');
            
            $notify_url = $this->context->link
                ->getModuleLink('safecharge', 'payment', array(
                    'prestaShopAction'  => 'getDMN',
                    'prestaShopOrderID' => $order_id,
                    'sc_create_logs'    => $_SESSION['sc_create_logs'],
                ));
            
            if(Configuration::get('SC_HTTP_NOTIFY') == 'yes') {
                $notify_url = str_repeat('https://', 'http://', $notify_url);
            }
            
            // execute refund, the response must be array('msg' => 'some msg', 'new_order_status' => 'some status')
            $json_arr = SC_REST_API::refund_order(
                array(
                    'test'              => Configuration::get('SC_TEST_MODE'),
                    'merchantId'        => Configuration::get('SC_MERCHANT_ID'),
                    'merchantSiteId'    => Configuration::get('SC_MERCHANT_SITE_ID'),
                    'hash_type'         => Configuration::get('SC_HASH_TYPE'),
                    'secret'            => Configuration::get('SC_SECRET_KEY'),
                )
                ,array(
                    'id'            => $last_slip_id,
                    'amount'        => $request_amoutn,
                    'reason'        => '', // no reason field
                    'webMasterId'   => 'PreastaShop ' . _PS_VERSION_
                )
                ,array(
                    'order_tr_id'   => $sc_order_info['related_transaction_id'],
                    'auth_code'     => $sc_order_info['auth_code'],
                )
                ,$currency->iso_code
                ,$notify_url
            );
        }
        catch(Exception $e) {
            SC_LOGGER::create_log($e->getMessage(), 'hookActionOrderSlipAdd Exception: ');
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
        
        $refund_url = SC_TEST_REFUND_URL;
        $cpanel_url = SC_TEST_CPANEL_URL;

        if(Configuration::get('SC_TEST_MODE') == 'no') {
            $refund_url = SC_LIVE_REFUND_URL;
            $cpanel_url = SC_LIVE_CPANEL_URL;
        }
        
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
            parse_str($resp, $json_arr);
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
            SC_LOGGER::create_log('Error: (invalid or undefined site id)');
            return $this->l($this->displayName.' Error: (invalid or undefined site id)');
        }
          
        if (!Configuration::get('SC_MERCHANT_ID')) {
            SC_LOGGER::create_log('Error: (invalid or undefined merchant id)');
            return $this->l($this->displayName.' Error: (invalid or undefined merchant id)');
        }
        
        if (!Configuration::get('SC_SECRET_KEY')) {
            SC_LOGGER::create_log('Error: (invalid or undefined secure key)');
            return $this->l($this->displayName.' Error: (invalid or undefined secure key)');
        }
          
        if (Tools::getValue('ppp_status') == "FAIL" || !empty(Tools::getValue('Error'))) {
            SC_LOGGER::create_log('Error: (payment failed, please select another payment method)');
            
            return $this->l($this->displayName.' Error: (payment failed, please select another payment method. "'
              . (!empty(Tools::getValue('Error')) ? urldecode(Tools::getValue('Error')) : '').'")');
        }
          
        return true;
    }
    
    /**
     * TODO - check it, may be useful
     * @return string
     */
    public function getTransactionMessage()
    {
        SC_LOGGER::create_log('getTransactionMessage');
        
        $result = "";
        
        if(!isset($_GET)) {
            return $result;
        }

        $result .=
            $this->l(' PPP_TransactionID = ') . Tools::getValue('PPP_TransactionID')
            .$this->l(', Status = ') . Tools::getValue('Status')
            .$this->l(', TransactionType = ') . Tools::getValue('transactionType')
            .$this->l(', GW_TransactionID = ') . Tools::getValue('TransactionID').', ';

        if(intval(Tools::getValue('ErrCode')) != 0) {
            $result .= $this->l('ErrorStr: ') . urldecode(Tools::getValue('Error'))."\n"
                .$this->l('ErrorCode: ').urldecode(Tools::getValue('ErrCode'))."\n"
                .$this->l('ExtendedErrorCode: ').urldecode(Tools::getValue('ExErrCode'))."\n"
                .$this->l('ErrorMessage: ').urldecode(Tools::getValue('message'))."\n";  
        }

        return $result;
    }
    
    /**
     * TODO - update it !
     * @return type
     */
//    public function getTRansactionStatus()
//	{
//		$status = Tools::getValue('Status');
//
//		if (
//            Tools::getValue("transactionType") == 'Void'
//            || Tools::getValue("transactionType") == 'Chargeback'
//            || Tools::getValue("transactionType") == 'Credit'
//        ) {
//            $status = 'HOLDED';
//		}
//		
//		switch ($status) {	
//			case 'HOLDED':
//					$orderState = (int)(Configuration::get('PS_OS_CANCELED'));
//				break;
//				
//				case 'APPROVED':
//					$orderState = (int)(Configuration::get('PS_OS_PAYMENT'));	
//				break;
//				
//				case 'DECLINED':
//					$orderState = (int)(Configuration::get('PS_OS_CANCELED'));
//				break;
//				
//				case 'ERROR':
//					$orderState = (int)(Configuration::get('PS_OS_CANCELED'));
//				break;
//				
//				case 'PENDING':
//					$orderState = (int)(Configuration::get('PS_OS_PREPARATION'));
//				break;
//				
//				case 'DNM_ERROR':
//				break;
//		}
//        
//		return $orderState;
//	}
    
    /**
     * TODO - update it !
     * @param type $status
     * @param type $transaction_id
     * @return type
     */
//    public function generateDMNResponseChecksum($status, $transaction_id)
//    {
//       return md5(stripslashes(Configuration::get('SC_SECRET_KEY')) . $status . $transaction_id);
//    }
    
    /**
     * TODO - do we use this ?
     * @param type $response
     */
//    public function setTransactionDetail($response)
//    {
//        if (isset($this->pcc))
//        {
//            $this->pcc->transaction_id = (string)$response['PPP_TransactionID'];
//            $this->pcc->card_number = '';
//            $this->pcc->card_brand = (string)$response['payment_method'];
//            $this->pcc->card_expiration = '';
//            $this->pcc->card_holder = (string)(isset($response['email']) ?
//                $response['email'] : $response['merchant_unique_id']);
//        }
//    }
    
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
     * TODO - do we use it ?
     * 
     * @param int $id_lang
     * @return string
     */
//    private function getLocale($id_lang)
//    {
//        $iso = Language::getIsoById(intval($id_lang));
//        
//        $locale = array(
//            'de' => 'de_DE', 'en' => 'en_US','it' => 'it_IT','es' => 'Es_ES',
//            'fr' => 'fr_FR','iw' => 'iw_IL','ar' => 'ar_AA','ru' => 'ru_RU',
//            'nl' => 'nl_NL','bg' => 'bg_BG','zh' => 'zh_CN','ja' => 'ja_JP',
//            'ko' => 'ko_KR','tr' => 'tr_TR','pt' => 'pt_BR'
//        );
//
//        if(isset($locale[$iso])) {
//            return $locale[$iso];
//        }
//        
//        return 'en_US';     
//    }

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
            SC_LOGGER::create_log($e->getMessage(), 'checkAdvancedCheckSum Exception: ');
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
