<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/* Generic code for PayLink */

require_once('class-citypay-library.php');

class CPPP_WP {

	private $pay_module;
	private	$request_addr = array();
	private $request_cart = array();
	private $request_client = array();
	private $request_config = array();

	function __construct() {
		$args = func_get_args();
		if (count($args)==1) {
			$this->pay_module = $args[0];
		} else {
			throw new Exception('Payment module must be provided to constructor.');
		}
	}

	public function debugLog($text) {
		if (method_exists($this->pay_module,'debugLog')) {
			$this->pay_module->debugLog($text);
		}
	}

	public function matchCurrencyConfig($currencyCode, $conf_num) {
		$conf_cur = trim(strtoupper($this->pay_module->getCurrencyConfig($conf_num)));
		$conf_mid = trim($this->pay_module->getMerchantConfig($conf_num));
		$conf_key = trim($this->pay_module->getLicenceConfig($conf_num));
		if (empty($conf_cur)) { return null; }		// Currency code not configured
		if (empty($conf_mid)) { return null; }		// Merchant ID not configured
		if (empty($conf_key)) { return null; }		// Licence key not configured
		if (!ctype_digit($conf_mid)) { return null; }	// Merchant ID is not numeric
		if (strcasecmp($conf_cur,$currencyCode)!=0) { return null; }	// Does not match required currency
		return array($conf_mid,$conf_key,$conf_cur);	// Matched, return config details
	}

	public function getCurrencyConfig($currencyCode) {
		for ($conf_num=1;$conf_num<=5;$conf_num++) {
			$conf=$this->matchCurrencyConfig($currencyCode,$conf_num);
			if (is_array($conf) && !empty($conf)) {
				return $conf;
			}
		}
		return null;
	}

	public function canUseForCurrency($currencyCode) {
		$conf=$this->getCurrencyConfig($currencyCode);
		if (is_array($conf) && !empty($conf)) { return true; }
		return false;	// No configured currency matches the required currency
	}
        
    public function setCustomParameter($name, $value, $qualifiers = null) {
        static $_qualifiers = array(
                'required', 'placeholder', 'label', 'locked', 'fieldType'
            );            
        if (is_array($qualifiers)) {
            $customParam = CPPP_Library::extractKeyValuesFromArray($qualifiers, $_qualifiers);
        } else {
            $customParam = array();
        }
        $customParam['name'] = $name;
        $customParam['value'] = $value;
        $customParams = &$this->request_config['config']['customParams'];
        if (is_null($customParams)) {
            $this->request_config['config']['customParams'] = array($customParam);
        } else {
            $customParams[] = $customParam;
        }
    }

	public function setRequestAddress($fname,$lname,$addr1,$addr2,$addr3,$area,$zip,$country,$email,$phone) {
		$this->request_addr = array(
			'cardholder'	=> array(
				'firstName'	=> trim($fname),
				'lastName'	=> trim($lname),
				'address'	=> array (
					'address1'	=> trim($addr1),
					'address2'	=> trim($addr2),
					'address3'	=> trim($addr3),
					'area'		=> trim($area),
					'postcode'	=> trim($zip),
					'country'	=> trim(strtoupper($country)))));
        if(!empty($email)) {
            $this->request_addr['cardholder']['email'] = trim($email);
        }
	}

	public function setRequestCart($mid,$key,$prefix,$cart_id,$price,$cart_desc) {
		$this->request_cart = array(
			'merchantid' => (int)$mid,
			'licenceKey' => $key,
			'identifier' => trim($prefix.$cart_id),
            'amount' => (int)$price,
            'cart' => array(
            'productInformation' => trim($cart_desc))
        );
	}

	public function setRequestClient($client_name, $client_version, $plugin_name, $plugin_version) {
		$this->request_client = array(
			'clientVersion'	=> 
                trim($client_name).' '.trim($client_version)
                    .'/'.trim($plugin_name).' '.trim($plugin_version)
        );
	}

	public function setRequestConfig($testmode, $postback_url, $return_success_url, $return_failure_url) {
		$this->request_config = array(
			//'test'		=> 'simulator',
			'test' => $testmode?'true':'false',
			'config' => array(
            /* Disabled for use with CityPay PayForm WordPress Plugin */
            /*'lockParams'	=> array('cardholder'),*/
			'redirect_success' => $return_success_url,
			'redirect_failure' => $return_failure_url)
		);
        $this->request_config['config']['redirect_params'] = true;
        $this->request_config['config']['postback'] = $postback_url;
	}
    
