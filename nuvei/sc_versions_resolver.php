<?php

/**
 * class SafeChargeVersionResolver
 * 
 * Try to resolve different versions problem in the plugin here
 * 
 * 2018-09
 * @author Nuvei
 */
class SafeChargeVersionResolver
{
    public static function set_tab()
    {
        if (version_compare(_PS_VERSION_, '1.4.0', '<')){
            return 'Payment';
        }
        else {
            return 'payments_gateways';
        }
    }
    
    public static function get_payment_l($smarty, $link, $name)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            return $link->getModuleLink('safecharge', 'payment');
        }
        
        return 'modules/'.$name.'/payment.php';
    }
    
    public static function set_order_status($order_id, $status)
    {
        $order = new Order((int)$order_id);
        
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $history = new OrderHistory();
            $history->id_order = (int)($order_id);
            $history->id_order_state = (int)($status);
            $history->changeIdOrderState((int)($status), (int)($order_id));
            $history->add(true);
        }
        else {
			if ( (int)$status == 3 && $order->getCurrentState() == 2 ){
				echo 'skip updating with Pending when old status is paid';
			}
			else{
				$order->setCurrentState((int)$status);
			}
        }
    }
}