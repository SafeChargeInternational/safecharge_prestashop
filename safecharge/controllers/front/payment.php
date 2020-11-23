<?php

/**
 * @author Nuvei
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
        
        if(
            !Configuration::get('SC_MERCHANT_ID')
            || !Configuration::get('SC_MERCHANT_SITE_ID')
            || !Configuration::get('SC_SECRET_KEY')
            || !Configuration::get('SC_CREATE_LOGS')
        ) {
            $this->module->createLog('Plugin is not active or missing Merchant mandatory data!');
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
            $this->module->prepareOrderData(true, true);
            return;
        }
		
		if(Tools::getValue('prestaShopAction', false) == 'deleteUpo') {
            $this->deleteUpo();
            return;
        }
		
		if(Tools::getValue('prestaShopAction', false) == 'beforeSuccess') {
            $this->beforeSuccess();
            return;
        }
        
        $this->processOrder();
    }
    
    private function processOrder()
    {
		$this->module->createLog(@$_REQUEST, 'processOrder() params');
		
        try {
			$sc_payment_method	= Tools::getValue('sc_payment_method', '');
			$cart				= $this->context->cart;
			
			// in case user go to confirm-order page too late
			if(empty($cart->id)) {
				// if there is Transaction ID we can check for existing Order
				if(is_numeric(Tools::getValue('sc_transaction_id'))) {
					$query = "SELECT id_order, id_cart, secure_key "
						. "FROM " . _DB_PREFIX_ . "order_payment "
						. "LEFT JOIN " . _DB_PREFIX_ . "orders "
						. "ON order_reference = reference "
						. "WHERE transaction_id = '" . Tools::getValue('sc_transaction_id') . "'";
					
					$order_data = Db::getInstance()->getRow($query);
					
					$this->module->createLog($query, 'processOrder() $query');
					$this->module->createLog($order_data, 'processOrder() $order_data');
					
					// redirect to success
					if(!empty($order_data)) {
						Tools::redirect($this->context->link->getPageLink(
							'order-confirmation',
							null,
							null,
							array(
								'id_cart'   => $order_data['id_cart'],
								'id_module' => (int)$this->module->id,
								'id_order'  => $order_data['id_order'],
								'key'       => $order_data['secure_key'],
							)
						));
						exit;
					}
				}
				
				$this->module->createLog(@$_REQUEST, 'processOrder() Error - Cart ID is empty.');
				Tools::redirect($this->context->link->getModuleLink(
					'safecharge',
					'payment',
					array(
						'prestaShopAction' => 'showError',
					)
				));
			}
			
			$customer			= $this->validate($cart);

			$error_url			= $this->context->link->getModuleLink(
					'safecharge',
					'payment',
					array(
						'prestaShopAction'	=> 'showError',
						'id_cart'			=> (int)$cart->id,
					)
				);

			$success_url		= $this->context->link->getPageLink(
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

			if(empty($sc_payment_method)) {
				$this->module->createLog(@$_REQUEST, 'REST API Order, but parameter sc_payment_method is empty.');
				Tools::redirect($error_url);
			}
			
			$payment_method = str_replace('apmgw_', '', $sc_payment_method);
			if(empty($payment_method) || is_numeric($payment_method)) {
				$payment_method = str_replace('apmgw_', '', Tools::getValue('sc_upo_name', ''));
			}
			
            # prepare Order data
            $order_time     = date('YmdHis', time());
            $is_user_logged = (bool)$this->context->customer->isLogged();
            $test_mode      = Configuration::get('SC_TEST_MODE');
			
			$total_amount	= number_format($cart->getOrderTotal(), 2, '.', '');
			
			// additional check for existing Order by the Card ID
			$query = "SELECT id_order "
				. "FROM " . _DB_PREFIX_ . "orders "
				. "WHERE id_cart = " . intval($cart->id);

			$order_data = Db::getInstance()->getRow($query);
			
			if(!empty($order_data)) {
				Tools::redirect($success_url);
			}
			// additional check for existing Order by the Card ID
			
			# 1. when use WebSDK
			if(Tools::getValue('sc_transaction_id', false)) {
				// save order
				$res = $this->module->validateOrder(
					(int)$cart->id
					,Configuration::get('SC_OS_AWAITING_PAIMENT') // the status
					,$total_amount
					,$this->module->displayName . ' - ' . $payment_method // payment_method
					,'' // message
					,array(
						'transaction_id' => Tools::getValue('sc_transaction_id', false)
					) // extra_vars
					,null // currency_special
					,false // dont_touch_amount
					,$this->context->cart->secure_key // secure_key
				);

				if(!$res) {
					$this->module->createLog(@$_REQUEST, 'processOrder() Order was not validated');
					Tools::redirect(Tools::redirect($error_url));
				}
				
				$this->module->createLog(
					Tools::getValue('sc_transaction_id'),
					'processOreder() - the webSDK Order was saved.'
				);
				
				$this->updateCustomPaymentFields($this->module->currentOrder);
				
				Tools::redirect($success_url);
			}
			# 1. when use WebSDK END
			
			######################
			
			// in case there is no sc_transaction_id, but the order is CC, redirect to error page
			if(Tools::getValue('sc_payment_method', false) == 'cc_card') {
				$this->module->createLog(
					@$_REQUEST,
					'processOrder() Error - this is payment with CC, but there is no transaction ID.'
				);
				Tools::redirect(Tools::redirect($error_url));
			}
			
			######################
			
			# 2. when use REST
			$save_order_after_apm = intval(Configuration::get('NUVEI_SAVE_ORDER_AFTER_APM_PAYMENT'));
			
			// modify success page
			if($save_order_after_apm == 1) {
				$success_url = $this->context->link
					->getModuleLink(
						'safecharge',
						'payment',
						array(
							'prestaShopAction'	=> 'beforeSuccess',
							'id_cart'			=> (int)$cart->id,
							'id_order'			=> $this->module->currentOrder,
							'key'				=> $customer->secure_key,
							'amount'			=> $total_amount,
							'payment_method'	=> $sc_payment_method,
							'upo_name'			=> Tools::getValue('sc_upo_name', '')
						)
					);
			}
			
            $pending_url	= $success_url;
			$back_url       = $this->context->link->getPageLink('order');
			$notify_url     = $this->module->getNotifyUrl();
			
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
            // get order data END
		
			#########
		
			$params = array(
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
				'deviceDetails'     => SC_CLASS::get_device_details($this->module->version),
				'languageCode'      => substr($this->context->language->locale, 0, 2),
				'webMasterId'       => SC_PRESTA_SHOP . _PS_VERSION_,
			);
			
			$params['userDetails'] = $params['billingAddress'];
			
			$params['items'][0] = array(
				'name'      => $cart->id,
				'price'     => $params['amount'],
				'quantity'  => 1
			);

			$params['checksum'] = hash(
				Configuration::get('SC_HASH_TYPE'),
				$params['merchantId']
					. $params['merchantSiteId']
					. $params['clientRequestId']
					. $params['amount']
					. $params['currency']
					. $params['timeStamp']
					. Configuration::get('SC_SECRET_KEY')
			);
			
			// UPO
			if(is_numeric($sc_payment_method)) {
				$endpoint_url = $test_mode == 'no' ? SC_LIVE_PAYMENT_NEW_URL : SC_TEST_PAYMENT_NEW_URL;
				$params['paymentOption']['userPaymentOptionId'] = $sc_payment_method;
			}
			// APM
			else {
				$endpoint_url = $test_mode == 'no' ? SC_LIVE_PAYMENT_URL : SC_TEST_PAYMENT_URL;
				$params['paymentMethod'] = $sc_payment_method;
				
				if(isset($_POST[$sc_payment_method])) {
					$params['userAccountDetails'] = $_POST[$sc_payment_method];
				}
			}
			
			$resp		= $this->module->callRestApi($endpoint_url, $params);
			$req_status	= $this->getRequestStatus($resp);
		}
		catch(Exception $e) {
			$this->module->createLog($e->getMessage(), 'processOrder() Exception:');
			
			$this->module->createLog(
				$this->context->link->getModuleLink(
					'safecharge', 
					'payment', 
					array('prestaShopAction' => 'showError')
				),
				'Exception URL:'
			);
			
			$this->module->createLog($this->context->cart, 'processOrder() Exception cart:');
			
			Tools::redirect(
				$this->context->link->getModuleLink(
					'safecharge', 
					'payment', 
					array('prestaShopAction' => 'showError')
				)
			);
		}

		if(
			!$resp
			|| $req_status == 'ERROR'
			|| @$resp['message'] == 'ERROR'
			|| in_array(@$resp['transactionStatus'], array('DECLINED', 'ERROR'))
		) {
			Tools::redirect($error_url);
		}
		
		$final_url = $success_url;
		
		if($save_order_after_apm == 0) {
			$res = $this->module->validateOrder(
				(int)$cart->id
				,Configuration::get('SC_OS_AWAITING_PAIMENT') // the status
				,$total_amount
				,$this->module->displayName . ' - ' . $payment_method // payment_method
				,'' // message
				,array() // extra_vars
				,null // currency_special
				,false // dont_touch_amount
				,$this->context->cart->secure_key // secure_key
			);

			if(!$res) {
				$this->module->createLog(@$_REQUEST, 'Order was not validated');
				Tools::redirect($error_url);
			}
			
			$this->module->createLog(@$_REQUEST, 'processOreder() - the APM/UPO Order was saved.');
		}

		if($req_status == 'SUCCESS') {
			// APM
			if(!empty($resp['redirectURL'])) {
				$this->module->createLog($resp['redirectURL'], 'processOrder() redirectURL:');
				$final_url = $resp['redirectURL'];
			}
			// UPO
			elseif(!empty($resp['paymentOption']['redirectUrl'])) {
				$this->module->createLog($resp['paymentOption']['redirectUrl'], 'redirectURL:');
				$final_url = $resp['paymentOption']['redirectUrl'];
			}
		}

		Tools::redirect($final_url);
    }
	
	/**
     * Function scOrderError
     * Shows a message when there is an error with the order
     */
    private function scOrderError()
    {
		$cart_id	= Tools::getValue('id_cart');
		$order_id	= Order::getOrderByCartId((int) $cart_id);
		$order_info = new Order($order_id);

		// in case the user owns the order for this cart id, and the order status
		// is canceled, redirect directly to Reorder Page
		if(
			(int) $this->context->customer->id == (int) $order_info->id_customer
			&& (int) $order_info->current_state == (int) Configuration::get('PS_OS_CANCELED')
		) {
			$url = $this->context->link->getPageLink(
				'order',
				null,
				null,
				array(
					'submitReorder'	=> '',
					'id_order'		=> (int) $order_id
				)
			);

			Tools::redirect($url);
		}
		
        $this->setTemplate('module:safecharge/views/templates/front/order_error.tpl');
    }
	
    /**
     * Function scGetDMN
     * 
     * IMPORTANT - with the DMN we get CartID, NOT OrderID
     */
    private function scGetDMN()
    {
        $this->module->createLog(@$_REQUEST, 'DMN request:');
		
		if(Tools::getValue('sc_stop_dmn', 0) == 1) {
			$this->module->createLog(
				http_build_query(@$_REQUEST),
				'DMN report: Manually stopped process.'
			);
            
			echo 'DMN report: Manually stopped process.';
            exit;
		}
        
        if(!$this->checkAdvancedCheckSum()) {
            $this->module->createLog(
				Tools::getValue('TransactionID'),
				'DMN Error: Advanced checsum error. The process ends here.'
			);
            
			echo 'DMN Error: Advanced checsum error. The process ends here.';
            exit;
        }
		
		if(empty(Tools::getValue('transactionType', ''))) {
			$this->module->createLog('DMN Error: transactionType is empty.');
			
			echo 'DMN Error: transactionType is empty.';
            exit;
		}
		
		if(empty(Tools::getValue('TransactionID', '')) || ! is_numeric(Tools::getValue('TransactionID', ''))) {
			$this->module->createLog('DMN Error: TransactionID is empty or not numeric.');
			
			echo 'DMN Error: TransactionID is empty or not numeric.';
            exit;
		}
		
        $req_status = $this->getRequestStatus();
        
        # Sale and Auth
        if(
			Tools::getValue('invoice_id') !== false
            && in_array(Tools::getValue('transactionType'), array('Sale', 'Auth'))
        ) {
			// REST and WebSDK
			$this->module->createLog(Tools::getValue('TransactionID'), 'DMN Report - REST sale.');
			
			if(!Tools::getValue('merchant_unique_id', false)) {
				$this->module->createLog(
					Tools::getValue('TransactionID'),
					'DMN Error - merchant_unique_id is empty!'
				);
				
				echo 'DMN Error - merchant_unique_id is empty!';
				exit;
			}
                
			try {
				$tries		= 0;
				$order_id	= false;
				
                do {
                    $tries++;
                    $order_id = Order::getIdByCartId(Tools::getValue('merchant_unique_id', 0));

                    if(!$order_id) {
						$this->module->createLog(
							Tools::getValue('TransactionID'),
							'DMN Report - the DMN wait for the order.'
						);
						
                        sleep(10);
                    }
					else {
						$order_info	= new Order($order_id);
						
						// check for slow Prestashop slow saving process
						if(empty($order_info->current_state)) {
							$this->module->createLog(
								Tools::getValue('TransactionID'),
								'DMN Error - current order state is 0. Wait for 10 seconds.'
							);
							
							sleep(10);
						}
					}
                }
				while($tries <= 6 and (!$order_id || empty($order_info->current_state)));
                
                if(!$order_id) {
					// do not create order for Declined transaction
					if(strtolower($this->getRequestStatus()) != 'approved') {
						$this->module->createLog(
							Tools::getValue('TransactionID'),
							'DMN Error - Not Approved DMN for not existing order - stop process.'
						);
						echo 'DMN Error - Not Approved DMN for not existing order - stop process.';
						exit;
					}
					
					// Approved Transaction - continue porocess
					$payment_method = str_replace('apmgw_', '', Tools::getValue('payment_method', ''));
					
					if(empty($payment_method) || is_numeric($payment_method)) {
						$payment_method = str_replace('apmgw_', '', Tools::getValue('sc_upo_name', ''));
					}
					
                    SC_CLASS::create_log(
						Tools::getValue('TransactionID'),
						'The DMN didn\'t wait for the Order creation. Try to save order by the DMN.',
						$this->module->version
					);
					
					// try to create Order here
					try {
						$res = $this->module->validateOrder(
							(int) Tools::getValue('merchant_unique_id')
							,Configuration::get('SC_OS_AWAITING_PAIMENT') // the status
							,floatval(Tools::getValue('totalAmount', 0))
							,$this->module->displayName . ' - ' . $payment_method // payment_method
							,'' // message
							,array(
								'transaction_id' => Tools::getValue('TransactionID', false)
							) // extra_vars
							,null // currency_special
							,false // dont_touch_amount
							,Tools::getValue('customField1', '') // secure_key
						);
						
						if(!$res) {
							SC_CLASS::create_log(
								Tools::getValue('TransactionID'),
								'DMN Error - Order was not validated',
								$this->module->version
							);
							echo 'DMN Error - Order was not validated';
							exit;
						}
						
						SC_CLASS::create_log(
							Tools::getValue('TransactionID'),
							'scGetDMN() - the Order was saved.', 
							$this->module->version
						);
					}
					catch(Exception $e) {
						SC_CLASS::create_log(
							[Tools::getValue('TransactionID'), $e->getMessage()],
							'DMN Exception',
							$this->module->version
						);
						echo 'DMN Exception when try to create an Order by DMN data.';
						exit;
					}
					
					SC_CLASS::create_log(
						Tools::getValue('TransactionID'),
						'DMN Report - An Order was made.',
						$this->module->version
					);
					$order_id = $this->module->currentOrder;
                }
				
				if($this->module->name != $order_info->module) {
					SC_CLASS::create_log(
						[
							'TransactionID' => Tools::getValue('TransactionID'),
							'order module' => $order_info->module
						],
						'DMN Error - the Order do not belongs to the ' . $this->module->name,
						$this->module->version
					);
					
					echo 'DMN Error - the Order do not belongs to the ' . $this->module->name;
					exit;
				}
				
				// check for transaction Id after sdk Order
				$payment		= new OrderPaymentCore();
				$order_payments	= $payment->getByOrderReference($order_info->reference);
				$insert_data	= true; // false for update
				
				if(!empty($order_payments) && is_array($order_payments)) {
					$last_payment = end($order_payments);
					
					if(
						'' != $last_payment->transaction_id
						&& $last_payment->transaction_id != Tools::getValue('TransactionID', 'int')
					) {
						SC_CLASS::create_log(
							array(
								'DMN TransactionID' => Tools::getValue('TransactionID', 'int'),
								'Last Payment transaction_id' => $last_payment->transaction_id
							),
							'DMN Error - DMN TransactionID does not mutch Last Payment transaction_id',
							$this->module->version
						);
						
						echo 'DMN Error - DMN TransactionID does not mutch Last Payment transaction_id';
						exit;
					}
					
					$insert_data = false;
				}
				// check for transaction Id after sdk Order END
				
				// wrong amount check
				$order_amount	= round(floatval($order_info->total_paid), 2);
				$dmn_amount		= round(Tools::getValue('totalAmount', 0), 2);
				
				if($order_amount != $dmn_amount) {
					$this->module->createLog(
						array(
							'DMN totalAmount' => $dmn_amount,
							'Order Amount' => $order_amount
						),
						'DMN Error - DMN totalAmount does not mutch Order Amount'
					);
				}
				// wrong amount check
				
				// check for previous DMN data
				$sc_data = Db::getInstance()->getRow(
					'SELECT * FROM safecharge_order_data '
					. 'WHERE order_id = ' . $order_id
				);
				
				SC_CLASS::create_log($sc_data, 'DMN Report - previous sc_data', $this->module->version);
				
				// there is prevous DMN data
				if(!empty($sc_data) && 'declined' == strtolower($req_status)) {
					SC_CLASS::create_log('DMN Error - Declined DMN for already Approved Order. Stop process here.', '', $this->module->version);
					
					echo 'DMN Error - Declined DMN for already Approved Order. Process Stops here.';
                    exit;
				}
				// check for previous DMN data END
				
                $this->updateCustomPaymentFields($order_id, $insert_data);
				
                if(
					intval($order_info->current_state) != intval(Configuration::get('PS_OS_PAYMENT'))
					&& intval($order_info->current_state) != intval(Configuration::get('PS_OS_ERROR'))
				) {
                    $this->changeOrderStatus(array(
                            'id'            => $order_id,
                            'current_state' => $order_info->current_state,
                            'has_invoice'	=> $order_info->hasInvoice(),
                            'total_paid'	=> $order_info->total_paid,
                            'id_customer'	=> $order_info->id_customer,
                            'reference'		=> $order_info->reference,
                        )
                        ,$req_status
                    );
                }
            }
            catch (Exception $ex) {
                SC_CLASS::create_log($ex->getMessage(), 'Sale DMN Exception:', $this->module->version);
                SC_CLASS::create_log($ex->getTrace(), '', $this->module->version);
                
				echo 'DMN Exception: ' . $ex->getMessage();
                exit;
            }
            
            echo 'DMN received.';
            exit;
        }
        
        # Refund
        if(
            in_array(Tools::getValue('transactionType'), array('Credit', 'Refund'))
            && !empty($req_status)
            && !empty(Tools::getValue('relatedTransactionId'))
        ) {
            SC_CLASS::create_log('PrestaShop Refund DMN.', '', $this->module->version);
            
			$order_info	= $this->getOrder();
			
            try {
                $currency = new Currency((int)$order_info->id_currency);
                
                $this->changeOrderStatus(array(
                        'id'            => $order_info->id,
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
                SC_CLASS::create_log($e->getMessage(), 'Refund DMN exception:', $this->module->version);
                echo 'DMN Exception: ' . $ex->getMessage();
                exit;
            }
        }
        
        # Void, Settle
        if(in_array(Tools::getValue('transactionType'), array('Void', 'Settle'))) {
            SC_CLASS::create_log(Tools::getValue('transactionType'), 'Void/Settle transactionType:', $this->module->version);
            
			$order_info = $this->getOrder();
			
            try {
                if(Tools::getValue('transactionType') == 'Settle') {
                    $this->updateCustomPaymentFields($order_info->id, false);
                }
                
                $this->changeOrderStatus(
                    array(
                        'id'            => $order_info->id,
                        'current_state' => $order_info->current_state,
						'has_invoice'	=> $order_info->hasInvoice(),
                    )
                    ,$req_status
                );
            }
            catch (Exception $ex) {
                SC_CLASS::create_log($ex->getMessage(), 'scGetDMN() Void/Settle Exception:', $this->module->version);
            }
            
            echo 'DMN received.';
            exit;
        }
        
        echo 'DMN received, but not recognized.';
        exit;
    }
	
	/**
	 * Function getOrder
	 * Get the Order by prestaShopOrderID parameter
	 * 
	 * @return \Order
	 */
	private function getOrder() {
		try {
			if(is_numeric(Tools::getValue('prestaShopOrderID'))) {
				$order_id = Tools::getValue('prestaShopOrderID');
			}
			else {
				$sc_data = Db::getInstance()->getRow(
					'SELECT * FROM safecharge_order_data '
					. 'WHERE related_transaction_id = "' . Tools::getValue('relatedTransactionId') .'"'
				);

				if(empty($sc_data) || empty($sc_data['order_id'])) {
					echo 'DMN Error: we can not find Order connected with incoming relatedTransactionId.';
					exit;
				}

				$order_id = $sc_data['order_id'];
			}

			$order_info = new Order($order_id);
			
			if(empty($order_info)) {
				SC_CLASS::create_log('DMN Refund Error - There is no Order for Order ID ' . $order_id, '', $this->module->version);

				echo 'DMN Refund Error - There is no Order for Order ID ' . $order_id;
				exit;
			}

			if($this->module->name != $order_info->module) {
				SC_CLASS::create_log('DMN Error - the order do not belongs to the ' . $this->module->name, '', $this->module->version);

				echo 'DMN Error - the order do not belongs to the ' . $this->module->name;
				exit;
			}
			
			return $order_info;
		}
		catch (Excception $e) {
			SC_CLASS::create_log($e->getMessage(), 'getOrder() exception:', $this->module->version);
			echo 'getOrder() Exception: ' . $ex->getMessage();
			exit;
		}
	}
    
    /**
     * Function changeOrderStatus
     * Change the status of the order.
     * 
     * @param array $order_info
     * @param string $status
     * @param array $res_args - we must use $res_args instead $_REQUEST, if not empty
     */
    private function changeOrderStatus($order_info, $status, $res_args = array())
    {
        SC_CLASS::create_log(
            'Order ' . $order_info['id'] .' has Status: ' . $status,
            'changeOrderStatus()',
			$this->module->version
        );
		
        $request = @$_REQUEST;
        if(!empty($res_args)) {
            $request = $res_args;
        }
        
        $msg				= '';
		$is_msg_private		= true;
        $message			= new MessageCore();
        $message->id_order	= $order_info['id'];
		$error_order_status	= '';
        
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
					$status_id = ''; // if we set the id we will have twice this status in the history
                }
                elseif($_REQUEST['transactionType'] == 'Settle') {
                    $msg = 'The amount has been captured by ' . SC_GATEWAY_TITLE . '. ';
                }
				// compare DMN amount and Order amount
				elseif(Tools::getValue('transactionType', false) === 'Sale') {
					$dmn_amount		= round(floatval(Tools::getValue('totalAmount', -1)), 2);
					$order_amount	= round(floatval($order_info['total_paid']), 2);
					
					if($dmn_amount !== $order_amount) {
						$error_order_status = (int)(Configuration::get('PS_OS_ERROR'));
					}
				}
                
                $msg .= ' PPP_TransactionID = ' . @$request['PPP_TransactionID']
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
                
                if($request['transactionType']) {
                    $msg .= ", TransactionType = " . $request['transactionType'];
                }

                $msg .= ', TransactionID = ' . @$request['TransactionID'];
                
                // Void, do not change status
                if($request['transactionType'] == 'Void') {
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
                if(in_array($request['transactionType'], array('Credit', 'Refund'))) {
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
                
				// Sale or Auth
				if(in_array($request['transactionType'], array('Sale', 'Auth'))) {
					$status_id = (int)(Configuration::get('PS_OS_CANCELED'));
				}
				
                break;

            case 'PENDING':
//                $status_id = (int)(Configuration::get('SC_OS_AWAITING_PAIMENT'));
                $status_id = '';
                
//                if (
//					$order_info['current_state'] == Configuration::get('PS_OS_PAYMENT')
//					|| $order_info['current_state'] == Configuration::get('PS_OS_PREPARATION')
//				) {
//                    $status_id = $order_info['current_state'];
//                    break;
//                }
                
//                $msg = 'Payment is still pending, PPP_TransactionID '
//                    . @$request['PPP_TransactionID'] . ", Status = " . $status;

                if(Tools::getValue('transactionType')) {
					$msg = Tools::getValue('transactionType') . ' is pending, '
						. 'PPP_TransactionID ' . @$request['PPP_TransactionID']
						. ', TransactionID = ' . @$request['TransactionID'];
					
//                    $msg .= ", TransactionType = " . Tools::getValue('transactionType');
                }

//                $msg .= ', TransactionID = ' . @$request['TransactionID'];
                
                // add one more message
//                $message->private = true;
//                $message->message = SC_GATEWAY_TITLE . $this->l(' payment status is pending Unique Id: ')
//                        .@$request['PPP_TransactionID'];
//                $message->add();
                
                break;
                
            default:
                SC_CLASS::create_log($status, 'Unexisting status:', $this->module->version);
        }
        
        // save order history
        if(!empty($status_id)) {
			SC_CLASS::create_log('changeOrderStatus() - Order status will be set to ' . $status_id, '', $this->module->version);
			
            $history = new OrderHistory();
            $history->id_order = (int)$order_info['id'];
            $history->changeIdOrderState($status_id, (int)($order_info['id']), !$order_info['has_invoice']);
            $history->add(true);
			
			// in case ot Payment error
			if(!empty($error_order_status)) {
				// add Error status
				$history->changeIdOrderState($error_order_status, (int)($order_info['id']));
				$history->add(true);
				
				// get and manipulate Order Payment
				$payment = new OrderPaymentCore();
				$order_payments	= $payment->getByOrderReference($order_info['reference']);
				
				if(is_array($order_payments) && !empty($order_payments)) {
					$order_payment	= end($order_payments);

					if(round($order_payment->amount, 2) != $dmn_amount) {
						Db::getInstance()->update(
							'order_payment',
							array('amount' => $dmn_amount),
							"order_reference = '" . $order_info['reference'] . "' AND amount = "
								. $order_amount . ' AND  	id_order_payment = ' . $order_payment->id
						);
					}
				}
			}
        }
        
        // save order message
		if(!empty($msg)) {
			$message->private = $is_msg_private;
			$message->message = $msg;
			$message->add();
		}
		
		$this->module->createLog(
			['order id' => $order_info['id'], 'status id ' => $status_id],
			'changeOrderStatus() END.'
		);
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
                Configuration::get('SC_SECRET_KEY') . Tools::getValue('totalAmount')
                    . Tools::getValue('currency') . Tools::getValue('responseTimeStamp')
                    . Tools::getValue('PPP_TransactionID') . $this->getRequestStatus()
                    . Tools::getValue('productId')
            );
        }
        catch(Exception $e) {
            SC_CLASS::create_log($e->getMessage(), 'checkAdvancedCheckSum Exception: ', $this->module->version);
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
	 * 
	 * @return bool
     */
    private function updateCustomPaymentFields($order_id, $insert = true)
    {
		$trans_id = !empty(Tools::getValue('sc_transaction_id', ''))
			? Tools::getValue('sc_transaction_id', '') : Tools::getValue('TransactionID', '');
		
        $data = array('order_id' => $order_id);
		
		// do not update empty values
		if(!empty($auth = Tools::getValue('AuthCode', ''))) {
			$data['auth_code'] = intval(Tools::getValue('AuthCode', ''));
		}
		if(!empty($trans_id)) {
			$data['related_transaction_id'] = intval($trans_id);
		}
		if(!empty($tr_type = Tools::getValue('transactionType', ''))) {
			$data['resp_transaction_type'] = filter_var($tr_type, FILTER_SANITIZE_STRING);
		}
		if(!empty($pm = Tools::getValue('payment_method', ''))) {
			$data['payment_method'] = filter_var($pm, FILTER_SANITIZE_STRING);
		}
		// do not update empty values END
		
		$fields_strings = implode(", ", array_keys($data));
		$values_string	= "'" . implode("', '", $data) . "'";
		$update_array	= array();
		
		foreach($data as $field => $val) {
			$update_array[] = $field . "='" . $val . "'";
		}
		
		$query = "INSERT INTO safecharge_order_data ({$fields_strings}) VALUES ({$values_string}) "
			. "ON DUPLICATE KEY UPDATE " . implode(", ", $update_array) . ";";
		
		try {
			$res = Db::getInstance()->execute($query);
		}
		catch (Exception $e) {
			SC_CLASS::create_log($e->getMessage(), 'updateCustomPaymentFields Exception', $this->module->version);
			SC_CLASS::create_log($query, '$query', $this->module->version);
			return false;
		}
		
		if(!$res) {
			SC_CLASS::create_log(Db::getInstance()->getMsgError(), 'updateCustomPaymentFields response error', $this->module->version);
			SC_CLASS::create_log($query, '$query', $this->module->version);
		}
		
		return $res;
    }
	
	private function deleteUpo()
	{
		$upo_id = Tools::getValue('upoId', false);
		
		if(!$upo_id) {
			echo json_encode(array(
				'status' => 'error',
				'message' => $this->l('Error - the UPO ID is missing.')
			));
			
			exit;
		}
		
		if(!(bool)$this->context->customer->isLogged()) {
			SC_CLASS::create_log('Delete UPO Error - the user is not logged.', '', $this->module->version);
			
            echo json_encode(array(
				'status' => 'error',
				'message' => $this->l('Error - the user is not logged.')
			));
			
			exit;
		}
		
        if (!Validate::isLoadedObject($this->context->customer)) {
            SC_CLASS::create_log('Delete UPO Error - we cannot validate the Customer.', '', $this->module->version);
			
            echo json_encode(array(
				'status' => 'error',
				'message' => $this->l('Error - we cannot validate the Customer.')
			));
			
			exit;
        }
		
		if(empty($this->context->customer->email)) {
			SC_CLASS::create_log('Delete UPO Error - Customer email is empty.', '', $this->module->version);
			
            echo json_encode(array(
				'status' => 'error',
				'message' => $this->l('Error - Customer email is empty.')
			));
			
			exit;
		}
		
		$timeStamp = date('YmdHis', time());
		
		$params = array(
			'merchantId'			=> Configuration::get('SC_MERCHANT_ID'),
			'merchantSiteId'		=> Configuration::get('SC_MERCHANT_SITE_ID'),
			'userTokenId'			=> $this->context->customer->email,
			'clientRequestId'		=> $timeStamp .'_'. uniqid(),
			'userPaymentOptionId'	=> $upo_id,
			'timeStamp'	=>			$timeStamp,
		);
		
		$params['checksum'] = hash(
			Configuration::get('SC_HASH_TYPE'),
			implode('', $params) . Configuration::get('SC_SECRET_KEY')
		);
		
		$resp = $this->module->callRestApi(
			Configuration::get('SC_TEST_MODE') == 'no' ? SC_LIVE_DELETE_UPO_URL : SC_TEST_DELETE_UPO_URL,
			$params
		);
		
		if(empty($resp['status']) || $resp['status'] != 'SUCCESS') {
			$msg = !empty($resp['reason']) ? ' - ' . $resp['reason'] : '';
			
            echo json_encode(array(
				'status' => 'error',
				'message' => $this->l('Error') . $msg
			));
			
			exit;
		}
		
		echo json_encode(array('status' => 'success'));
	}
	
	private function beforeSuccess()
	{
		$error_url = $this->context->link->getModuleLink(
			'safecharge',
			'payment',
			array(
				'prestaShopAction'	=> 'showError',
				'id_cart'			=> (int) Tools::getValue('id_cart', 0),
			)
		);
		
		$payment_method = str_replace('apmgw_', '', Tools::getValue('payment_method', ''));
		
		if(empty($payment_method) || is_numeric($payment_method)) {
			$payment_method = str_replace('apmgw_', '', Tools::getValue('upo_name', ''));
		}
		
		// save order
		$res = $this->module->validateOrder(
			(int) Tools::getValue('id_cart', 0)
			,Configuration::get('SC_OS_AWAITING_PAIMENT') // the status
			,Tools::getValue('amount', 0)
			,$this->module->displayName . ' - ' . $payment_method // payment_method
			,'' // message
			,array() // extra_vars
			,null // currency_special
			,false // dont_touch_amount
			,Tools::getValue('key', $this->context->cart->secure_key) // secure_key
		);

		if(!$res) {
			Tools::redirect($error_url);
		}
		
		SC_CLASS::create_log('beforeSuccess() - the Order was saved.', '', $this->module->version);
		
		$success_url = $this->context->link->getPageLink(
			'order-confirmation',
			null,
			null,
			array(
				'id_cart'   => (int) Tools::getValue('id_cart', 0),
				'id_module' => (int)$this->module->id,
				'id_order'  => Tools::getValue('id_order', 0),
				'key'       => Tools::getValue('key', '')
			)
		);
		
		Tools::redirect($success_url);
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
            SC_CLASS::create_log(
				array(
					'$cart->id_customer' => $cart->id_customer,
					'$cart->id_address_delivery' => $cart->id_address_delivery,
					'$cart->id_address_invoice' => $cart->id_address_invoice,
					'$this->module->active' => $this->module->active,
				),
				'Validate error',
				$this->module->version
			);
            
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
            SC_CLASS::create_log(Module::getPaymentModules(), 'This payment method is not available:', $this->module->version);
            
			Tools::redirect($this->context->link->getModuleLink(
				'safecharge',
				'payment',
				array('prestaShopAction' => 'showError')
			));
        }

        $customer = new Customer($cart->id_customer);
        
        if (!Validate::isLoadedObject($customer)) {
            SC_CLASS::create_log($customer, '$customer:', $this->module->version);
            Tools::redirect($this->context->link->getPageLink('order'));
        }
        
        return $customer;
    }
    
}
