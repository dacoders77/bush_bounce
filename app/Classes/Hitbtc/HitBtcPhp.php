<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 9/15/2018
 * Time: 3:32 PM
 */

namespace App\Classes\Hitbtc;


class HitBtcPhp
    /**
     * show off @method
     *
     * @method string balance() balance( array $params )
     * @method string ordersActive() ordersActive( array $params )
     * @method string new_order() new_order( array $params )
     * @method string cancel_order() cancel_order( array $params )
     * @method string trades() trades( array $params )
     * @method string ordersRecent() ordersRecent( array $params )
     *
     * @package Hitbtc
     */

{
    CONST HITBTC_API_URL = 'http://api.hitbtc.com'; // 'http://demo-api.hitbtc.com' HHTPS!
    CONST HITBTC_TRADING_API_URL_SEGMENT = '/api/1/trading/'; // /api/1/trading/  /api/2/account/-not working
    private $_key, $_secret;

    private $_availableMethods = array(
        'balance',
        'orders/active',
        'new_order',
        'cancel_order',
        'trades',
        'orders/recent',
        'order'
    );
    private $_postMethods = array(
        'new_order',
        'cancel_order'
    );
    public function __construct($key, $secret)
    {
        $this->_key = $key;
        $this->_secret = $secret;
        $this->_nonce =  time()*1E3;
    }
    public function __call($name, $arguments) {
        $methodPathParts = preg_split('/(?=[A-Z])/', $name);
        $methodPathParts = array_map(
            function($pathSegment) { return strtolower($pathSegment); },
            $methodPathParts
        );
        $method = implode('/', $methodPathParts);
        if(!in_array($method, $this->_availableMethods)){
            throw new \Exception( 'Method that you try to call doesn\'t exists!' );
        }
        return $this->_request($method, $arguments, in_array($method, $this->_postMethods));
    }
    private function _request($method, $arguments, $isPost = FALSE)
    {
        $requestUri = self::HITBTC_TRADING_API_URL_SEGMENT
            . $method
            . '?nonce=' . $this->_getNonce()
            . '&apikey=' . $this->_key;
        $arguments = sizeof($arguments) > 0 ? $arguments[0] : array();
        $params = http_build_query($arguments);
        if (strlen($params) && $isPost === FALSE) {
            $requestUri .= '&' . $params;
        }
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => self::HITBTC_API_URL . $requestUri,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => 1
        ));
        if($isPost) {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Signature: ' . $this->_signature($requestUri, $isPost ? $params : '')));

        //echo self::HITBTC_API_URL . $requestUri . "\n";
        //echo $params . "\n";

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
    private function _signature($uri, $postData)
    {
        return strtolower(hash_hmac('sha512', $uri . $postData, $this->_secret));
    }
    private function _getNonce()
    {
        return $this->_nonce++;
    }

}