<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 9/8/2018
 * Time: 10:05 AM
 */

namespace App\Classes\Hitbtc;
use Exception as Exception; // a common import
use App\Classes;

/**
 * Class HitBtc2
 * @info Test connection hitbtc.ccom api class. Code taken from ccxt lib. Exchange.php.
 * Only few methods were taken from the original lib
 * @package App\Classes
 */

// digits counting mode
const DECIMAL_PLACES = 0;
const SIGNIFICANT_DIGITS = 1;

class Exchange
{
    public function describe () {
        return array ();
    }

    public function __construct ($options = array ()) {

        // todo auto-camelcasing for methods in PHP
        // $method_names = get_class_methods ($this);
        // foreach ($method_names as $method_name) {
        //     if ($method_name) {
        //         if (($method_name[0] != '_') && ($method_name[-1] != '_') && (mb_strpos ($method_name, '_') !== false)) {
        //             $parts = explode ('_', $method_name);
        //             $camelcase = $parts[0];
        //             for ($i = 1; $i < count ($parts); $i++) {
        //                 $camelcase .= static::capitalize ($parts[$i]);
        //             }
        //             // $this->$camelcase = $this->$method_name;
        //             // echo $method_name . " " . method_exists ($this, $method_name) . " " . $camelcase . " " . method_exists ($this, $camelcase) . "\n";
        //         }
        //     }
        // }

        $this->curl         = curl_init ();
        $this->curl_options = array (); // overrideable by user, empty by default

        $this->id           = null;

        // rate limiter params
        $this->rateLimit   = 2000;
        $this->tokenBucket = array (
            'refillRate' => 1.0 / $this->rateLimit,
            'delay' => 1.0,
            'capacity' => 1.0,
            'defaultCost' => 1.0,
            'maxCapacity' => 1000,
        );

        $this->curlopt_interface = null;
        $this->timeout   = 10000; // in milliseconds
        $this->proxy     = '';
        $this->origin    = '*'; // CORS origin
        $this->headers   = array ();

        $this->options   = array (); // exchange-specific options if any

        $this->skipJsonOnStatusCodes = false; // TODO: reserved, rewrite the curl routine to parse JSON body anyway

        $this->name      = null;
        $this->countries = null;
        $this->version   = null;
        $this->certified = false;
        $this->urls      = array ();
        $this->api       = array ();
        $this->comment   = null;

        $this->markets       = null;
        $this->symbols       = null;
        $this->ids           = null;
        $this->currencies    = array ();
        $this->balance       = array ();
        $this->orderbooks    = array ();
        $this->fees          = array ('trading' => array (), 'funding' => array ());
        $this->precision     = array ();
        $this->limits        = array ();
        $this->orders        = array ();
        $this->trades        = array ();
        $this->transactions  = array ();
        $this->exceptions    = array ();
        $this->verbose       = false;
        $this->apiKey        = '';
        $this->secret        = '';
        $this->password      = '';
        $this->uid           = '';
        $this->privateKey    = '';
        $this->walletAddress = '';

        $this->twofa         = false;
        $this->marketsById   = null;
        $this->markets_by_id = null;
        $this->currencies_by_id = null;
        $this->userAgent   = null; // 'ccxt/' . $this::VERSION . ' (+https://github.com/ccxt/ccxt) PHP/' . PHP_VERSION;
        $this->userAgents = array (
            'chrome' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36',
            'chrome39' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36',
        );
        $this->minFundingAddressLength = 1; // used in check_address
        $this->substituteCommonCurrencyCodes = true;
        $this->timeframes = null;
        $this->parseJsonResponse = true;

        $this->requiredCredentials = array (
            'apiKey' => true,
            'secret' => true,
            'uid' => false,
            'login' => false,
            'password' => false,
            'twofa' => false, // 2-factor authentication (one-time password key)
            'privateKey' => false,
            'walletAddress' => false,
        );

        // API methods metainfo
        $this->has = array (
            'CORS' => false,
            'publicAPI' => true,
            'privateAPI' => true,
            'cancelOrder' => true,
            'cancelOrders' => false,
            'createDepositAddress' => false,
            'createOrder' => true,
            'createMarketOrder' => true,
            'createLimitOrder' => true,
            'deposit' => false,
            'fetchBalance' => true,
            'fetchClosedOrders' => false,
            'fetchCurrencies' => false,
            'fetchDepositAddress' => false,
            'fetchDeposits' => false,
            'fetchFundingFees' => false,
            'fetchL2OrderBook' => true,
            'fetchMarkets' => true,
            'fetchMyTrades' => false,
            'fetchOHLCV' => 'emulated',
            'fetchOpenOrders' => false,
            'fetchOrder' => false,
            'fetchOrderBook' => true,
            'fetchOrderBooks' => false,
            'fetchOrders' => false,
            'fetchTicker' => true,
            'fetchTickers' => false,
            'fetchTrades' => true,
            'fetchTradingFees' => false,
            'fetchTradingLimits' => false,
            'fetchTransactions' => false,
            'fetchWithdrawals' => false,
            'withdraw' => false,
        );

        $this->precisionMode = DECIMAL_PLACES;

        $this->lastRestRequestTimestamp = 0;
        $this->lastRestPollTimestamp    = 0;
        $this->restRequestQueue         = null;
        $this->restPollerLoopIsRunning  = false;
        $this->enableRateLimit          = false;
        $this->last_http_response = null;
        $this->last_json_response = null;
        $this->last_response_headers = null;

        $this->commonCurrencies = array (
            'XBT' => 'BTC',
            'BCC' => 'BCH',
            'DRK' => 'DASH'
        );

        $options = array_replace_recursive ($this->describe(), $options);

        if ($options)
            foreach ($options as $key => $value)
                $this->{$key} =
                    (property_exists ($this, $key) && is_array ($this->{$key}) && is_array ($value)) ?
                        array_replace_recursive ($this->{$key}, $value) :
                        $value;

        if ($this->api)
            $this->define_rest_api ($this->api, 'request');

        if ($this->markets)
            $this->set_markets ($this->markets);
    }

