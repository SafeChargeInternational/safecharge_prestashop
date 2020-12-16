<?php

/* 
 * Put all Constants here.
 * 
 * 2018
 * 
 * @author Nuvei
 */

$sc_test_endpoint_host = 'https://ppp-test.safecharge.com/ppp/api/v1';

// list of devices
define('SC_DEVICES', json_encode(array('iphone', 'ipad', 'android', 'silk', 'blackberry', 'touch', 'linux', 'windows', 'mac')));

// list of browsers
define('SC_BROWSERS', json_encode(array('ucbrowser', 'firefox', 'chrome', 'opera', 'msie', 'edge', 'safari', 'blackberry', 'trident')));

// list of devices types
define('SC_DEVICES_TYPES', json_encode(array('macintosh', 'tablet', 'mobile', 'tv', 'windows', 'linux', 'tv', 'smarttv', 'googletv', 'appletv', 'hbbtv', 'pov_tv', 'netcast.tv', 'bluray')));

define('SC_PRESTA_SHOP', 'PrestaShop ');
define('SC_SOURCE_APPLICATION', '');
define('SC_STOP_DMN', 0);