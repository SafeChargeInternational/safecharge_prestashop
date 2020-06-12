<?php

/**
 * @author SafeCharge
 * @year 2019
 */

if (!session_id()) {
    session_start();
}

require_once _PS_MODULE_DIR_ . 'safecharge' . DIRECTORY_SEPARATOR . 'sc_config.php';
require_once _PS_MODULE_DIR_ . 'safecharge' . DIRECTORY_SEPARATOR . 'SC_CLASS.php';

class SafeChargePaymentModuleFrontController extends ModuleFrontController
{
//    public $ssl = true;
    
    public function initContent()
    {
        parent::initContent();
        
//		if(isset($_SESSION['sc_order_vars'])) {
//			unset($_SESSION['sc_order_vars']);
//		}
		
        if(
            !Configuration::get('SC_MERCHANT_ID')
            || !Configuration::get('SC_MERCHANT_SITE_ID')
            || !Configuration::get('SC_SECRET_KEY')
            || !Configuration::get('SC_CREATE_LOGS')
        ) {
            SC_CLASS::create_log('Plugin is not active or missing Merchant mandatory data!');
            Tools::redirect($this->context->link->getPageLink('order'));
        }
        
        $_SESSION['sc_create_logs'] = Configuration::get('SC_CREATE_LOGS');

        if(Tools::getValue('prestaShopAction', false) == 'showError') {
            $this->scOrderError();
            return;
        }
        
		if(Tools::getValue('prestaShopAction', false) == 'getDMN') {
            $this->scGetDMN();
            return;
        }
		
		if(Tools::getValue('prestaShopAction', false) == 'createOpenOrder') {
            $this->createOpenOrder();
            return;
        }
        
        $this->processOrder();
    }
    