    public function set_markets ($markets, $currencies = null) {
        $values = is_array ($markets) ? array_values ($markets) : array ();
        for ($i = 0; $i < count($values); $i++) {
            $values[$i] = array_merge (
                $this->fees['trading'],
                array ('precision' => $this->precision, 'limits' => $this->limits),
                $values[$i]
            );
        }
        $this->markets = $this->indexBy ($values, 'symbol');
        $this->markets_by_id = $this->indexBy ($values, 'id');
        $this->marketsById = $this->markets_by_id;
        $this->symbols = array_keys ($this->markets);
        sort ($this->symbols);
        $this->ids = array_keys ($this->markets_by_id);
        sort ($this->ids);
        if ($currencies) {
            $this->currencies = array_replace_recursive ($currencies, $this->currencies);
        } else {
            $base_currencies = array_map (function ($market) {
                return array (
                    'id' => array_key_exists ('baseId', $market) ? $market['baseId'] : $market['base'],
                    'numericId' => array_key_exists ('baseNumericId', $market) ? $market['baseNumericId'] : null,
                    'code' => $market['base'],
                    'precision' => array_key_exists ('precision', $market) ? (
                    array_key_exists ('base', $market['precision']) ? $market['precision']['base'] : (
                    array_key_exists ('amount', $market['precision']) ? $market['precision']['amount'] : null
                    )) : 8,
                );
            }, array_filter ($values, function ($market) {
                return array_key_exists ('base', $market);
            }));
            $quote_currencies = array_map (function ($market) {
                return array (
                    'id' => array_key_exists ('quoteId', $market) ? $market['quoteId'] : $market['quote'],
                    'numericId' => array_key_exists ('quoteNumericId', $market) ? $market['quoteNumericId'] : null,
                    'code' => $market['quote'],
                    'precision' => array_key_exists ('precision', $market) ? (
                    array_key_exists ('quote', $market['precision']) ? $market['precision']['quote'] : (
                    array_key_exists ('price', $market['precision']) ? $market['precision']['price'] : null
                    )) : 8,
                );
            }, array_filter ($values, function ($market) {
                return array_key_exists ('quote', $market);
            }));
            $currencies = $this->indexBy (array_merge ($base_currencies, $quote_currencies), 'code');
            $this->currencies = array_replace_recursive ($currencies, $this->currencies);
        }
        $this->currencies_by_id = $this->indexBy (array_values ($this->currencies), 'id');
        return $this->markets;
    }

