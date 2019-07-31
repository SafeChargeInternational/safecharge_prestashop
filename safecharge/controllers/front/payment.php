<?php

/**
 * @author SafeCharge
 * @year 2019
 */

if (!session_id()) {
    session_start();
}

require_once _PS_MODULE_DIR_ . 'safecharge' . DIRECTORY_SEPARATOR . 'sc_config.php';
require_once _PS_MODULE_DIR_ . 'safecharge' . DIRECTORY_SEPARATOR . 'SC_HELPER.php';

class SafeChargePaymentModuleFrontController extends ModuleFrontController
{
//    public $ssl = true;
    
    public function initContent()
    {
        parent::initContent();
        
        if(
            !Configuration::get('SC_MERCHANT_ID')
            || !Configuration::get('SC_MERCHANT_SITE_ID')
            || !Configuration::get('SC_SECRET_KEY')
            || !Configuration::get('SC_CREATE_LOGS')
        ) {
            SC_HELPER::create_log('Plugin is not active or missing Merchant mandatory data!');
            Tools::redirect($this->context->link->getPageLink('order'));
        }
        
        $_SESSION['sc_create_logs'] = Configuration::get('SC_CREATE_LOGS');

        // continie d3d p3d payment
        if(isset($_REQUEST['PaRes']) && !empty($_REQUEST['PaRes'])) {
            $this->payWithD3dP3d();
            return;
        }
        elseif(Tools::getValue('prestaShopAction', false) == 'showError') {
            $this->scOrderError();
            return;
        }
        elseif(Tools::getValue('prestaShopAction', false) == 'getDMN') {
            $this->scGetDMN();
            return;
        }
        
        $this->processOrder();
    }
    