    private function processOrder()
    {
		SC_CLASS::create_log(@$_REQUEST, 'processOrder params');
		
        try {
            # prepare Order data
            $cart           = $this->context->cart;
            $customer       = $this->validate($cart);
            $order_time     = date('YmdHis', time());
            $is_user_logged = (bool)$this->context->customer->isLogged();
            $test_mode      = Configuration::get('SC_TEST_MODE');
			
			$total_amount	= number_format($cart->getOrderTotal(), 2, '.', '');
			
			# when use WebSDK
			if(!empty($_POST['sc_transaction_id'])) {
				SC_CLASS::create_log('WebSDK Order');
				
				// save order
				$res = $this->module->validateOrder(
					(int)$cart->id
					,Configuration::get('SC_OS_AWAITING_PAIMENT') // the status
					,$total_amount
					,$this->module->displayName . ' - ' . $this->l('Card') // payment_method
					,'' // message
					,array() // extra_vars
					,null // currency_special
					,false // dont_touch_amount
					,$this->context->cart->secure_key // secure_key
				);

				if(!$res) {
					SC_CLASS::create_log('Order was not validated');

					Tools::redirect($this->context->link->getModuleLink(
						'safecharge',
						'payment',
						array('prestaShopAction' => 'showError')
					));
				}

				Tools::redirect('index.php?controller=order-confirmation&id_cart='
					. $cart->id . '&id_module=' . $this->module->id . '&id_order='
					. $this->module->currentOrder . '&key=' . $customer->secure_key);
			}

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
			
            $pending_url    = $success_url;
			$back_url       = $this->context->link->getPageLink('order');
            
			$error_url      = $this->context->link
                ->getModuleLink('safecharge', 'payment', array('prestaShopAction' => 'showError'));
            
			$notify_url     = $this->context->link
                ->getModuleLink(
					'safecharge', 
					'payment', 
					array(
						'prestaShopAction'  => 'getDMN',
						'sc_create_logs'       => $_SESSION['sc_create_logs'],
					)
				);
			
            if(
				Configuration::get('SC_HTTP_NOTIFY') == 'yes'
				&& false !== strpos($notify_url, 'https://')
			) {
                $notify_url = str_replace('https://', 'http://', $notify_url);
            }
			
            $address_invoice    = new Address((int)($cart->id_address_invoice));
            $phone              = $address_invoice->phone ? $address_invoice->phone : $address_invoice->phone_mobile;
            $country_inv        = new Country((int)($address_invoice->id_country), Configuration::get('PS_LANG_DEFAULT'));
            $customer           = new Customer((int)($cart->id_customer));
            $currency           = new Currency((int)($cart->id_currency));
            $address_delivery	= $address_invoice;
			$country_delivery	= $country_inv;
            
			if(!empty($cart->id_address_delivery) && $cart->id_address_delivery != $cart->id_address_invoice) {
                $address_delivery	= new Address((int)($cart->id_address_delivery));
				$country_delivery   = new Country((int)($address_delivery->id_country), Configuration::get('PS_LANG_DEFAULT'));
            }

            $country_del = new Country((int)($address_delivery->id_country), Configuration::get('PS_LANG_DEFAULT'));
            
            if($total_amount < 0) {
                $total_amount = number_format(0, 2, '.', '');
            }
            # get order data END
        }
        catch(Exception $e) {
            SC_CLASS::create_log($e->getMessage(), 'Process payment Exception: ');
            
			Tools::redirect($this->context->link->getModuleLink(
				'safecharge', 
				'payment', 
				array('prestaShopAction' => 'showError')
			));
        }
		
		#########
		
		$sc_payment_method = Tools::getValue('sc_payment_method', false);
        
		if(!$sc_payment_method) {
			SC_CLASS::create_log('REST API Order, but parameter sc_payment_method is empty.');
			Tools::redirect($error_url);
		}
		
		try {
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
				
				'billingAddress'    => array(
					"firstName"	=> $address_invoice->firstname,
					"lastName"	=> $address_invoice->lastname,
					"address"   => $address_invoice->address1,
					"phone"     => $address_invoice->phone,
					"zip"       => $address_invoice->postcode,
					"city"      => $address_invoice->city,
					'country'	=> $country_inv->iso_code,
					'state'     => strlen($address_invoice->id_state) == 2
									? $address_invoice->id_state : substr($address_invoice->id_state, 0, 2),
					'email'		=> $customer->email,
					'county'    => '',
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
				
				'urlDetails'        => array(
					'successUrl'        => $success_url,
					'failureUrl'        => $error_url,
					'pendingUrl'        => $pending_url,
					'notificationUrl'   => $notify_url,
				),
				'timeStamp'         => $order_time,
				'sessionToken'      => Tools::getValue('lst', ''),
				'deviceDetails'     => SC_CLASS::get_device_details(),
				'languageCode'      => substr($this->context->language->locale, 0, 2),
				'webMasterId'       => SC_PRESTA_SHOP . _PS_VERSION_,
			);
			
			$sc_params['userDetails'] = $sc_params['billingAddress'];
			
			$sc_params['items'][0] = array(
				'name'      => $cart->id,
				'price'     => $sc_params['amount'],
				'quantity'  => 1
			);

			$sc_params['checksum'] = hash(
				Configuration::get('SC_HASH_TYPE'),
				$sc_params['merchantId']
					. $sc_params['merchantSiteId']
					. $sc_params['clientRequestId']
					. $sc_params['amount']
					. $sc_params['currency']
					. $sc_params['timeStamp']
					. Configuration::get('SC_SECRET_KEY')
			);
			
			// UPO
			if(is_numeric($sc_payment_method)) {
				$endpoint_url = $test_mode == 'no' ? SC_LIVE_PAYMENT_NEW_URL : SC_TEST_PAYMENT_NEW_URL;
				$sc_params['paymentOption']['userPaymentOptionId'] = $sc_payment_method;
				
				// the UPO is card and this is the CVV
//				if(!empty($_POST['cvv_for_' . $sc_payment_method])) {
				if(Tools::getValue('cvv_for_' . $sc_payment_method, false)) {
//					$sc_params['paymentOption']['card']['CVV'] = $_POST['cvv_for_' . $sc_payment_method];
					$sc_params['paymentOption']['card']['CVV'] = Tools::getValue('cvv_for_' . $sc_payment_method, '');
				}
			}
			// APM
			else {
				$endpoint_url = $test_mode == 'no' ? SC_LIVE_PAYMENT_URL : SC_TEST_PAYMENT_URL;
				$sc_params['paymentMethod'] = $sc_payment_method;
				
//				if(isset($_POST[$sc_payment_method]) && is_array($_POST[$sc_payment_method])) {
				if(Tools::getValue($sc_payment_method, false) && is_array(Tools::getValue($sc_payment_method, false))) {
//					$sc_params['userAccountDetails'] = $_POST[$sc_payment_method];
					$sc_params['userAccountDetails'] = Tools::getValue($sc_payment_method, false);
				}
			}
			
			$resp = SC_CLASS::call_rest_api($endpoint_url, $sc_params);

			$req_status = $this->getRequestStatus($resp);
		}
		catch(Exception $e) {
			SC_CLASS::create_log($e->getMessage(), 'Process APM payment Exception: ');
            
			Tools::redirect($error_url);
		}

		if(
			!$resp
			|| $req_status == 'ERROR'
			|| @$resp['w kre'] == 'ERROR'
			|| @$resp['transactionStatus'] == 'DECLINED'
		) {
			Tools::redirect($error_url);
		}
		
		if(!empty($sc_params['paymentMethod'])) {
			$order_payment_name = str_replace('apmgw_', '', $sc_params['paymentMethod']);
		}
		elseif(!empty(Tools::getValue('sc_upo_name', ''))) {
			$order_payment_name = str_replace('apmgw_', '', Tools::getValue('sc_upo_name', 'empty'));
		}

		// save order
		$res = $this->module->validateOrder(
			(int)$cart->id
			,Configuration::get('SC_OS_AWAITING_PAIMENT') // the status
			,$sc_params['amount']
			,$this->module->displayName . ' - ' . $order_payment_name // payment_method
			,'' // message
			,array() // extra_vars
			,null // currency_special
			,false // dont_touch_amount
			,$this->context->cart->secure_key // secure_key
		);

		if(!$res) {
			Tools::redirect($error_url);
		}

		$final_url = $success_url;

		if($req_status == 'SUCCESS') {
			// APM
			if(!empty($resp['redirectURL'])) {
				SC_CLASS::create_log($resp['redirectURL'], 'redirectURL:');
				$final_url = $resp['redirectURL'];
			}
			// UPO
			elseif(!empty($resp['paymentOption']['redirectUrl'])) {
				SC_CLASS::create_log($resp['paymentOption']['redirectUrl'], 'redirectURL:');
				$final_url = $resp['paymentOption']['redirectUrl'];
			}
		}

		Tools::redirect($final_url);
    }
	