    public function define_rest_api ($api, $method_name, $options = array ()) {
        foreach ($api as $type => $methods)
            foreach ($methods as $http_method => $paths)
                foreach ($paths as $path) {

                    $splitPath = mb_split ('[^a-zA-Z0-9]', $path);

                    $uppercaseMethod  = mb_strtoupper ($http_method);
                    $lowercaseMethod  = mb_strtolower ($http_method);
                    $camelcaseMethod  = static::capitalize ($lowercaseMethod);
                    $camelcaseSuffix  = implode (array_map (get_called_class() . '::capitalize', $splitPath));
                    $lowercasePath    = array_map ('trim', array_map ('strtolower', $splitPath));
                    $underscoreSuffix = implode ('_', array_filter ($lowercasePath));

                    $camelcase  = $type . $camelcaseMethod . static::capitalize ($camelcaseSuffix);
                    $underscore = $type . '_' . $lowercaseMethod . '_' . mb_strtolower ($underscoreSuffix);

                    if (array_key_exists ('suffixes', $options)) {
                        if (array_key_exists ('camelcase', $options['suffixes']))
                            $camelcase .= $options['suffixes']['camelcase'];
                        if (array_key_exists ('underscore', $options['suffixes']))
                            $underscore .= $options['suffixes']['underscore'];
                    }

                    $partial = function ($params = array ()) use ($path, $type, $uppercaseMethod, $method_name) {
                        return call_user_func (array ($this, $method_name), $path, $type, $uppercaseMethod, $params);
                    };

                    $this->$camelcase  = $partial;
                    $this->$underscore = $partial;
                }
    }

