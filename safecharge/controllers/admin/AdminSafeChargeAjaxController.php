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

class AdminSafeChargeAjaxController extends ModuleAdminControllerCore
{
    public function __construct()
    {
        parent::__construct();
        
        if(
            isset($_POST['scOrder'])
            && is_numeric($_POST['scOrder'])
            && intval($_POST['scOrder']) > 0
            && in_array(@$_POST['scAction'], array('settle', 'void'))
        ) {
            $this->order_void_settle();
        }
        
//        if(@$_POST['scAction'] == 'deleteLogs') {
//            $this->delete_logs();
//        }
		
		if(@$_POST['scAction'] == 'saveOrder') {
			$this->save_order();
		}
            
        exit;
    }
    
    /**
     * Function order_void_settle
     * We use one function for both because the only
     * difference is the endpoint, all parameters are same
     */
    private function order_void_settle()
    {
        SC_CLASS::create_log('Void/Settle');
        
        if(!$_POST['scAction'] or empty($_POST['scAction'])) {
            echo json_encode(array('status' => 0, 'msg' => 'There is no action.'));
            exit;
        }
        
        $order_id   = intval($_POST['scOrder']);
        $order_info = new Order($order_id);
        $currency   = new Currency($order_info->id_currency);
        $sc_data    = Db::getInstance()->getRow('SELECT * FROM safecharge_order_data WHERE order_id = ' . $order_id);
        $time       = date('YmdHis', time());
        $status     = 1; // default status of the response
        
        $_SESSION['sc_create_logs'] = Configuration::get('SC_CREATE_LOGS');
        
        $notify_url = $this->context->link->getModuleLink('safecharge', 'payment', array(
            'prestaShopAction'  => 'getDMN',
            'prestaShopOrderID' => $order_id,
            'create_logs'       => $_SESSION['sc_create_logs'],
        ));
		
        if(
			Configuration::get('SC_HTTP_NOTIFY') == 'yes'
			&& false !== strpos($notify_url, 'https://')
		) {
            $notify_url = str_repeat('https://', 'http://', $notify_url);
        }
        
        $test_mode = Configuration::get('SC_TEST_MODE');
        
        $params = array(
            'merchantId'            => Configuration::get('SC_MERCHANT_ID'),
            'merchantSiteId'        => Configuration::get('SC_MERCHANT_SITE_ID'),
            'clientRequestId'       => $time . '_' . $sc_data['related_transaction_id'],
            'clientUniqueId'        => $order_id,
            'amount'                => number_format($order_info->total_paid, 2, '.', ''),
            'currency'              => $currency->iso_code,
            'relatedTransactionId'  => $sc_data['related_transaction_id'],
            'authCode'              => $sc_data['auth_code'],
            'urlDetails'            => array('notificationUrl' => $notify_url),
            'timeStamp'             => $time,
            'sourceApplication'     => SC_SOURCE_APPLICATION,
        );
        
        if(defined('_PS_VERSION_')) {
            $params['webMasterId'] = SC_PRESTA_SHOP . _PS_VERSION_;
        }
        
        $checksum = hash(
            Configuration::get('SC_HASH_TYPE'),
            Configuration::get('SC_MERCHANT_ID') . Configuration::get('SC_MERCHANT_SITE_ID')
                . $params['clientRequestId'] . $params['clientUniqueId'] . $params['amount']
                . $params['currency'] . $params['relatedTransactionId'] . $params['authCode']
                . $notify_url . $time . Configuration::get('SC_SECRET_KEY')
        );
        
        $params['checksum'] = $checksum;
        
        if($_POST['scAction'] == 'settle') {
            $url = $test_mode == 'no' ? SC_LIVE_SETTLE_URL : SC_TEST_SETTLE_URL;
        }
        elseif($_POST['scAction'] == 'void') {
            $url = $test_mode == 'no' ? SC_LIVE_VOID_URL : SC_TEST_VOID_URL;
        }
        
        $resp = SC_CLASS::call_rest_api($url, $params);
        
        if(
            !$resp || !is_array($resp)
            || @$resp['status'] == 'ERROR'
            || @$resp['transactionStatus'] == 'ERROR'
            || @$resp['transactionStatus'] == 'DECLINED'
        ) {
            $status = 0;
        }
        
        echo json_encode(array('status' => $status, 'data' => $resp));
        exit;
    }
    
	private function save_order()
	{
		if(
			empty($_POST['cart_id'])
			|| empty($_POST['orderStatus'])
			|| empty($_POST['amount'])
			|| empty($_POST['moduleName'])
			|| empty($_POST['customerKey'])
		) {
			echo json_encode(array('status' => 0, 'msg' => 'Missing mandatory data'));
			exit;
		}
		
		$this->module->validateOrder(
			(int)$cart->id
//			,Configuration::get('PS_OS_PREPARATION') // the status
			,Configuration::get('SC_OS_PENDING') // the status
			,$sc_params['amount']
			,$this->module->displayName
		//    ,null
		//    ,null // for the mail
		//    ,(int)$currency->id
		//    ,false
		//    ,$customer->secure_key
		);
		
	}
}
