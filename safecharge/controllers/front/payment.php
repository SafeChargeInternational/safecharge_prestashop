<?php

/**
 * @author SafeCharge
 * @year 2019
 */

if (!session_id()) {
    session_start();
}

require_once _PS_MODULE_DIR_ . 'safecharge' . DIRECTORY_SEPARATOR . 'sc_config.php';
require_once _PS_MODULE_DIR_ . 'safecharge' . DIRECTORY_SEPARATOR . 'sc_logger.php';

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
            SC_LOGGER::create_log('Plugin is not active or missing Merchant mandatory data!');
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
            SC_LOGGER::create_log('processOrder');
            
            # prepare Order data
            $cart       = $this->context->cart;
            $customer   = $this->validate($cart);
            $order_time = date('YmdHis', time());

            # get order data
            $sc_params['numberofitems']    = 1;
            $sc_params['handling']         = '0.00';
            $sc_params['total_tax']        = '0.00'; // taxes
            $sc_params['merchant_id']      = Configuration::get('SC_MERCHANT_ID');
            $sc_params['merchant_site_id'] = Configuration::get('SC_MERCHANT_SITE_ID');
            $sc_params['time_stamp']       = $order_time;
            $sc_params['encoding']         = 'utf-8';
            $sc_params['version']          = '4.0.0';

            $sc_params['success_url']   = $this->context->link->getPageLink(
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
            
            $_SESSION['SC_SUCCESS_URL'] = $sc_params['success_url'];
            
            $sc_params['pending_url']   = $sc_params['success_url'];
            $sc_params['error_url']     = $this->context->link
                ->getModuleLink('safecharge', 'payment', array('prestaShopAction' => 'showError'));
            $sc_params['back_url']      = $this->context->link->getPageLink('order');
            
            $sc_params['notify_url'] = $this->context->link
                ->getModuleLink('safecharge', 'payment', array(
                    'prestaShopAction'  => 'getDMN',
                    'sc_create_logs'       => $_SESSION['sc_create_logs'],
                ));
            
            if(Configuration::get('SC_HTTP_NOTIFY') == 'yes') {
                $sc_params['notify_url'] = str_repeat('https://', 'http://', $sc_params['notify_url']);
            }

            $sc_params['invoice_id']            = $cart->id . '_' . $order_time;
            $sc_params['merchant_unique_id']    = $cart->id;

            $address_invoice    = new Address((int)($cart->id_address_invoice));
            $country_inv        = new Country((int)($address_invoice->id_country), Configuration::get('PS_LANG_DEFAULT'));

            $sc_params['first_name']    = urlencode(preg_replace("/[[:punct:]]/", '', $address_invoice->firstname));
            $sc_params['last_name']     = urlencode(preg_replace("/[[:punct:]]/", '', $address_invoice->lastname));
            $sc_params['address1']      = urlencode(preg_replace("/[[:punct:]]/", '', $address_invoice->address1));
            $sc_params['address2']      = urlencode(preg_replace("/[[:punct:]]/", '', $address_invoice->address2));
            $sc_params['zip']           = $address_invoice->postcode;
            $sc_params['city']          = urlencode(preg_replace("/[[:punct:]]/", '', $address_invoice->city));
            $sc_params['state']         = strlen($address_invoice->id_state) == 2
                ? $address_invoice->id_state : substr($address_invoice->id_state, 0, 2);
            $sc_params['country']       = $country_inv->iso_code;

            $phone                      = $address_invoice->phone ? $address_invoice->phone : $address_invoice->phone_mobile;
            $sc_params['phone1']        = urlencode(preg_replace("/[[:punct:]]/", '', $phone));

            $customer                   = new Customer((int)($cart->id_customer));
            $sc_params['email']         = $customer->email;
            
            $is_user_logged = (bool)$this->context->customer->isLogged();
            $sc_params['user_token_id'] = $is_user_logged ? $customer->email : '';

            $address_delivery = $address_invoice;
            if($cart->id_address_delivery != $cart->id_address_invoice) {
                $address_delivery = new Address((int)($cart->id_address_delivery));
            }

            $country_del = new Country((int)($address_delivery->id_country), Configuration::get('PS_LANG_DEFAULT'));

            $sc_params['shippingFirstName'] = urlencode(preg_replace("/[[:punct:]]/", '', $address_delivery->firstname));
            $sc_params['shippingLastName']  = urlencode(preg_replace("/[[:punct:]]/", '', $address_delivery->lastname));
            $sc_params['shippingAddress']   = urlencode(preg_replace("/[[:punct:]]/", '', $address_delivery->address1));
            $sc_params['shippingCity']      = urlencode(preg_replace("/[[:punct:]]/", '', $address_delivery->city));
            $sc_params['shippingCountry']   = $country_del->iso_code;
            $sc_params['shippingZip']       = $address_delivery->postcode;

            $sc_params['user_token']        = 'auto';
            $sc_params['payment_method']    = ''; // fill it for the REST API

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

            $sc_params['total_amount'] = number_format($cart->getOrderTotal(), 2, '.', '');
            if($sc_params['total_amount'] < 0) {
                $sc_params['total_amount'] = number_format(0, 2, '.', '');
            }

            $currency = new Currency((int)($cart->id_currency));
            $sc_params['currency']         = $currency->iso_code;
            $sc_params['merchantLocale']   = $this->context->language->locale;
            $sc_params['webMasterId']      = 'PrestsShop ' . _PS_VERSION_;

            # get order data END
        }
        catch(Exception $e) {
            SC_LOGGER::create_log($e->getMessage(), 'initContent Exception: ');
            Tools::redirect($this->context->link
                ->getModuleLink('safecharge', 'payment', array('prestaShopAction' => 'showError')));
        }
        
        # REST API flow
        if(Configuration::get('SC_PAYMENT_METHOD') == 'rest') {
            if(empty($_POST)) {
                SC_LOGGER::create_log('REST API Order, but post array is empty.');
                Tools::redirect($sc_params['error_url']);
            }
            
            SC_LOGGER::create_log('REST API Order');
            
            require_once _PS_MODULE_DIR_ . 'safecharge' . DIRECTORY_SEPARATOR . 'SC_REST_API.php';
            
            $this->context->smarty->assign('scApi', 'rest');
            
            // for the REST set one combined item only
            $sc_params['items'][0] = array(
                'name'      => $cart->id,
                'price'     => $sc_params['total_amount'],
                'quantity'  => 1
            );
            
            // specific data for the REST payment
            $sc_params['client_request_id']     = date('YmdHis', time()) .'_'. uniqid();
            
            $sc_params['urlDetails'] = array(
                'successUrl'        => $sc_params['success_url'],
                'failureUrl'        => $sc_params['error_url'],
                'pendingUrl'        => $sc_params['pending_url'],
                'notificationUrl'   => $sc_params['notify_url'],
            );

            // set the payment method type
            $payment_method = 'apm';
            
            $sc_params['APM_data']['payment_method'] = @$_POST['sc_payment_method']; // name of the method
            $sc_params['APM_data']['apm_fields'] = array(); // fields of the method
            
            if(isset($_POST['sc_payment_method'])) {
                // user selected UPO - we get the ID
                if(is_numeric($_POST['sc_payment_method'])) {
                    $payment_method = 'd3d';
                    
                    $sc_params['userPaymentOption'] = array(
                        'userPaymentOptionId'   => $_POST['sc_payment_method'],
                        'CVV'                   => $_POST['upo_cvv_field_' . $_POST['sc_payment_method']],
                    );
                }
                // user selected APM - we get the name
                elseif(in_array(@$_POST['sc_payment_method'], array('cc_card', 'dc_card'))) {
                    $payment_method = 'd3d';

                    if(isset($_POST['sc_payment_method'], $_POST[$_POST['sc_payment_method']]['ccTempToken'])) {
                        $sc_params['APM_data']['apm_fields']['ccTempToken'] =
                            $_POST[$_POST['sc_payment_method']]['ccTempToken'];
                    }
                }
                // if payment method has other fields add them
                elseif(
                    isset($_POST[$_POST['sc_payment_method']])
                    && is_array($_POST[$_POST['sc_payment_method']])
                ) {
                    foreach($_POST[$_POST['sc_payment_method']] as $field => $val) {
                        $sc_params['APM_data']['apm_fields'][$field] = $val;
                    }
                }
            }
            
            SC_LOGGER::create_log($payment_method, '$payment_method:');
            
            $sc_params['lst']             = @$_POST['lst'];
            $sc_params['languageCode']    = substr($sc_params['merchantLocale'], 0, 2);
            $sc_params['test']            = Configuration::get('SC_TEST_MODE');
            $sc_params['merchantId']      = Configuration::get('SC_MERCHANT_ID');
            $sc_params['merchantSiteId']  = Configuration::get('SC_MERCHANT_SITE_ID');
            
            $sc_params['checksum'] = hash(
                Configuration::get('SC_HASH_TYPE'),
                stripslashes(
                    $sc_params['merchantId']
                    . $sc_params['merchantSiteId']
                    . $sc_params['client_request_id']
                    . $sc_params['total_amount']
                    . $sc_params['currency']
                    . $sc_params['time_stamp']
                    . Configuration::get('SC_SECRET_KEY')
            ));
            
            SC_LOGGER::create_log($sc_params, '$sc_params: ');
            
            $resp = SC_REST_API::process_payment(
                $sc_params
                ,array()
                ,$cart->id
                ,$payment_method
            );
            
            $req_status = $this->getRequestStatus($resp);
            
            if(!$resp || $req_status == 'ERROR') {
                SC_LOGGER::create_log(
                    array('$resp' => $resp, 'status' => 'ERROR'),
                    'REST Order error with the response: '
                );
                Tools::redirect($sc_params['error_url']);
            }
            
            if(
                $req_status == 'ERROR'
                || @$resp['transactionStatus'] == 'ERROR'
            ) {
                Tools::redirect($sc_params['error_url']);
            }
            
            // save order
            $this->module->validateOrder(
                (int)$cart->id
                ,Configuration::get('PS_OS_PREPARATION') // the status
                ,$sc_params['total_amount']
                ,$this->module->displayName
            //    ,null
            //    ,null // for the mail
            //    ,(int)$currency->id
            //    ,false
            //    ,$customer->secure_key
            );
            
            $final_url = $sc_params['success_url'];
            
            if($req_status == 'SUCCESS') {
                # The case with D3D and P3D
                /* 
                 * isDynamic3D is hardcoded to be 1, see SC_REST_API line 509
                 * for the three cases see: https://www.safecharge.com/docs/API/?json#dynamic3D,
                 * Possible Scenarios for Dynamic 3D (isDynamic3D = 1)
                 * prepare the new session data
                 */
                if($payment_method == 'd3d') {
                    $params_p3d = array(
                        'sessionToken'      => $resp['sessionToken'],
                        'orderId'           => $resp['orderId'],
                        'merchantId'        => $resp['merchantId'],
                        'merchantSiteId'    => $resp['merchantSiteId'],
                        'userTokenId'       => $resp['userTokenId'],
                        'clientUniqueId'    => $resp['clientUniqueId'],
                        'clientRequestId'   => $resp['clientRequestId'],
                        'transactionType'   => ucfirst(Configuration::get('SC_PAYMENT_ACTION')),
                        'currency'          => $sc_params['currency'],
                        'amount'            => $sc_params['total_amount'],
                        'amountDetails'     => array(
                            'totalShipping'     => '0.00',
                            'totalHandling'     => '0.00',
                            'totalDiscount'     => '0.00',
                            'totalTax'          => '0.00',
                        ),
                        'items'             => $sc_params['items'],
                        'deviceDetails'     => array(), // get them in SC_REST_API Class
                        'shippingAddress'   => array(
                            'firstName'         => $sc_params['shippingFirstName'],
                            'lastName'          => $sc_params['shippingLastName'],
                            'address'           => $sc_params['shippingAddress'],
                            'phone'             => '',
                            'zip'               => $sc_params['shippingZip'],
                            'city'              => $sc_params['shippingCity'],
                            'country'           => $sc_params['shippingCountry'],
                            'state'             => '',
                            'email'             => '',
                            'shippingCounty'    => '',
                        ),
                        'billingAddress'    => array(
                            'firstName'         => $sc_params['first_name'],
                            'lastName'          => $sc_params['last_name'],
                            'address'           => $sc_params['address1'],
                            'phone'             => $sc_params['phone1'],
                            'zip'               => $sc_params['zip'],
                            'city'              => $sc_params['city'],
                            'country'           => $sc_params['country'],
                            'state'             => '',
                            'email'             => $sc_params['email'],
                            'county'            => '',
                        ),
                        'paResponse'        => '',
                        'urlDetails'        => $sc_params['urlDetails'],
                        'timeStamp'         => $sc_params['time_stamp'],
                        'checksum'          => $sc_params['checksum'],
                        'webMasterId'       => $sc_params['webMasterId'],
                    );
                    
                    if(isset($sc_params['APM_data']['apm_fields']['ccTempToken'])) {
                        $params_p3d['cardData']['ccTempToken'] = $sc_params['APM_data']['apm_fields']['ccTempToken'];
                    }
                    elseif(isset($sc_params['userPaymentOption'])) {
                        $params_p3d['userPaymentOption'] = $sc_params['userPaymentOption'];
                    }
                    
                    $_SESSION['SC_P3D_Params'] = $params_p3d;
                    
                    // case 1
                    if(
                        isset($sc_params['acsUrl'], $sc_params['threeDFlow'])
                        && !empty($sc_params['acsUrl'])
                        && intval($sc_params['threeDFlow']) == 1
                    ) {
                        SC_LOGGER::create_log('D3D case 1');
                        SC_LOGGER::create_log($sc_params['acsUrl'], 'acsUrl: ');
                    
                        // step 1 - go to acsUrl, it will return us to Pending page
                    //    $this->context->smarty->assign('acsUrl',  $resp['acsUrl']);
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
                        
                        SC_LOGGER::create_log(
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
                        SC_LOGGER::create_log('process_payment() D3D case 2.');
                        $this->payWithD3dP3d(); // we exit there
                    }
                    // case 3 do nothing
                }
                // The case with D3D and P3D END
                // in case we have redirectURL
                elseif(isset($resp['redirectURL']) && !empty($resp['redirectURL'])) {
                    SC_LOGGER::create_log($resp['redirectURL'], '$sc_params[redirectURL]');
                    $final_url = $resp['redirectURL'];
                }
            }
            
            $this->context->smarty->assign('finalUrl',  $final_url);
        }
        # Cashier flow
        else {
            $this->context->smarty->assign('scApi', 'cashier');
            
            $sc_params['numberofitems'] = $items;
            
            $discount = number_format($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS), 2, '.', '');
            $handling = number_format($cart->getOrderTotal(true, Cart::ONLY_SHIPPING), 2, '.', '');
            
            // last check for correct calculations
            $test_diff = round($items_price + $handling - $discount - $sc_params['total_amount'], 2);
            
            if($test_diff != 0) {
                SC_LOGGER::create_log($handling, 'handling before $test_diff: ');
                SC_LOGGER::create_log($discount, 'discount_total before $test_diff: ');
                SC_LOGGER::create_log($test_diff, '$test_diff: ');
                
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
            
            SC_LOGGER::create_log($sc_params, 'Cashier inputs: ');
            
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
            SC_LOGGER::create_log('payWithD3dP3d(): SC_P3D_Params array is missing');
            Tools::redirect($this->context->link
                ->getModuleLink('safecharge', 'payment', array('prestaShopAction' => 'showError')));
        }
        
        SC_LOGGER::create_log('payWithD3dP3d()');
        
        if(isset($_REQUEST['PaRes'])) {
            $_SESSION['SC_P3D_Params']['paResponse'] = $_REQUEST['PaRes'];
        }
        
        try {
            $order_id = Order::getOrderByCartId($_SESSION['SC_P3D_Params']['clientUniqueId']);
            $order_info = new Order($order_id);
            
            
            
            $p3d_resp = SC_REST_API::call_rest_api(
               Configuration::get('SC_TEST_MODE') == 'yes' ? SC_TEST_P3D_URL : SC_LIVE_P3D_URL
                ,$_SESSION['SC_P3D_Params']
                ,$_SESSION['SC_P3D_Params']['checksum']
            );
        }
        catch (Exception $ex) {
            SC_LOGGER::create_log($ex->getMessage(), 'P3D fail Exception: ');
            Tools::redirect($this->context->link
                ->getModuleLink('safecharge', 'payment', array('prestaShopAction' => 'showError')));
        }
        
        SC_LOGGER::create_log($p3d_resp, 'D3D / P3D, REST API Call response: ');

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

            SC_LOGGER::create_log('Payment 3D API response fails.');
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
        SC_LOGGER::create_log(@$_REQUEST, 'DMN request: ');
        
        if(!$this->checkAdvancedCheckSum()) {
            SC_LOGGER::create_log('DMN report: You receive DMN from not trusted source. The process ends here.');
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
                SC_LOGGER::create_log('Cashier sale.');
                
                try {
                    $arr = explode("_", $_REQUEST['invoice_id']);
                    $cart_id  = intval($arr[0]);
                }
                catch (Exception $ex) {
                    SC_LOGGER::create_log($ex->getMessage(), 'Cashier DMN Exception when try to get Order ID: ');
                    echo 'DMN Exception: ' . $ex->getMessage();
                    exit;
                }
            }
            // REST
            else {
                SC_LOGGER::create_log('REST sale.');
                
                try {
                    $cart_id = intval($_REQUEST['merchant_unique_id']);
                }
                catch (Exception $ex) {
                    SC_LOGGER::create_log($ex->getMessage(), 'REST DMN Exception when try to get Order ID: ');
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
                SC_LOGGER::create_log($ex->getMessage(), 'Sale DMN Exception: ');
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
            SC_LOGGER::create_log('PrestaShop Refund DMN.');
            
            try {
                $order_id = intval(@$_REQUEST['prestaShopOrderID']);
                $order_info = new Order($order_id);
                
                if(!$order_info) {
                    SC_LOGGER::create_log($order_info, 'There is no order info: ');
                    
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
                SC_LOGGER::create_log($e->getMessage(), 'Refund DMN exception: ');
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
            SC_LOGGER::create_log($_REQUEST['transactionType'], 'Void/Settle transactionType: ');
            
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
                SC_LOGGER::create_log($ex->getMessage(), 'scGetDMN() Void/Settle Exception: ');
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
        SC_LOGGER::create_log(
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
                        SC_LOGGER::create_log($e->getMessage(), 'Change order status Exception: ');
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
                SC_LOGGER::create_log($status, 'Unexisting status: ');
        }
        
//        SC_LOGGER::create_log($order_info['id'] . ', ' . @$status_id ? $status_id : 'not changed'
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
            SC_LOGGER::create_log($e->getMessage(), 'checkAdvancedCheckSum Exception: ');
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
            SC_LOGGER::create_log($cart, '$cart: ');
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
            SC_LOGGER::create_log(Module::getPaymentModules(), 'This payment method is not available: ');
            Tools::redirect($this->context->link
                ->getModuleLink('safecharge', 'payment', array('prestaShopAction' => 'showError')));
        }

        $customer = new Customer($cart->id_customer);
        
        if (!Validate::isLoadedObject($customer)) {
            SC_LOGGER::create_log($customer, '$customer: ');
            Tools::redirect($this->context->link->getPageLink('order'));
        }
        
        return $customer;
    }
    
}
