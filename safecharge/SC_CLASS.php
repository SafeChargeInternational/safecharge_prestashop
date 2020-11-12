<?php

/**
 * SC_CLASS Class
 * 
 * @year 2020
 * @author Nuvei
 */
class SC_CLASS
{
	// array details to validate request parameters
    private static $params_validation = array(
        // deviceDetails
        'deviceType' => array(
            'length' => 10,
            'flag'    => FILTER_SANITIZE_STRING
        ),
        'deviceName' => array(
            'length' => 255,
            'flag'    => FILTER_DEFAULT
        ),
        'deviceOS' => array(
            'length' => 255,
            'flag'    => FILTER_DEFAULT
        ),
        'browser' => array(
            'length' => 255,
            'flag'    => FILTER_DEFAULT
        ),
//        'ipAddress' => array(
//            'length' => 15,
//            'flag'    => FILTER_VALIDATE_IP
//        ),
        // deviceDetails END
        
        // userDetails, shippingAddress, billingAddress
        'firstName' => array(
            'length' => 30,
            'flag'    => FILTER_DEFAULT
        ),
        'lastName' => array(
            'length' => 40,
            'flag'    => FILTER_DEFAULT
        ),
        'address' => array(
            'length' => 60,
            'flag'    => FILTER_DEFAULT
        ),
        'cell' => array(
            'length' => 18,
            'flag'    => FILTER_DEFAULT
        ),
        'phone' => array(
            'length' => 18,
            'flag'    => FILTER_DEFAULT
        ),
        'zip' => array(
            'length' => 10,
            'flag'    => FILTER_DEFAULT
        ),
        'city' => array(
            'length' => 30,
            'flag'    => FILTER_DEFAULT
        ),
        'country' => array(
            'length' => 20,
            'flag'    => FILTER_SANITIZE_STRING
        ),
        'state' => array(
            'length' => 2,
            'flag'    => FILTER_SANITIZE_STRING
        ),
        'county' => array(
            'length' => 255,
            'flag'    => FILTER_DEFAULT
        ),
        // userDetails, shippingAddress, billingAddress END
        
        // specific for shippingAddress
        'shippingCounty' => array(
            'length' => 255,
            'flag'    => FILTER_DEFAULT
        ),
        'addressLine2' => array(
            'length' => 50,
            'flag'    => FILTER_DEFAULT
        ),
        'addressLine3' => array(
            'length' => 50,
            'flag'    => FILTER_DEFAULT
        ),
        // specific for shippingAddress END
        
        // urlDetails
        'successUrl' => array(
            'length' => 1000,
            'flag'    => FILTER_VALIDATE_URL
        ),
        'failureUrl' => array(
            'length' => 1000,
            'flag'    => FILTER_VALIDATE_URL
        ),
        'pendingUrl' => array(
            'length' => 1000,
            'flag'    => FILTER_VALIDATE_URL
        ),
        'notificationUrl' => array(
            'length' => 1000,
            'flag'    => FILTER_VALIDATE_URL
        ),
        // urlDetails END
    );
	
	private static $params_validation_email = array(
		'length'	=> 79,
		'flag'		=> FILTER_VALIDATE_EMAIL
	);
	
