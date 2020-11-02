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

class AdminSafeChargeAjaxController extends ModuleAdminControllerCore
{
    public function __construct()
    {
        parent::__construct();
        
        if(
            is_numeric(Tools::getValue('scOrder'))
            && intval(Tools::getValue('scOrder')) > 0
            && in_array(Tools::getValue('scAction'), array('settle', 'void'))
        ) {
            $this->order_void_settle();
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
        
        if(empty(Tools::getValue('scAction', ''))) {
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
        
        $notify_url = $this->module->getNotifyUrl();
        $test_mode	= Configuration::get('SC_TEST_MODE');
		$trans_id	= !empty($sc_data['transaction_id']) ? $sc_data['transaction_id'] : $sc_data['related_transaction_id'];
        
        $params = array(
            'merchantId'            => Configuration::get('SC_MERCHANT_ID'),
            'merchantSiteId'        => Configuration::get('SC_MERCHANT_SITE_ID'),
            'clientRequestId'       => $time . '_' . $trans_id,
            'clientUniqueId'        => $order_id,
            'amount'                => number_format($order_info->total_paid, 2, '.', ''),
            'currency'              => $currency->iso_code,
            'relatedTransactionId'  => $trans_id,
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
	
}