    public function fetch ($url, $method = 'GET', $headers = null, $body = null) {

        if ($this->enableRateLimit)
            $this->throttle ();

        $headers = array_merge ($this->headers, $headers ? $headers : array ());


        if (strlen ($this->proxy))
            $headers['Origin'] = $this->origin;

        if (!$headers)
            $headers = array ();
        elseif (is_array ($headers)) {
            $tmp = $headers;
            $headers = array ();
            foreach ($tmp as $key => $value)
                $headers[] = $key . ': ' . $value;
        }

        // this name for the proxy string is deprecated
        // we should rename it to $this->cors everywhere
        $url = $this->proxy . $url;

        $verbose_headers = $headers;

        curl_setopt ($this->curl, CURLOPT_URL, $url);

        if ($this->timeout) {
            curl_setopt ($this->curl, CURLOPT_CONNECTTIMEOUT_MS, (int)($this->timeout));
            curl_setopt ($this->curl, CURLOPT_TIMEOUT_MS, (int)($this->timeout));
        }

        curl_setopt ($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt ($this->curl, CURLOPT_SSL_VERIFYPEER, false);

        if ($this->userAgent)
            if (gettype ($this->userAgent) == 'string') {
                curl_setopt ($this->curl, CURLOPT_USERAGENT, $this->userAgent);
                $verbose_headers = array_merge ($verbose_headers, array ('User-Agent' => $this->userAgent));
            } else if ((gettype ($this->userAgent) == 'array') && array_key_exists ('User-Agent', $this->userAgent)) {
                curl_setopt ($this->curl, CURLOPT_USERAGENT, $this->userAgent['User-Agent']);
                $verbose_headers = array_merge ($verbose_headers, $this->userAgent);
            }

        curl_setopt ($this->curl, CURLOPT_ENCODING, '');

        if ($method == 'GET') {

            curl_setopt ($this->curl, CURLOPT_HTTPGET, true);

        } else if ($method == 'POST') {

            curl_setopt ($this->curl, CURLOPT_POST, true);
            curl_setopt ($this->curl, CURLOPT_POSTFIELDS, $body);

        } else if ($method == 'PUT') {

            curl_setopt ($this->curl, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt ($this->curl, CURLOPT_POSTFIELDS, $body);

            $headers[] = 'X-HTTP-Method-Override: PUT';

        } else if ($method == 'DELETE') {

            curl_setopt ($this->curl, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt ($this->curl, CURLOPT_POSTFIELDS, $body);

            $headers[] = 'X-HTTP-Method-Override: DELETE';
        }

        if ($headers)
            curl_setopt ($this->curl, CURLOPT_HTTPHEADER, $headers);

        if ($this->verbose) {
            print_r ("\nRequest:\n");
            print_r (array ($method, $url, $verbose_headers, $body));
        }

        // we probably only need to set it once on startup
        if ($this->curlopt_interface) {
            curl_setopt ($this->curl, CURLOPT_INTERFACE, $this->curlopt_interface);
        }

        /*
        // this is currently not integrated, reserved for future
        if ($this->proxy) {
            curl_setopt ($this->curl, CURLOPT_PROXY, $this->proxy);
        }
        */

        curl_setopt ($this->curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt ($this->curl, CURLOPT_FAILONERROR, false);

        $response_headers = array ();

        // this function is called by curl for each header received
        curl_setopt ($this->curl, CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$response_headers) {
                $length = strlen ($header);
                $header = explode (':', $header, 2);
                if (count ($header) < 2) // ignore invalid headers
                    return $length;
                $name = strtolower (trim ($header[0]));
                if (!array_key_exists ($name, $response_headers))
                    $response_headers[$name] = [trim ($header[1])];
                else
                    $response_headers[$name][] = trim ($header[1]);
                return $length;
            }
        );

        // user-defined cURL options (if any)
        if (!empty($this->curl_options))
            curl_setopt_array ($this->curl, $this->curl_options);

        $result = curl_exec ($this->curl);

        $this->lastRestRequestTimestamp = $this->milliseconds ();
        $this->last_http_response = $result;
        $this->last_response_headers = $response_headers;

        if ($this->parseJsonResponse) {

            $this->last_json_response =
                ((gettype ($result) == 'string') &&  (strlen ($result) > 1)) ?
                    json_decode ($result, $as_associative_array = true) : null;

        }


        $curl_errno = curl_errno ($this->curl);
        $curl_error = curl_error ($this->curl);
        $http_status_code = curl_getinfo ($this->curl, CURLINFO_HTTP_CODE);

        // Reset curl opts
        curl_reset ($this->curl);

        if ($this->verbose) {
            print_r ("\nResponse:\n");
            print_r (array ($method, $url, $http_status_code, $curl_error, $response_headers, $result));
        }

        $this->handle_errors ($http_status_code, $curl_error, $url, $method, $response_headers, $result ? $result : null);

        if ($result === false) {

            if ($curl_errno == 28) // CURLE_OPERATION_TIMEDOUT
                $this->raise_error ('RequestTimeout', $url, $method, $curl_errno, $curl_error);

            // var_dump ($result);

            // all sorts of SSL problems, accessibility
            $this->raise_error ('ExchangeNotAvailable', $url, $method, $curl_errno, $curl_error);
        }

        if (in_array ($http_status_code, array (418, 429))) {

            $this->raise_error ('DDoSProtection', $url, $method, $http_status_code,
                'not accessible from this location at the moment');
        }

        if (in_array ($http_status_code, array (404, 409, 500, 501, 502))) {

            $this->raise_error ('ExchangeNotAvailable', $url, $method, $http_status_code,
                'not accessible from this location at the moment');
        }

        if (in_array ($http_status_code, array (422))) {

            $this->raise_error ('ExchangeError', $url, $method, $http_status_code,
                'Unprocessable Entity');
        }

        if (in_array ($http_status_code, array (408, 504))) {

            $this->raise_error ('RequestTimeout', $url, $method, $http_status_code,
                'not accessible from this location at the moment');
        }

        if (in_array ($http_status_code, array (401, 511))) {

            $details = '(possible reasons: ' . implode (', ', array (
                    'invalid API keys',
                    'bad or old nonce',
                    'exchange is down or offline',
                    'on maintenance',
                    'DDoS protection',
                    'rate-limiting in effect',
                )) . ')';

            $this->raise_error ('AuthenticationError', $url, $method, $http_status_code,
                $this->last_http_response, $details);
        }

        if (in_array ($http_status_code, array (400, 403, 405, 503, 520, 521, 522, 525, 530))) {

            if (preg_match ('#cloudflare|incapsula|overload|ddos#i', $result)) {

                $this->raise_error ('DDoSProtection', $url, $method, $http_status_code,
                    'not accessible from this location at the moment');

            } else {

                $details = '(possible reasons: ' . implode (', ', array (
                        'invalid API keys',
                        'bad or old nonce',
                        'exchange is down or offline',
                        'on maintenance',
                        'DDoS protection',
                        'rate-limiting in effect',
                    )) . ')';

                $this->raise_error ('ExchangeNotAvailable', $url, $method, $http_status_code,
                    $this->last_http_response, $details);
            }
        }


        if ($this->parseJsonResponse && !$this->last_json_response) {

            if (preg_match ('#offline|busy|retry|wait|unavailable|maintain|maintenance|maintenancing#i', $result)) {

                $details = '(possible reasons: ' . implode (', ', array (
                        'exchange is down or offline',
                        'on maintenance',
                        'DDoS protection',
                        'rate-limiting in effect',
                    )) . ')';

                $this->raise_error ('ExchangeNotAvailable', $url, $method, $http_status_code,
                    'not accessible from this location at the moment', $details);
            }

            if (preg_match ('#cloudflare|incapsula#i', $result)) {
                $this->raise_error ('DDoSProtection', $url, $method, $http_status_code,
                    'not accessible from this location at the moment');
            }
        }

        return $this->parseJsonResponse ? $this->last_json_response : $result;
    }

    // this method is experimental
    public function throttle () {
        $now = $this->milliseconds ();
        $elapsed = $now - $this->lastRestRequestTimestamp;
        if ($elapsed < $this->rateLimit) {
            $delay = $this->rateLimit - $elapsed;
            usleep ((int)($delay * 1000.0));
        }
    }

    public function milliseconds () {
        list ($msec, $sec) = explode (' ', microtime ());
        return $sec . substr ($msec, 2, 3);
    }

    public function raise_error ($exception_type, $url, $method = 'GET', $error = null, $details = null) {

        //echo "HitBtc55.php error. line 309 " . $exception_type . " " . $url . " " . $error . " " . $details . "\n";
        //throw new Exception();

        $exception_class = __NAMESPACE__ . '\\' . $exception_type;
        throw new $exception_class (implode (' ', array (
            $this->id,
            $method,
            $url,
            $error,
            $details,
        )));

    }

    public function handle_errors ($code, $reason, $url, $method, $headers, $body) {
        // it's a stub function, does nothing in base code
    }

    //****************************


    // Taken from exchange.php line 939
    public function fetch2 ($path, $api = 'public', $method = 'GET', $params = array (), $headers = null, $body = null) {
        // Go to hitbtc2.php line 550. This is an overrided method
        $request = $this->sign ($path, $api, $method, $params, $headers, $body);
        return $this->fetch ($request['url'], $request['method'], $request['headers'], $request['body']);
    }

    public static function omit ($array, $keys) {
        $result = $array;
        if (is_array ($keys))
            foreach ($keys as $key)
                unset ($result[$key]);
        else
            unset ($result[$keys]);
        return $result;
    }

    public static function extract_params ($string) {
        if (preg_match_all ('/{([\w-]+)}/u', $string, $matches))
            return $matches[1];
    }

    public static function implode_params ($string, $params) {
        foreach ($params as $key => $value)
            $string = implode ($value, mb_split ('{' . $key . '}', $string));
        return $string;
    }

    public static function urlencode ($string) {
        return http_build_query ($string);
    }

    public function check_required_credentials () {
        $keys = array_keys ($this->requiredCredentials);
        foreach ($this->requiredCredentials as $key => $value) {
            if ($value && (!$this->$key)) {
                throw new AuthenticationError ($this->id . ' requires `' . $key . '`');

            }
        }
    }

    public static function json ($data, $params = array ()) {
        $options = array (
            'convertArraysToObjects' => JSON_FORCE_OBJECT,
            // other flags if needed...
        );
        $flags = 0;
        foreach ($options as $key => $value)
            if (array_key_exists ($key, $params) && $params[$key])
                $flags |= $options[$key];
        return json_encode ($data, $flags);
    }

    public static function encode ($input) {
        return $input;
    }

    public static function decode ($input) {
        return $input;
    }

    public static function indexBy ($arrayOfArrays, $key) {
        return static::index_by ($arrayOfArrays, $key);
    }

    public static function capitalize ($string) {
        return mb_strtoupper (mb_substr ($string, 0, 1)) . mb_substr ($string, 1);
    }

    public static function index_by ($array, $key) {
        $result = array ();
        foreach ($array as $element) {
            if (isset ($element[$key])) {
                $result[$element[$key]] = $element;
            }
        }
        return $result;
    }


}