    /**
     * Function call_rest_api
	 * 
     * Call REST API with cURL post and get response.
     * The URL depends from the case.
     * 
     * @param type $url - API URL
     * @param array $params - parameters
	 * @param string $plugin_version
     * 
     * @return mixed
     */
    public static function call_rest_api($url, $params, $plugin_version = '')
    {
        $resp				= false;
		$plugin_messages	= array();
		
		// get them only if we pass them empty
		if (empty($params['deviceDetails'])) {
			$params['deviceDetails'] = self::get_device_details($plugin_version);
		}
		
		# validate parameters
		// directly check the mails
		if(isset($params['billingAddress']['email'])) {
			if(!filter_var($params['billingAddress']['email'], self::$params_validation_email['flag'])) {
				return array(
					'status' => 'ERROR',
					'message' => 'The parameter Billing Address Email is not valid.'
				);
			}
			
			if(strlen($params['billingAddress']['email']) > self::$params_validation_email['length']) {
				return array(
					'status' => 'ERROR',
					'message' => 'The parameter Billing Address Email must be maximum '
						. self::$params_validation_email['length'] . ' symbols.'
				);
			}
		}
		
		if(isset($params['shippingAddress']['email'])) {
			if(!filter_var($params['shippingAddress']['email'], self::$params_validation_email['flag'])) {
				return array(
					'status' => 'ERROR',
					'message' => 'The parameter Shipping Address Email is not valid.'
				);
			}
			
			if(strlen($params['shippingAddress']['email']) > self::$params_validation_email['length']) {
				return array(
					'status' => 'ERROR',
					'message' => 'The parameter Shipping Address Email must be maximum '
						. self::$params_validation_email['length'] . ' symbols.'
				);
			}
		}
		// directly check the mails END
		
		try {
			foreach ($params as $key1 => $val1) {
				if (!is_array($val1) && !empty($val1) && array_key_exists($key1, self::$params_validation)) {
					$new_val = $val1;

					if (mb_strlen($val1) > self::$params_validation[$key1]['length']) {
						$new_val			= mb_substr($val1, 0, self::$params_validation[$key1]['length']);
						$plugin_messages[]	= 'Limit ' . $key1;
					}

					$params[$key1] = filter_var($new_val, self::$params_validation[$key1]['flag']);
				}
				elseif (is_array($val1) && !empty($val1)) {
					foreach ($val1 as $key2 => $val2) {
						if (!is_array($val2) && !empty($val2) && array_key_exists($key2, self::$params_validation)) {
							$new_val = $val2;

							if (mb_strlen($val2) > self::$params_validation[$key2]['length']) {
								$new_val			= mb_substr($val2, 0, self::$params_validation[$key2]['length']);
								$plugin_messages[]	= 'Limit ' . $key2;
							}

							$params[$key1][$key2] = filter_var($new_val, self::$params_validation[$key2]['flag']);
						}
					}
				}
			}
		}
		catch(Exception $e) {
			$plugin_messages[] = 'Request validation exception: ' . $e->getMessage();
		}
		# validate parameters END
		
        $json_post = json_encode($params);
        
        try {
            $header =  array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json_post),
            );
            