    private function processOrder()
    {
        try {
            SC_HELPER::create_log('processOrder');
            
            # prepare Order data
            $cart           = $this->context->cart;
            $customer       = $this->validate($cart);
            $order_time     = date('YmdHis', time());
            $is_user_logged = (bool)$this->context->customer->isLogged();
            $test_mode      = Configuration::get('SC_TEST_MODE');

            # get order data
            $success_url    = $this->context->link->getPageLink(
                'order-confirmation',
                null,
                null,
                array(
                    'id_cart'   => (int)$cart->id,
                    'id_module' => (int)$this->module->id,
                    'id_order'  => $this->module->currentOrder,
                    'key'       => $customer->secure_key,
                )
            );
            
            $_SESSION['SC_SUCCESS_URL'] = $success_url;
            
            $pending_url    = $success_url;
            $error_url      = $this->context->link
                ->getModuleLink('safecharge', 'payment', array('prestaShopAction' => 'showError'));
            $back_url       = $this->context->link->getPageLink('order');
            $notify_url     = $this->context->link
                ->getModuleLink('safecharge', 'payment', array(
                    'prestaShopAction'  => 'getDMN',
                    'sc_create_logs'       => $_SESSION['sc_create_logs'],
                ));
            
            if(Configuration::get('SC_HTTP_NOTIFY') == 'yes') {
                $notify_url = str_repeat('https://', 'http://', $notify_url);
            }

            $address_invoice    = new Address((int)($cart->id_address_invoice));
            $phone              = $address_invoice->phone ? $address_invoice->phone : $address_invoice->phone_mobile;
            $country_inv        = new Country((int)($address_invoice->id_country), Configuration::get('PS_LANG_DEFAULT'));
            $customer           = new Customer((int)($cart->id_customer));
            $currency           = new Currency((int)($cart->id_currency));

            $address_delivery = $address_invoice;
            if($cart->id_address_delivery != $cart->id_address_invoice) {
                $address_delivery = new Address((int)($cart->id_address_delivery));
            }

            $country_del = new Country((int)($address_delivery->id_country), Configuration::get('PS_LANG_DEFAULT'));
            
            $total_amount = number_format($cart->getOrderTotal(), 2, '.', '');
            if($total_amount < 0) {
                $total_amount = number_format(0, 2, '.', '');
            }
            # get order data END
        }
        catch(Exception $e) {
            SC_HELPER::create_log($e->getMessage(), 'Process payment Exception: ');
            Tools::redirect($this->context->link
                ->getModuleLink('safecharge', 'payment', array('prestaShopAction' => 'showError')));
        }
        
        # REST API flow
        if(Configuration::get('SC_PAYMENT_METHOD') == 'rest') {
            if(empty($_POST)) {
                SC_HELPER::create_log('REST API Order, but post array is empty.');
                Tools::redirect($error_url);
            }
            
            SC_HELPER::create_log('REST API Order');
            
            $this->context->smarty->assign('scApi', 'rest');
            
            $sc_params = array(
                'merchantId'        => Configuration::get('SC_MERCHANT_ID'),
                'merchantSiteId'    => Configuration::get('SC_MERCHANT_SITE_ID'),
                'userTokenId'       => $is_user_logged ? $customer->email : '',
                'clientUniqueId'    => $cart->id,
                'clientRequestId'   => date('YmdHis', time()) .'_'. uniqid(),
                'currency'          => $currency->iso_code,
                'amount'            => (string) $total_amount,
                'amountDetails'     => array(
                    'totalShipping'     => '0.00',
                    'totalHandling'     => '0.00',
                    'totalDiscount'     => '0.00',
                    'totalTax'          => '0.00',
                ),
                'userDetails'       => array(
                    'firstName'         => urlencode(preg_replace("/[[:punct:]]/", '', $address_invoice->firstname)),
                    'lastName'          => urlencode(preg_replace("/[[:punct:]]/", '', $address_invoice->lastname)),
                    'address'           => urlencode(preg_replace("/[[:punct:]]/", '', $address_invoice->address1)),
                    'phone'             => urlencode(preg_replace("/[[:punct:]]/", '', $phone)),
                    'zip'               => $address_invoice->postcode,
                    'city'              => urlencode(preg_replace("/[[:punct:]]/", '', $address_invoice->city)),
                    'country'           => $country_inv->iso_code,
                    'state'             => strlen($address_invoice->id_state) == 2
											? $address_invoice->id_state : substr($address_invoice->id_state, 0, 2),
                    'email'             => $customer->email,
                    'county'            => '',
                ),
                'shippingAddress'   => array(
                    'firstName'         => urlencode(preg_replace("/[[:punct:]]/", '', $address_delivery->firstname)),
                    'lastName'          => urlencode(preg_replace("/[[:punct:]]/", '', $address_delivery->lastname)),
                    'address'           => urlencode(preg_replace("/[[:punct:]]/", '', $address_delivery->address1)),
                    'cell'              => '',
                    'phone'             => '',
                    'zip'               => $address_delivery->postcode,
                    'city'              => urlencode(preg_replace("/[[:punct:]]/", '', $address_delivery->city)),
                    'country'           => $country_del->iso_code,
                    'state'             => '',
                    'email'             => '',
                    'shippingCounty'    => '',
                ),
                'urlDetails'        => array(
                    'successUrl'        => $success_url,
                    'failureUrl'        => $error_url,
                    'pendingUrl'        => $pending_url,
                    'notificationUrl'   => $notify_url,
                ),
                'timeStamp'         => $order_time,
                'sessionToken'      => @$_POST['lst'],
                'deviceDetails'     => SC_HELPER::get_device_details(),
                'languageCode'      => substr($this->context->language->locale, 0, 2),
                'webMasterId'       => 'PrestsShop ' . _PS_VERSION_,
            );
			
			$sc_params['billingAddress'] = array(
                'firstName'         => $sc_params['userDetails']['firstName'],
                'lastName'          => $sc_params['userDetails']['lastName'],
                'address'           => $sc_params['userDetails']['address'],
                'cell'              => '',
                'phone'             => $sc_params['userDetails']['phone'],
                'zip'               => $sc_params['userDetails']['zip'],
                'city'              => $sc_params['userDetails']['city'],
                'country'           => $sc_params['userDetails']['country'],
                'state'             => $sc_params['userDetails']['state'],
                'email'             => $sc_params['userDetails']['email'],
                'county'            => '',
            );
            
            // for the REST set one combined item only
            $sc_params['items'][0] = array(
                'name'      => $cart->id,
                'price'     => $sc_params['amount'],
                'quantity'  => 1
            );
            
            $sc_params['checksum'] = hash(
                Configuration::get('SC_HASH_TYPE'),
                stripslashes(
                    $sc_params['merchantId']
                    . $sc_params['merchantSiteId']
                    . $sc_params['clientRequestId']
                    . $sc_params['amount']
                    . $sc_params['currency']
                    . $sc_params['timeStamp']
                    . Configuration::get('SC_SECRET_KEY')
            ));
            
            // in case of UPO
            if(is_numeric(@$_POST['sc_payment_method'])) {
                $sc_params['userPaymentOption'] = array(
                    'userPaymentOptionId' => $_POST['sc_payment_method'],
                    'CVV' => $_POST['upo_cvv_field_' . $_POST['sc_payment_method']],
                );
                
                $sc_params['isDynamic3D'] = 1;
                $endpoint_url = $test_mode == 'no' ? SC_LIVE_D3D_URL : SC_TEST_D3D_URL;
            }
            // in case of Card
            elseif(in_array(@$_POST['sc_payment_method'], array('cc_card', 'dc_card'))) {
                if(isset($_POST[$_POST['sc_payment_method']]['ccTempToken'])) {
                    $sc_params['cardData']['ccTempToken'] = $_POST[$_POST['sc_payment_method']]['ccTempToken'];
                }
                
                if(isset($_POST[$_POST['sc_payment_method']]['CVV'])) {
                    $sc_params['cardData']['CVV'] = $_POST[$_POST['sc_payment_method']]['CVV'];
                }
                
                if(isset($_POST[$_POST['sc_payment_method']]['cardHolderName'])) {
                    $sc_params['cardData']['cardHolderName'] = $_POST[$_POST['sc_payment_method']]['cardHolderName'];
                }

                $sc_params['isDynamic3D'] = 1;
                $endpoint_url = $test_mode == 'no' ? SC_LIVE_D3D_URL : SC_TEST_D3D_URL;
            }
            // in case of APM
            elseif(@$_POST['sc_payment_method']) {
                $endpoint_url = $test_mode == 'no' ? SC_LIVE_PAYMENT_URL : SC_TEST_PAYMENT_URL;
                $sc_params['paymentMethod'] = $_POST['sc_payment_method'];
                
                if(isset($_POST[@$_POST['sc_payment_method']]) && is_array($_POST[$_POST['sc_payment_method']])) {
                    $sc_params['userAccountDetails'] = $_POST[$_POST['sc_payment_method']];
                }
            }
            
            $resp = SC_HELPER::call_rest_api($endpoint_url, $sc_params);
            
            SC_HELPER::create_log($resp, 'process order response:');
            
            $req_status = $this->getRequestStatus($resp);
            
            if(
                !$resp
                || $req_status == 'ERROR'
                || @$resp['transactionStatus'] == 'ERROR'
                || @$resp['transactionStatus'] == 'DECLINED'
            ) {
                Tools::redirect($error_url);
            }
            
            // save order
            $this->module->validateOrder(
                (int)$cart->id
                ,Configuration::get('PS_OS_PREPARATION') // the status
                ,$sc_params['amount']
                ,$this->module->displayName
            //    ,null
            //    ,null // for the mail
            //    ,(int)$currency->id
            //    ,false
            //    ,$customer->secure_key
            );
            
            $final_url = $success_url;
            
            if($req_status == 'SUCCESS') {
                # The case with D3D and P3D
                /* 
                 * isDynamic3D is hardcoded to be 1, see SC_REST_API line 509
                 * for the three cases see: https://www.safecharge.com/docs/API/?json#dynamic3D,
                 * Possible Scenarios for Dynamic 3D (isDynamic3D = 1)
                 * prepare the new session data
                 */
                if($payment_method == 'd3d') {
                    $params_p3d = $params;
                    
                    $params_p3d['orderId']          = $resp['orderId'];
                    $params_p3d['transactionType']  = @$resp['transactionType'];
                    $params_p3d['paResponse']       = '';
                    
                    $_SESSION['SC_P3D_Params'] = $params_p3d;
                    
                    // case 1
                    if(
                        isset($resp['acsUrl'], $resp['threeDFlow'])
                        && !empty($resp['acsUrl'])
                        && intval($resp['threeDFlow']) == 1
                    ) {
                        SC_HELPER::create_log('D3D case 1');
                        SC_HELPER::create_log($sc_params['acsUrl'], 'acsUrl: ');
                    
                        // step 1 - go to acsUrl, it will return us to Pending page
                        $final_url = $resp['acsUrl'];
                        
                        $this->context->smarty->assign('PaReq',  $resp['paRequest']);
                        // continue the payment here
                        $this->context->smarty->assign(
                            'TermUrl',
                            $this->context->link->getModuleLink(
                                'safecharge',
                                'payment',
                                array('sc_create_logs' => $_SESSION['sc_create_logs'])
                            )
                        );
                        
                        SC_HELPER::create_log(
                            array(
                                $resp['acsUrl'],
                                $resp['paRequest'],
                                $this->context->link->getModuleLink(
                                    'safecharge',
                                    'payment',
                                    array('sc_create_logs' => $_SESSION['sc_create_logs'])
                                )
                            ),
                            'params for acsUrl: '
                        );
                        
                        // step 2 - wait for the DMN
                    }
                    // case 2
                    elseif(isset($resp['threeDFlow']) && intval($resp['threeDFlow']) == 1) {
                        SC_HELPER::create_log('process_payment() D3D case 2.');
                        $this->payWithD3dP3d(); // we exit there
                    }
                    // case 3 do nothing
                }
                // The case with D3D and P3D END
                // in case we have redirectURL
                elseif(isset($resp['redirectURL']) && !empty($resp['redirectURL'])) {
                    SC_HELPER::create_log($resp['redirectURL'], 'redirectURL:');
                    $final_url = $resp['redirectURL'];
                }
            }
            
            $this->context->smarty->assign('finalUrl',  $final_url);
        }
        # Cashier flow
        else {
            $this->context->smarty->assign('scApi', 'cashier');
            
            $sc_params = array(
                'merchant_id'           => Configuration::get('SC_MERCHANT_ID'),
                'merchant_site_id'      => Configuration::get('SC_MERCHANT_SITE_ID'),
                'time_stamp'            => $order_time,
                'encoding'              => 'utf-8',
                'version'               => '4.0.0',
                'success_url'           => $success_url,
                'pending_url'           => $pending_url,
                'error_url'             => $error_url,
                'back_url'              => $back_url,
                'notify_url'            => $notify_url,
                'invoice_id'            => $cart->id . '_' . $order_time,
                'merchant_unique_id'    => $cart->id,
                'first_name'            => urlencode(preg_replace("/[[:punct:]]/", '', $address_invoice->firstname)),
                'last_name'             => urlencode(preg_replace("/[[:punct:]]/", '', $address_invoice->lastname)),
                'address1'              => urlencode(preg_replace("/[[:punct:]]/", '', $address_invoice->address1)),
                'address2'              => urlencode(preg_replace("/[[:punct:]]/", '', $address_invoice->address2)),
                'zip'                   => $address_invoice->postcode,
                'city'                  => urlencode(preg_replace("/[[:punct:]]/", '', $address_invoice->city)),
                'state'                 => strlen($address_invoice->id_state) == 2
                    ? $address_invoice->id_state : substr($address_invoice->id_state, 0, 2),
                'country'               => $country_inv->iso_code,
                'phone1'                => urlencode(preg_replace("/[[:punct:]]/", '', $phone)),
                'email'                 => $customer->email,
                'user_token_id'         => $is_user_logged ? $customer->email : '',
                'shippingFirstName'     => urlencode(preg_replace("/[[:punct:]]/", '', $address_delivery->firstname)),
                'shippingLastName'      => urlencode(preg_replace("/[[:punct:]]/", '', $address_delivery->lastname)),
                'shippingAddress'       => urlencode(preg_replace("/[[:punct:]]/", '', $address_delivery->address1)),
                'shippingCity'          => urlencode(preg_replace("/[[:punct:]]/", '', $address_delivery->city)),
                'shippingCountry'       => $country_del->iso_code,
                'shippingZip'           => $address_delivery->postcode,
                'user_token'            => 'auto',
                'total_amount'          => $total_amount,
                'merchantLocale'        => $this->context->language->locale,
                'currency'              => $currency->iso_code,
                'webMasterId'           => 'PrestsShop ' . _PS_VERSION_,
            );
            
            $items = $items_price = 0;
            $products = $cart->getProducts(true);
            foreach($products as $product) {
                $items++;

                $single_price = number_format(($product['total_wt'] / $product['quantity']), 2, '.', '');
                $items_price += $product['total_wt'];

                $sc_params['item_name_'.$items]      = urlencode($product['name']);
                $sc_params['item_number_'.$items]    = $product['id_product'];
                $sc_params['item_quantity_'.$items]  = $product['quantity'];
                $sc_params['item_amount_'.$items]    = $single_price;
            }
            
            $sc_params['numberofitems'] = $items;
            
            $discount = number_format($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS), 2, '.', '');
            $handling = number_format($cart->getOrderTotal(true, Cart::ONLY_SHIPPING), 2, '.', '');
            
            // last check for correct calculations
            $test_diff = round($items_price + $handling - $discount - $sc_params['total_amount'], 2);
            
            if($test_diff != 0) {
                SC_HELPER::create_log($handling, 'handling before $test_diff: ');
                SC_HELPER::create_log($discount, 'discount_total before $test_diff: ');
                SC_HELPER::create_log($test_diff, '$test_diff: ');
                
                if($test_diff > 0) {
                    if($handling - $test_diff >= 0) {
						$handling -= $test_diff; // will decrease
					}
                    else {
                        $discount += $test_diff; // will increase
                    }
					
				}
				else {
                    if($discount + $test_diff > 0) {
                        $discount += $test_diff; // will decrease
                    }
                    else {
                        $handling += abs($test_diff); // will increase
                    }
				}
            }
            
            $sc_params['discount'] = number_format($discount, 2, '.', '');
            $sc_params['handling'] = number_format($handling, 2, '.', '');
            
            // the end point URL depends of the test mode and selected payment api
            $action_url = Configuration::get('SC_TEST_MODE') == 'yes'
                ? SC_TEST_CASHIER_URL : SC_LIVE_CASHIER_URL;
            $this->context->smarty->assign('action_url',    $action_url);
            
            $for_checksum = Configuration::get('SC_SECRET_KEY') . implode('', $sc_params);
            
            $sc_params['checksum'] = hash(
                Configuration::get('SC_HASH_TYPE'),
                stripslashes($for_checksum)
            );
            
            SC_HELPER::create_log($sc_params, 'Cashier inputs: ');
            
            $this->module->validateOrder(
                (int)$cart->id
                ,Configuration::get('PS_OS_PREPARATION')
                ,$sc_params['total_amount']
                ,$this->module->displayName
            //    ,null
            //    ,null // for the mail
            //    ,(int)$currency->id
            //    ,false
            //    ,$customer->secure_key
            );
            
            $this->context->smarty->assign('order_params',  $sc_params);
        }
        