	/**
	 * Function createOpenOrder
	 * Create new OpenOrder after declined or error try
	 */
	private function createOpenOrder()
	{
		$plugin_root = dirname(dirname(dirname(__FILE__)));
		require_once $plugin_root . '/safecharge.php';
		
		$sc				= new SafeCharge();
		SC_CLASS::create_log($_SERVER, 'createOpenOrder() call prepareOrderData');
		SC_CLASS::create_log($_REQUEST, 'createOpenOrder() call prepareOrderData');
		$session_token	= $sc->prepareOrderData(true);
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
        SC_CLASS::create_log(@$_REQUEST, 'DMN request:');
        
        if(!$this->checkAdvancedCheckSum()) {
            SC_CLASS::create_log('DMN report: You receive DMN from not trusted source. The process ends here.');
            echo 'DMN Error: You receive DMN from not trusted source. The process ends here.';
            exit;
        }
		
		if(empty($_REQUEST['transactionType'])) {
			echo 'DMN Error: transactionType is empty.';
            exit;
		}
		
		if(empty($_REQUEST['TransactionID']) or ! is_numeric($_REQUEST['TransactionID'])) {
			echo 'DMN Error: TransactionID is empty or not numeric.';
            exit;
		}
		
        $req_status = $this->getRequestStatus();
        
        # Sale and Auth
        if(
            isset($_REQUEST['invoice_id'])
            && in_array($_REQUEST['transactionType'], array('Sale', 'Auth'))
        ) {
			// REST and WebSDK
			SC_CLASS::create_log('REST sale.');
			
			if(empty($_REQUEST['merchant_unique_id'])) {
				SC_CLASS::create_log('Sale/Auth Error - merchant_unique_id is empty!');
				echo 'Sale/Auth Error - merchant_unique_id is empty!';
				exit;
			}
                
			try {
				$tries		= 0;
				$order_id	= false;
				
                do {
                    $tries++;

                    $order_id = Order::getIdByCartId($_REQUEST['merchant_unique_id']);

                    if(!$order_id) {
						SC_CLASS::create_log($order_id, '$order_id:');
                        sleep(3);
                    }
                }
				while($tries <= 10 and !$order_id);
                
                if(!$order_id) {
                    SC_CLASS::create_log('The DMN didn\'t wait for the Order creation. Exit.');
					
                    echo 'The DMN didn\'t wait for the Order creation. Exit.';
                    exit;
                }
				
                $order_info = new Order($order_id);
                $this->updateCustomPaymentFields($order_id);

                if(intval($order_info->current_state) != intval(Configuration::get('PS_OS_PAYMENT'))) {
                    $this->changeOrderStatus(array(
                            'id'            => $order_id,
                            'current_state' => $order_info->current_state,
                            'has_invoice'	=> $order_info->hasInvoice(),
                        )
                        ,$req_status
                    );
                }
            }
            catch (Exception $ex) {
                SC_CLASS::create_log($ex->getMessage(), 'Sale DMN Exception: ');
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
            && !empty($_REQUEST['relatedTransactionId'])
        ) {
            SC_CLASS::create_log('PrestaShop Refund DMN.');
            
            try {
				// PS Refund
				if(!empty($_REQUEST['prestaShopOrderID']) and is_numeric($_REQUEST['prestaShopOrderID'])) {
					$order_id = $_REQUEST['prestaShopOrderID'];
				}
				// CPanel Refund
				else {
					$sc_data = Db::getInstance()->getRow(
						'SELECT * FROM safecharge_order_data '
						. 'WHERE related_transaction_id = "' . $_REQUEST['relatedTransactionId'] .'"'
					);
					
					if(empty($sc_data)) {
						echo 'DMN Error: we can not find Order connected with incoming relatedTransactionId.';
						exit;
					}
					
					$order_id = @$sc_data['order_id'];
				}
				
                $order_info = new Order($order_id);
                
                if(!$order_info) {
                    SC_CLASS::create_log($order_info, 'There is no order info: ');
                    
                    echo 'DMN received, but there is no Order.';
                    exit;
                }
                
                $currency = new Currency((int)$order_info->id_currency);
                
                $this->changeOrderStatus(array(
                        'id'            => $order_id,
                        'current_state' => $order_info->current_state,
						'has_invoice'	=> $order_info->hasInvoice(),
                        'currency'      => $currency->iso_code,
                    )
                    ,$req_status
                );
            
                echo 'DMN received.';
                exit;
            }
            catch (Excception $e) {
                SC_CLASS::create_log($e->getMessage(), 'Refund DMN exception: ');
                echo 'DMN Exception: ' . $ex->getMessage();
                exit;
            }
        }
        
        # Void, Settle
        if(in_array($_REQUEST['transactionType'], array('Void', 'Settle'))) {
            SC_CLASS::create_log($_REQUEST['transactionType'], 'Void/Settle transactionType: ');
            
			// PS Void/Settle
			if(!empty($_REQUEST['prestaShopOrderID']) and is_numeric($_REQUEST['prestaShopOrderID'])) {
				$order_id = $_REQUEST['prestaShopOrderID'];
			}
			// CPanel Void/Settle
			else {
				$sc_data = Db::getInstance()->getRow(
					'SELECT * FROM safecharge_order_data '
					. 'WHERE related_transaction_id = "' . @$_REQUEST['relatedTransactionId'] . '"'
				);
				
				if(!empty($sc_data)) {
					$order_id = @$sc_data['order_id'];
				}
				else {
					$order_id = null;
				}
			}
			
            try {
                $order_info = new Order($order_id);
                
                if($_REQUEST['transactionType'] == 'Settle') {
                    $this->updateCustomPaymentFields($order_id, false);
                }
                
                $this->changeOrderStatus(
                    array(
                        'id'            => $order_id,
                        'current_state' => $order_info->current_state,
						'has_invoice'	=> $order_info->hasInvoice(),
                    )
                    ,$req_status
                );
            }
            catch (Exception $ex) {
                SC_CLASS::create_log($ex->getMessage(), 'scGetDMN() Void/Settle Exception: ');
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
        SC_CLASS::create_log(
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
                $msg = $this->l('Your request was Canceled') . '. '
                    . 'PPP_TransactionID = ' . @$request['PPP_TransactionID']
                    . ", Status = " . $status . "\n\r" . 'TransactionID = '
                    . @$request['TransactionID'];

                $status_id = (int)(Configuration::get('PS_OS_CANCELED'));
                break;

            case 'APPROVED':
				$status_id = (int)(Configuration::get('PS_OS_PAYMENT')); // set the Order status to Complete
				
                // Void
                if($_REQUEST['transactionType'] == 'Void') {
                    $msg = $this->l('DMN message: Your Void request was success, Order #')
                        . $order_info['id'] . ' ' . $this->l('was canceled') . '.';

                    $status_id = (int)(Configuration::get('PS_OS_CANCELED'));
                    break;
                }
                
                // Refund
                if(in_array($_REQUEST['transactionType'], array('Credit', 'Refund'))) {
					$curr_refund_amount = number_format(floatval(@$_REQUEST['totalAmount']), 2, '.', '');
					$formated_refund = $curr_refund_amount . ' ' .$order_info['currency'];
					
					$msg = 'DMN message: Your Refund was APPROVED. Transaction ID #'
						. $_REQUEST['TransactionID'] .' Refund Amount ' . $formated_refund;

					$status_id = (int)(Configuration::get('PS_OS_REFUND'));
                    break;
                }
                
                $msg = 'The amount has been authorized and captured by ' . SC_GATEWAY_TITLE . '. ';
                
                if($_REQUEST['transactionType'] == 'Auth') {
                    $msg = 'The amount has been authorized and wait to for Settle.';
//                    $status_id = (int)(Configuration::get('PS_OS_PREPARATION'));
//                    $status_id = (int)(Configuration::get('SC_OS_AWAITING_PAIMENT'));
					$status_id = ''; // if we set the id we will have twice this status in the history
                }
                elseif($_REQUEST['transactionType'] == 'Settle') {
                    $msg = 'The amount has been captured by ' . SC_GATEWAY_TITLE . '. ';
                }
                
                $msg .= 'PPP_TransactionID = ' . @$request['PPP_TransactionID']
                    . ", Status = ". $status;
                
                if($_REQUEST['transactionType']) {
                    $msg .= ", TransactionType = ". @$_REQUEST['transactionType'];
                }
                
                $msg .= ', TransactionID = '. @$request['TransactionID'];
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
                
                if($_REQUEST['transactionType']) {
                    $msg .= ", TransactionType = " . $_REQUEST['transactionType'];
                }

                $msg .= ', TransactionID = ' . @$request['TransactionID'];
                
                // Void, do not change status
                if($_REQUEST['transactionType'] == 'Void') {
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
                if(in_array($_REQUEST['transactionType'], array('Credit', 'Refund'))) {
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
//                $status_id = (int)(Configuration::get('PS_OS_PREPARATION'));
                $status_id = (int)(Configuration::get('SC_OS_AWAITING_PAIMENT'));
                
                if ($order_info['current_state'] == 2 || $order_info['current_state'] == 3) {
                    $status_id = $order_info['current_state'];
                    break;
                }
                
                $msg = 'Payment is still pending, PPP_TransactionID '
                    . @$request['PPP_TransactionID'] . ", Status = " . $status;

                if(@$_REQUEST['transactionType']) {
                    $msg .= ", TransactionType = " . @$_REQUEST['transactionType'];
                }

                $msg .= ', TransactionID = ' . @$request['TransactionID'];
                
                // add one more message
                $message->private = true;
                $message->message = SC_GATEWAY_TITLE . $this->l(' payment status is pending Unique Id: ')
                        .@$request['PPP_TransactionID'];
                $message->add();
                
                break;
                
            default:
                SC_CLASS::create_log($status, 'Unexisting status: ');
        }
        
        // save order history
        if(!empty($status_id)) {
			SC_CLASS::create_log($status_id, 'change order status');
			
            $history = new OrderHistory();
            $history->id_order = (int)$order_info['id'];
            $history->changeIdOrderState($status_id, (int)($order_info['id']), !$order_info['has_invoice']);
            $history->add(true);
        }
        
        // save order message
        $message->private = true;
        $message->message = $msg;
        $message->add();
		
		SC_CLASS::create_log('Change_order_status() end.');
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
            SC_CLASS::create_log($e->getMessage(), 'checkAdvancedCheckSum Exception: ');
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
            SC_CLASS::create_log($cart, '$cart: ');
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
            SC_CLASS::create_log(Module::getPaymentModules(), 'This payment method is not available: ');
            Tools::redirect($this->context->link
                ->getModuleLink('safecharge', 'payment', array('prestaShopAction' => 'showError')));
        }

        $customer = new Customer($cart->id_customer);
        
        if (!Validate::isLoadedObject($customer)) {
            SC_CLASS::create_log($customer, '$customer: ');
            Tools::redirect($this->context->link->getPageLink('order'));
        }
        
        return $customer;
    }
    
}