            // create cURL post
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_post);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $resp = curl_exec($ch);
			curl_close($ch);
			
			if (false === $resp) {
				return false;
			}

			$resp_arr = json_decode($resp, true);
			// add the plugin messages
			$resp_arr['plugin messages'] = $plugin_messages;
			
			return $resp_arr;
        }
        catch(Exception $e) {
			return array(
				'status' => 'ERROR',
				'message' => 'Exception ERROR when call REST API: ' . $e->getMessage()
			);
        }
    }
    
    /**
     * Function get_device_details
	 * 
     * Get browser and device based on HTTP_USER_AGENT.
     * The method is based on D3D payment needs.
     * 
	 * @param string $plugin_version
     * @return array $device_details
     */
    public static function get_device_details($plugin_version = '')
    {
        $device_details = array(
			'deviceType'    => 'UNKNOWN', // DESKTOP, SMARTPHONE, TABLET, TV, and UNKNOWN
			'deviceName'    => 'UNKNOWN',
			'deviceOS'      => 'UNKNOWN',
			'browser'       => 'UNKNOWN',
			'ipAddress'     => '0.0.0.0',
		);
		
		if(empty($_SERVER['HTTP_USER_AGENT'])) {
			$device_details['Warning'] = 'User Agent is empty.';
			
			self::create_log($device_details['Warning'], 'Error', $plugin_version);
			return $device_details;
		}
		
		$user_agent = strtolower(filter_var($_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_STRING));
		
		if (empty($user_agent)) {
			$device_details['Warning'] = 'Probably the merchant Server has problems with PHP filter_var function!';
			
			self::create_log($device_details['Warning'], 'Error', $plugin_version);
			return $device_details;
		}
		
		$device_details['deviceName'] = $user_agent;
		
        if (defined('SC_DEVICES_TYPES')) {
			$devs_tps = json_decode(SC_DEVICES_TYPES, true);

			if (is_array($devs_tps) && !empty($devs_tps)) {
				foreach ($devs_tps as $d) {
					if (strstr($user_agent, $d) !== false) {
						if(in_array($d, array('linux', 'windows', 'macintosh'), true)) {
							$device_details['deviceType'] = 'DESKTOP';
						} else if('mobile' === $d) {
							$device_details['deviceType'] = 'SMARTPHONE';
						} else if('tablet' === $d) {
							$device_details['deviceType'] = 'TABLET';
						} else {
							$device_details['deviceType'] = 'TV';
						}

						break;
					}
				}
			}
		}

        if (defined('SC_DEVICES')) {
			$devs = json_decode(SC_DEVICES, true);

			if (is_array($devs) && !empty($devs)) {
				foreach ($devs as $d) {
					if (strstr($user_agent, $d) !== false) {
						$device_details['deviceOS'] = $d;
						break;
					}
				}
			}
		}

		if (defined('SC_BROWSERS')) {
			$brs = json_decode(SC_BROWSERS, true);

			if (is_array($brs) && !empty($brs)) {
				foreach ($brs as $b) {
					if (strstr($user_agent, $b) !== false) {
						$device_details['browser'] = $b;
						break;
					}
				}
			}
		}

        // get ip
		if (!empty($_SERVER['REMOTE_ADDR'])) {
			$ip_address = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
		}
		if (empty($ip_address) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip_address = filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP);
		}
		if (empty($ip_address) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip_address = filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP);
		}
		if (!empty($ip_address)) {
			$device_details['ipAddress'] = (string) $ip_address;
		}
            
        return $device_details;
    }
    
    /**
     * Function create_log
     * 
     * @param mixed $data
     * @param string $title - title for the printed log
	 * @param string $plugin_version - version of the plugin
	 * 
	 * @deprecated
     */
    public static function create_log($data, $title = '', $plugin_version = '')
    {
        // path is different fore each plugin
        $logs_path = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'var'
			.DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
		
		if(!is_dir($logs_path)) {
			return;
		}
        
        if(
            @$_REQUEST['sc_create_logs'] == 'yes' || @$_REQUEST['sc_create_logs'] == 1
            || @$_SESSION['sc_create_logs'] == 'yes' || @$_SESSION['sc_create_logs'] == 1
        ) {
            // same for all plugins
            $d		= $data;
			$string	= '';

            if(is_array($data)) {
                if(!empty($data['userAccountDetails']) && is_array($data['userAccountDetails'])) {
					$data['userAccountDetails'] = 'userAccountDetails details';
                }
                if(!empty($data['userPaymentOption']) && is_array($data['userPaymentOption'])) {
                    $data['userPaymentOption'] = 'userPaymentOption details';
                }
                if(!empty($data['paymentOption']) && is_array($data['paymentOption'])) {
					$data['paymentOption'] = 'paymentOption details';
                }
				
//				if(!empty($data['paymentMethods']) && is_array($data['paymentMethods'])) {
//					$data['paymentMethods'] = json_encode($data['paymentMethods']);
//                }
                
                $d = print_r(json_encode($data), true);
            }
            elseif(is_object($data)) {
                $d = print_r(json_encode($data), true);
            }
            elseif(is_bool($data)) {
                $d = $data ? 'true' : 'false';
            }
			
			if(!empty($plugin_version)) {
				$string .= '[v.' . $plugin_version . '] | ';
			}

            if(!empty($title)) {
                $string .= $title . "\r\n";
            }
            
            $string .= $d . "\r\n\r\n";
            // same for all plugins

            try {
                file_put_contents(
                    $logs_path . 'Nuvei-' . date('Y-m-d', time()) . '.txt',
                    date('H:i:s', time()) . ': ' . $string, FILE_APPEND
                );
            }
            catch (Exception $exc) {}
        }
    }

}