        $this->setTemplate('module:safecharge/views/templates/front/form.tpl');
    }
    
    /**
     * Function payWithD3dP3d
     * After we get the DMN form the issuer/bank call this method to continue the flow.
     */
    private function payWithD3dP3d()
    {
        if(!isset($_SESSION['SC_P3D_Params'])) {
            SC_HELPER::create_log('payWithD3dP3d(): SC_P3D_Params array is missing');
            Tools::redirect($this->context->link
                ->getModuleLink('safecharge', 'payment', array('prestaShopAction' => 'showError')));
        }
        
        SC_HELPER::create_log('payWithD3dP3d()');
        
        if(isset($_REQUEST['PaRes'])) {
            $_SESSION['SC_P3D_Params']['paResponse'] = $_REQUEST['PaRes'];
        }
        
        try {
            $order_id = Order::getOrderByCartId($_SESSION['SC_P3D_Params']['clientUniqueId']);
            $order_info = new Order($order_id);
            
            
            
            $p3d_resp = SC_HELPER::call_rest_api(
               Configuration::get('SC_TEST_MODE') == 'yes' ? SC_TEST_P3D_URL : SC_LIVE_P3D_URL
                ,$_SESSION['SC_P3D_Params']
                ,$_SESSION['SC_P3D_Params']['checksum']
            );
        }
        catch (Exception $ex) {
            SC_HELPER::create_log($ex->getMessage(), 'P3D fail Exception: ');
            Tools::redirect($this->context->link
                ->getModuleLink('safecharge', 'payment', array('prestaShopAction' => 'showError')));
        }
        
        SC_HELPER::create_log($p3d_resp, 'D3D / P3D, REST API Call response: ');

        if(!$p3d_resp) {
            if(intval($order_info->current_state) == (int)(Configuration::get('PS_OS_PREPARATION'))) {
                $this->changeOrderStatus(
                    array(
                        'id'            => $order_id,
                        'current_state' => $order_info->current_state,
                        'currency'      => $currency->iso_code
                    ),
                    'FAIL'
                );
            }
            else {
                // save order message
                $message = new MessageCore();
                $message->id_order = $order_info['id'];
                $message->private = true;
                $message->message = $this->l('Payment 3D API response fails.');
                $message->add();
            }

            SC_HELPER::create_log('Payment 3D API response fails.');
            Tools::redirect($this->context->link
                ->getModuleLink('safecharge', 'payment', array('prestaShopAction' => 'showError')));
        }

        header('Location: ' . $_SESSION['SC_SUCCESS_URL']);
        exit;
        // now wait for the DMN
    }

    /**
     * Function scOrderError
     * Shows a message when there is an error with the order
     */
    private function scOrderError()
    {
        $this->setTemplate('module:safecharge/views/templates/front/order_error.tpl');
    }
    
    /**
     * Function scGetDMN
     * 
     * IMPORTANT - with the DMN we get CartID, NOT OrderID
     */
    private function scGetDMN()
    {
        SC_HELPER::create_log(@$_REQUEST, 'DMN request: ');
        
        if(!$this->checkAdvancedCheckSum()) {
            SC_HELPER::create_log('DMN report: You receive DMN from not trusted source. The process ends here.');
            echo 'DMN report: You receive DMN from not trusted source. The process ends here.';
            exit;
        }
        
        $req_status = $this->getRequestStatus();
        
        # Sale and Auth
        if(
            isset($_REQUEST['transactionType'], $_REQUEST['invoice_id'])
            && in_array($_REQUEST['transactionType'], array('Sale', 'Auth'))
        ) {
            // Cashier
            if(!empty($_REQUEST['invoice_id'])) {
                SC_HELPER::create_log('Cashier sale.');
                
                try {
                    $arr = explode("_", $_REQUEST['invoice_id']);
                    $cart_id  = intval($arr[0]);
                }
                catch (Exception $ex) {
                    SC_HELPER::create_log($ex->getMessage(), 'Cashier DMN Exception when try to get Order ID: ');
                    echo 'DMN Exception: ' . $ex->getMessage();
                    exit;
                }
            }
            // REST
            else {
                SC_HELPER::create_log('REST sale.');
                
                try {
                    $cart_id = intval($_REQUEST['merchant_unique_id']);
                }
                catch (Exception $ex) {
                    SC_HELPER::create_log($ex->getMessage(), 'REST DMN Exception when try to get Order ID: ');
                    echo 'DMN Exception: ' . $ex->getMessage();
                    exit;
                }
            }
            
            try {
                $order_id = Order::getOrderByCartId($cart_id);
                $order_info = new Order($order_id);
                $this->updateCustomPaymentFields($order_id);

                if(intval($order_info->current_state) != (int)(Configuration::get('PS_OS_PAYMENT'))) {
                    $this->changeOrderStatus(array(
                            'id'            => $order_id,
                            'current_state' => $order_info->current_state,
                        )
                        ,$req_status
                    );
                }
            }
            catch (Exception $ex) {
                SC_HELPER::create_log($ex->getMessage(), 'Sale DMN Exception: ');
                echo 'DMN Exception: ' . $ex->getMessage();
                exit;
            }
            
            echo 'DMN received.';
            exit;
        }
        
        # Refund
        if(
            in_array(@$_REQUEST['transactionType'], array('Credit', 'Refund'))
            && !empty($req_status)
        ) {
            SC_HELPER::create_log('PrestaShop Refund DMN.');
            
            try {
                $order_id = intval(@$_REQUEST['prestaShopOrderID']);
                $order_info = new Order($order_id);
                
                if(!$order_info) {
                    SC_HELPER::create_log($order_info, 'There is no order info: ');
                    
                    echo 'DMN received, but there is no Order.';
                    exit;
                }
                
                $currency = new Currency((int)$order_info->id_currency);
                
                $this->changeOrderStatus(array(
                        'id'            => $order_id,
                        'current_state' => $order_info->current_state,
                        'currency'      => $currency->iso_code,
                    )
                    ,$req_status
                );
            
                echo 'DMN received.';
                exit;
            }
            catch (Excception $e) {
                SC_HELPER::create_log($e->getMessage(), 'Refund DMN exception: ');
                echo 'DMN Exception: ' . $ex->getMessage();
                exit;
            }
        }
        
        # Void, Settle
        if(
            isset($_REQUEST['prestaShopOrderID'], $_REQUEST['transactionType'])
            && is_numeric($_REQUEST['prestaShopOrderID'])
            && in_array($_REQUEST['transactionType'], array('Void', 'Settle'))
        ) {
            SC_HELPER::create_log($_REQUEST['transactionType'], 'Void/Settle transactionType: ');
            
            try {
                $order_info = new Order($_REQUEST['prestaShopOrderID']);
                
                if($_REQUEST['transactionType'] == 'Settle') {
                    $this->updateCustomPaymentFields($_REQUEST['prestaShopOrderID'], false);
                }
                
                $this->changeOrderStatus(
                    array(
                        'id'            => $_REQUEST['prestaShopOrderID'],
                        'current_state' => $order_info->current_state,
                    )
                    ,$req_status
                );
            }
            catch (Exception $ex) {
                SC_HELPER::create_log($ex->getMessage(), 'scGetDMN() Void/Settle Exception: ');
            }
            
            echo 'DMN received.';
            exit;
        }
        
        echo 'DMN received, but not recognized.';
        exit;
    }
    
    /**
     * Function changeOrderStatus
     * Change the status of the order.
     * 
     * @param Order $order_info
     * @param string $status
     * @param array $res_args - we must use $res_args instead $_REQUEST, if not empty
     */
    private function changeOrderStatus($order_info, $status, $res_args = array())
    {
        SC_HELPER::create_log(
            'Order ' . $order_info['id'] .' has Status: ' . $status,
            'Change_order_status(): '
        );
        
        $request = @$_REQUEST;
        if(!empty($res_args)) {
            $request = $res_args;
        }
        
        $msg = '';
        $message = new MessageCore();
        $message->id_order = $order_info['id'];
        
        switch($status) {
            case 'CANCELED':
                $msg = $this->l('Your request was Canceld') . '. '
                    . 'PPP_TransactionID = ' . @$request['PPP_TransactionID']
                    . ", Status = " . $status . ', GW_TransactionID = '
                    . @$request['TransactionID'];

                $status_id = (int)(Configuration::get('PS_OS_CANCELED'));
                break;

            case 'APPROVED':
                // Void
                if(@$_REQUEST['transactionType'] == 'Void') {
                    $msg = $this->l('DMN message: Your Void request was success, Order #')
                        . $order_info['id'] . ' ' . $this->l('was canceld') . '.';

                    $status_id = (int)(Configuration::get('PS_OS_CANCELED'));
                    break;
                }
                
                // Refund
                if(in_array(@$_REQUEST['transactionType'], array('Credit', 'Refund'))) {
                    try {
                        $curr_refund_amount = floatval(@$_REQUEST['totalAmount']);
                        $curr_refund_amount = number_format($curr_refund_amount, 2, '.', '');

                        $formated_refund = $curr_refund_amount . ' ' .$order_info['currency'];

                        $msg = 'DMN message: Your Refund with Transaction ID #'
                            . @$_REQUEST['clientUniqueId'] .' and Refund Amount ' . $formated_refund
                            . ' was APPROVED.';
                    }
                    catch(Exception $e) {
                        SC_HELPER::create_log($e->getMessage(), 'Change order status Exception: ');
                    }
                    break;
                }
                
                $msg = 'The amount has been authorized and captured by ' . SC_GATEWAY_TITLE . '. ';
                $status_id = (int)(Configuration::get('PS_OS_PAYMENT')); // set the Order status to Complete
                
                if(@$_REQUEST['transactionType'] == 'Auth') {
                    $msg = 'The amount has been authorized and wait to for Settle. ';
                    $status_id = (int)(Configuration::get('PS_OS_PREPARATION'));
                }
                elseif(@$_REQUEST['transactionType'] == 'Settle') {
                    $msg = 'The amount has been captured by ' . SC_GATEWAY_TITLE . '. ';
                }
                
                $msg .= 'PPP_TransactionID = ' . @$request['PPP_TransactionID']
                    . ", Status = ". $status;
                
                if(@$_REQUEST['transactionType']) {
                    $msg .= ", TransactionType = ". @$_REQUEST['transactionType'];
                }
                
                $msg .= ', GW_TransactionID = '. @$request['TransactionID'];
                break;

            case 'ERROR':
            case 'DECLINED':
            case 'FAIL':
                $reason = ', Reason = ';
                if(isset($request['reason']) && $request['reason'] != '') {
                    $reason .= $request['reason'];
                }
                elseif(isset($request['Reason']) && $request['Reason'] != '') {
                    $reason .= $request['Reason'];
                }
                
                $msg = 'Payment failed. PPP_TransactionID =  '. @$request['PPP_TransactionID']
                    . ", Status = " . $status . ", Error code = " . @$request['ErrCode']
                    . ", Message = " . @$request['message'] . $reason;
                
                if(@$_REQUEST['transactionType']) {
                    $msg .= ", TransactionType = " . @$_REQUEST['transactionType'];
                }

                $msg .= ', GW_TransactionID = ' . @$request['TransactionID'];
                
                // Void, do not change status
                if(@$_REQUEST['transactionType'] == 'Void') {
                    $msg = $this->l('DMN message: Your Void request fail');
                    
                    if(@$_REQUEST['Reason']) {
                        $msg .= ' ' . $this->l('with message') . ' "' . $_REQUEST['Reason'] . '". ';
                    }
                    else {
                        $msg .= '. ';
                    }
                    
                    break;
                }
                
                // Refund, do not change status
                if(in_array(@$_REQUEST['transactionType'], array('Credit', 'Refund'))) {
                    if(!isset($_REQUEST['totalAmount']) || !$_REQUEST['totalAmount']) {
                        break;
                    }
                    
                    $curr_refund_amount = floatval(@$_REQUEST['totalAmount']);
                    $curr_refund_amount = number_format($curr_refund_amount, 2, '.', '');

                    $formated_refund = $curr_refund_amount . ' ' .$order_info['currency'];
                    
                    $msg = 'DMN message: Your Refund with Transaction ID #'
                        . @$_REQUEST['clientUniqueId'] .' and Refund Amount: ' . $formated_refund
                        . ' ' . @$_REQUEST['requestedCurrency'] . ' was fail.';
                    
                    if(@$_REQUEST['Reason']) {
                        $msg .= ' Reason: ' . $_REQUEST['Reason'] . '.';
                    }
                    elseif(@$_REQUEST['paymentMethodErrorReason']) {
                        $msg .= ' Reason: ' . $_REQUEST['paymentMethodErrorReason'] . '.';
                    }
                    elseif(@$_REQUEST['gwErrorReason']) {
                        $msg .= ' Reason: ' . $_REQUEST['gwErrorReason'] . '.';
                    }
                    
                    break;
                }
                
                break;

            case 'PENDING':
                $status_id = (int)(Configuration::get('PS_OS_PREPARATION'));
                
                if ($order_info['current_state'] == 2 || $order_info['current_state'] == 3) {
                    $status_id = $order_info['current_state'];
                    break;
                }
                
                $msg = 'Payment is still pending, PPP_TransactionID '
                    . @$request['PPP_TransactionID'] . ", Status = " . $status;

                if(@$_REQUEST['transactionType']) {
                    $msg .= ", TransactionType = " . @$_REQUEST['transactionType'];
                }

                $msg .= ', GW_TransactionID = ' . @$request['TransactionID'];
                
                // add one more message
                $message->private = true;
                $message->message = $this->l(SC_GATEWAY_TITLE .' payment status is pending<br/>Unique Id: ')
                        .@$request['PPP_TransactionID'];
                $message->add();
                
                break;
                
            default:
                SC_HELPER::create_log($status, 'Unexisting status: ');
        }
        
//        SC_HELPER::create_log($order_info['id'] . ', ' . @$status_id ? $status_id : 'not changed'
//            . ', ' . $msg, 'order_info id, status_id, msg: ');
        
        // save order history
        if(isset($status_id) && $status_id) {
            $history = new OrderHistory();
            $history->id_order = (int)$order_info['id'];
            $history->changeIdOrderState($status_id, (int)($order_info['id']));
        }
        
        // save order message
        $message->private = true;
        $message->message = $msg;
        $message->add();
    }
    
    /**
     * Function getRequestStatus
     * We need this stupid function because as response request variable
     * we get 'Status' or 'status'...
     * 
     * @param array $params
     * @return string
     */
    private function getRequestStatus($params = array())
    {
        if(empty($params)) {
            $params = $_REQUEST;
        }
        
        if(isset($params['Status'])) {
            return $params['Status'];
        }

        if(isset($params['status'])) {
            return $params['status'];
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
     * Function updateCustomPaymentFields
     * Update Order Custom Payment Fields
     * 
     * @param int $order_id
     * @param bool $insert - if we pass false, we will update
     */
    private function updateCustomPaymentFields($order_id, $insert = true)
    {
        $data = array(
            'order_id'                  => $order_id,
            'auth_code'                 => isset($_REQUEST['AuthCode']) ? $_REQUEST['AuthCode'] : '',
            'related_transaction_id'    => isset($_REQUEST['TransactionID']) ? $_REQUEST['TransactionID'] : '',
            'resp_transaction_type'     => isset($_REQUEST['transactionType']) ? $_REQUEST['transactionType'] : '',
            'payment_method'            => isset($_REQUEST['payment_method']) ? $_REQUEST['payment_method'] : '',
        );
        
        if($insert) {
            $res = Db::getInstance()->insert(
                'safecharge_order_data'
                ,$data
                ,false
                ,true
                ,Db::INSERT
                ,false // do not put prefix on table name !
            );
        }
        else {
            $res = Db::getInstance()->update(
                'safecharge_order_data'
                ,$data
                ,'order_id = ' . intval($order_id)
                ,0
                ,false
                ,true
                ,false // do not put prefix on table name !
            );
        }
    }
    
    /**
     * Function validate
     * Validate process
     * 
     * @param Cart $cart
     * @return Customer
     */
    private function validate($cart)
    {
        if (
            $cart->id_customer == 0
            || $cart->id_address_delivery == 0 
            || $cart->id_address_invoice == 0 
            || !$this->module->active
        ) {
            SC_HELPER::create_log($cart, '$cart: ');
            Tools::redirect($this->context->link->getPageLink('order'));
        }

        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'safecharge') {
                $authorized = true;
                break;
            }
        }
        
        if (!$authorized) {
            SC_HELPER::create_log(Module::getPaymentModules(), 'This payment method is not available: ');
            Tools::redirect($this->context->link
                ->getModuleLink('safecharge', 'payment', array('prestaShopAction' => 'showError')));
        }

        $customer = new Customer($cart->id_customer);
        
        if (!Validate::isLoadedObject($customer)) {
            SC_HELPER::create_log($customer, '$customer: ');
            Tools::redirect($this->context->link->getPageLink('order'));
        }
        
        return $customer;
    }
    
}
