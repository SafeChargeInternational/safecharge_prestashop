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
require_once _PS_MODULE_DIR_ . 'safecharge' . DIRECTORY_SEPARATOR . 'SC_REST_API.php';

class AdminSafeChargeAjaxController extends ModuleAdminControllerCore
{
    public function __construct()
    {
        parent::__construct();
        
        if(
            !isset($_POST['scOrder'])
            || !is_numeric($_POST['scOrder'])
            || intval($_POST['scOrder']) <= 0
        ) {
            SC_LOGGER::create_log(@$_POST['scOrder'], 'Missing Order ID: ');
            
            echo json_encode(array(
                'status' => 'error',
                'msg' => 'There is no Order ID'
            ));
            exit;
        }
        
        if(in_array(@$_POST['scAction'], array('settle', 'void'))) {
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
        $order_id = intval($_POST['scOrder']);
        $order_info = new Order($order_id);
        $currency = new Currency($order_info->id_currency);
        
        $sc_data = Db::getInstance()->getRow('SELECT * FROM safecharge_order_data WHERE order_id = ' . $order_id);
        
        $_SESSION['sc_create_logs'] = Configuration::get('SC_CREATE_LOGS');
        
        $time = date('YmdHis', time());
        
        $notify_url = $this->context->link->getModuleLink('safecharge', 'payment', array(
            'prestaShopAction'  => 'getDMN',
            'prestaShopOrderID' => $order_id,
            'create_logs'       => $_SESSION['sc_create_logs'],
        ));
        
        if(Configuration::get('SC_HTTP_NOTIFY') == 'yes') {
            $notify_url = str_repeat('https://', 'http://', $notify_url);
        }
        
        $params = array(
            'merchantId'            => Configuration::get('SC_MERCHANT_ID'),
            'merchantSiteId'        => Configuration::get('SC_MERCHANT_SITE_ID'),
            'clientRequestId'       => $time . '_' . $sc_data['related_transaction_id'],
            'clientUniqueId'        => uniqid(),
            'amount'                => number_format($order_info->total_paid, 2, '.', ''),
            'currency'              => $currency->iso_code,
            'relatedTransactionId'  => $sc_data['related_transaction_id'],
            'authCode'              => $sc_data['auth_code'],
            'urlDetails'            => array('notificationUrl' => $notify_url),
            'timeStamp'             => $time,
            'test'                  => Configuration::get('SC_TEST_MODE'), // need to define the endpoint
        );
        
        if(defined('_PS_VERSION_')) {
            $params['webMasterId'] = 'PrestsShop ' . _PS_VERSION_;
        }
        
        $checksum = hash(
            Configuration::get('SC_HASH_TYPE'),
            Configuration::get('SC_MERCHANT_ID') . Configuration::get('SC_MERCHANT_SITE_ID')
                . $params['clientRequestId'] . $params['clientUniqueId'] . $params['amount']
                . $params['currency'] . $params['relatedTransactionId'] . $params['authCode']
                . $notify_url . $time . Configuration::get('SC_SECRET_KEY')
        );
        
        $params['checksum'] = $checksum;
        
        SC_LOGGER::create_log($params, 'The params for Void/Settle: ');
        
        SC_REST_API::void_and_settle_order($params, @$_POST['scAction'], true);
    }
    
}