    public function setRequestConfigOption($option) {
        if (!is_array($this->request_config['config']['options'])) {
            $this->request_config['config']['options'] = array();
        }
        
        array_push($this->request_config['config']['options'], $option);
    }
    
    public function setRequestMerchant($email_address) {
        $this->request_merchant = array(
            'email' => $email_address
        );
    }

	public function getJSON() {
        // note, call to this function at line 120 results in PHP warnings for lack of
        // specified parameters; yet getJSON simply collates information that forms part of
        // the current instance of CPPP_Paylink  
		$params=array_merge(
            $this->request_merchant,
            $this->request_cart,
            $this->request_client,
            $this->request_addr,
            $this->request_config
        );
		return json_encode($params);
	}

    public function getPaylinkURL($http_options = null) {
        $json = $this->getJSON();
        $this->debugLog($json);

        // Default HTTP options
        $http_args = array(
            'method'      => 'POST',
            'body'        => $json,
            'timeout'     => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => array(
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json;charset=UTF-8',
                'Content-Length' => strlen($json)
            ),
            'cookies'     => array(),
            'sslverify'   => true,
        );

        // Merge with any HTTP options passed to the function
        if (isset($http_options) && is_array($http_options)) {
            $http_args = wp_parse_args($http_options, $http_args);
        }

        // Send the request to the Paylink URL
        $response = wp_remote_post('https://secure.citypay.com/paylink3/create', $http_args);

        // Check for errors
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->debugLog("HTTP request error: $error_message");
            throw new Exception('Error generating PayLink token');
        }

        // Get the response body
        $response_body = wp_remote_retrieve_body($response);

        // Log response for debugging
        $this->debugLog("Response: " . print_r($response_body, true));

        // Decode the JSON response
        $results = json_decode($response_body, true);

        // Check for a valid response
        if ($results['result'] != 1) {
            $this->debugLog("Invalid response: " . print_r($results, true));
            throw new Exception('Invalid response from PayLink');
        }

        $paylink_url = $results['url'];

        if (empty($paylink_url)) {
            $this->debugLog("No URL obtained: " . print_r($results, true));
            throw new Exception('No URL obtained from PayLink');
        }

        return $paylink_url;
    }

	public function validPostbackIP($remote_addr,$allowed_ip) {
		if (empty($allowed_ip)) {
			$allowed_ip="54.246.184.81, 54.246.184.93, 54.246.184.95";
		}
		if (strcasecmp($allowed_ip,'Any')!=0) {
			$ip_list=explode(',',$ip_conf);
			foreach ($ip_list as $ip_check) {
				if (strcmp(trim($ip_check),$remote_addr)==0) {
					return true;
				}
			}
			return false;
		}
		return true;
	}

    public function getPostbackData() {
        // Get the raw POST data
        $raw_post_data = file_get_contents("php://input");

        // Check if the raw POST data is empty
        if (empty($raw_post_data)) {
            return null;
        }

        // Decode the JSON data
        $postback_data = json_decode($raw_post_data, true);

        // Check if the JSON decoding was successful and the result is an array
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($postback_data)) {
            return null;
        }

        // Convert all keys to lowercase
        $postback_data = array_change_key_case($postback_data, CASE_LOWER);

        // Sanitize the data
        $sanitized_data = array();
        foreach ($postback_data as $key => $value) {
            // Sanitize based on the type of data
            if (is_email($value)) {
                $sanitized_data[$key] = sanitize_email($value);
            } elseif (is_array($value)) {
                // Recursively sanitize arrays
                $sanitized_data[$key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized_data[$key] = sanitize_text_field($value);
            }
        }

        return $sanitized_data;
    }

	public function isAuthorised($postback_data) {
		$result=$postback_data['authorised'];
		$this->debugLog('isAuthorised result is type '.gettype($result).' value = '.$result);
		if (is_string($result)) { return (strtolower($result) === 'true'); }
		if (is_bool($result)) { return $result === true; }
		return false;
	}

	public function validatePostbackData($postback_data,$key) {
		$hash_src = $postback_data['authcode'].
        $postback_data['amount'].$postback_data['errorcode'].
        $postback_data['merchantid'].$postback_data['transno'].
        $postback_data['identifier'].$key;
		// Check both the sha1 and sha256 hash values to ensure that results have not
		// been tampered with
		$check=base64_encode(sha1($hash_src,true));
		if (strcmp($postback_data['sha1'],$check)!=0) { return false; }
		$check=base64_encode(hash('sha256',$hash_src,true));
		if (strcmp($postback_data['sha256'],$check)!=0) { return false; }
		return true;	// Hash values match expected value
	}
}