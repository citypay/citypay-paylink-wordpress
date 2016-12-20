<?php
/**
 * Plugin Name: CityPay PayLink PayForm WP
 * Plugin URI: http://citypay.com/paylink
 * Description: Include an arbitrary payment processing form.
 * Version: 1.0.7
 * Author: CityPay Limited
 * Author URI: http://citypay.com
 */

defined('ABSPATH') or die;

require_once('includes/class-citypay-library.php');
require_once('includes/class-citypay-logger.php');
require_once('includes/class-citypay-stack.php');
require_once('includes/class-citypay-filter.php');
require_once('includes/class-citypay-validation.php');

if (file_exists('customer/overrides.php')) {
    require_once('customer/overrides.php');
}

define('CP_PAYLINK_DISPATCHER', 'cp_paylink');
define('CP_PAYLINK_MERCHANT_ID', 'cp_paylink_merchant_id');
define('CP_PAYLINK_LICENCE_KEY', 'cp_paylink_licence_key');
define('CP_PAYLINK_MERCHANT_EMAIL_ADDRESS', 'cp_paylink_merchant_email_address');
define('CP_PAYLINK_ENABLE_MERCHANT_EMAIL', 'cp_paylink_enable_merchant_email');
define('CP_PAYLINK_ENABLE_TEST_MODE', 'cp_paylink_enable_test_mode');
define('CP_PAYLINK_ENABLE_DEBUG_MODE', 'cp_paylink_enable_debug_mode');
    
define('CP_PAYLINK_NAME_REGEX', '/^\s*\b(?:(Mr|Mrs|Miss|Dr)\b\.?+)?+\s*\b([\w-]+)\b\s+\b(\b\w\b)?\s*([\w-\s]+?)\s*$/i');
define('CP_PAYLINK_IDENTIFIER_REGEX', '/[A-Za-z0-9]{5,}/');

define('CP_PAYLINK_NO_ERROR', 0x00);

define('CP_PAYLINK_AMOUNT_PARSE_ERROR', -1);
define('CP_PAYLINK_AMOUNT_PARSE_ERROR_EMPTY_STRING', -2);
define('CP_PAYLINK_AMOUNT_PARSE_ERROR_INVALID_CHARACTER', -3);
define('CP_PAYLINK_AMOUNT_PARSE_ERROR_INVALID_PRECISION', -4);
define('CP_PAYLINK_AMOUNT_PARSE_ERROR_BELOW_MINIMUM_VALUE', -5);
define('CP_PAYLINK_AMOUNT_PARSE_ERROR_ABOVE_MAXIMUM_VALUE', -6);

define('CP_PAYLINK_DEFAULT_MINIMUM_AMOUNT', 0);

define('CP_PAYLINK_TEXT_FIELD_PARSE_ERROR_EMPTY_STRING', -100);

define('CP_PAYLINK_EMAIL_ADDRESS_FIELD_PARSE_ERROR_EMPTY_STRING', -200);
define('CP_PAYLINK_EMAIL_ADDRESS_FIELD_PARSE_ERROR_NOT_VALID', -201);

define('CP_PAYLINK_IDENTIFIER_FIELD_PARSE_ERROR_EMPTY_STRING', -300);
define('CP_PAYLINK_IDENTIFIER_FIELD_PARSE_ERROR_NOT_VALID', -301);

define('CP_PAYLINK_NAME_FIELD_PARSE_ERROR_EMPTY_STRING', -400);
define('CP_PAYLINK_NAME_FIELD_PARSE_ERROR_NOT_VALID', -401);

define('CP_PAYLINK_TERMS_AND_CONDITIONS_NOT_ACCEPTED', -500);

define('CP_PAYLINK_DEFAULT_ERROR_MESSAGE', 'cp_paylink_default_error_messages');

$cp_paylink_default_error_messages = array(
        CP_PAYLINK_TEXT_FIELD_PARSE_ERROR_EMPTY_STRING
            => __('This field cannot be empty.'),
        CP_PAYLINK_IDENTIFIER_FIELD_PARSE_ERROR_EMPTY_STRING
            => __('This field cannot be empty.'),
        CP_PAYLINK_IDENTIFIER_FIELD_PARSE_ERROR_NOT_VALID
            => __('This field does not contain an acceptable value.'),
        CP_PAYLINK_NAME_FIELD_PARSE_ERROR_EMPTY_STRING
            => __('This field cannot be empty.'),
        CP_PAYLINK_NAME_FIELD_PARSE_ERROR_NOT_VALID
            => __('This field does not contain an acceptable value. Please enter a person\'s name of the form <b>&lt;firstname&gt; &lt;lastname&gt;</b>.'),
        CP_PAYLINK_EMAIL_ADDRESS_FIELD_PARSE_ERROR_EMPTY_STRING
            => __('This field cannot be empty.'),
        CP_PAYLINK_EMAIL_ADDRESS_FIELD_PARSE_ERROR_NOT_VALID
            => __('This field does not contain an acceptable value. Please enter a valid email address of the form <b>&lt;name&gt;@&lt;domain-name&gt;</b>.'),
        CP_PAYLINK_AMOUNT_PARSE_ERROR_EMPTY_STRING
            => __('This field cannot be empty.'),
        CP_PAYLINK_AMOUNT_PARSE_ERROR_INVALID_CHARACTER
            => __('This field contains an invalid character; only numeric digits and a decimal point are acceptable.'),
        CP_PAYLINK_AMOUNT_PARSE_ERROR_INVALID_PRECISION
            => __('This field contains a value with too many digits appearing after the decimal point, and is therefore unacceptable.'),
        CP_PAYLINK_AMOUNT_PARSE_ERROR_BELOW_MINIMUM_VALUE
            => __('This field contains an amount that is below the lowest acceptable value for transactions processed using this service.'),
        CP_PAYLINK_AMOUNT_PARSE_ERROR_ABOVE_MAXIMUM_VALUE
            => __('This field contains an amount that is more than the maxmimum acceptable value for transactions processed using this service.'),
        CP_PAYLINK_TERMS_AND_CONDITIONS_NOT_ACCEPTED
            => __('You must accept the terms and conditions to use this service.')
    );

define('CP_PAYLINK_PROCESSING_ERROR_DATA_INPUT_ERROR', 0x05);
define('CP_PAYLINK_PROCESSING_ERROR_PAYLINK_ERROR', 0x06);

function cp_paylink_config_stack() {
    static $cp_paylink_config_stack = NULL;
    if (is_null($cp_paylink_config_stack)) {
        $cp_paylink_config_stack = new cp_paylink_config_stack();
    }
    return $cp_paylink_config_stack;
}

/*function cp_paylink_enqueue_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri().'/style.css');
    wp_enqueue_style('child-style', get_stylesheet_uri(), array('parent-style'));
}*/

/*function cp_paylink_enqueue_javascript() {
    wp_enqueue_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js');
    wp_enqueue_script('paylink', 'https://secure.citypay.com/paylink3/js/paylink-api-1.0.0.min.js', array('jquery'));
}*/

function cp_paylink_send_cors_headers($headers) {
    error_log("send_cors_headers: ".$headers);
    $headers['Access-Control-Allow-Origin'] = "https://secure.citypay.com";
    return $headers;
}

function cp_paylink_add_query_vars_filter($vars) {
    $vars[] = "cp_paylink";
    return $vars;
}

function cp_paylink_payform_field_config_sort($v1, $v2) {   
    if ($v1->order > $v2->order) {
        return 1;
    } elseif ($v1->order < $v2->order) {
        return -1;
    } else {
        return 0;
    }
}
        
class cp_paylink_field {
    public $id, $label, $name, $order, $placeholder;
    public $value, $content, $passthrough;
    public $error, $error_message;
    public function __construct($id, $name, $label, $placeholder = '', $order = 99, $content = null, $passthrough = false) {
        $this->id = $id;
        $this->name = $name;
        $this->label = $label;
        $this->placeholder = $placeholder;
        $this->order = $order;
        $this->content = $content;
        $this->passthrough = $passthrough;
    }
    
    public function configure_error_message($attrs, $content = null) {
        $a = shortcode_atts(
                array(
                        'handle' => ''
                    ),
                $attrs
            );
        
        /*echo '<pre>';
        var_dump($a);
        var_dump($attrs);
        debug_print_backtrace();
        echo '</pre>';*/
        
        $h = constant($a['handle']);
        if (!is_null($h)) {
            $this->error_message[$h] = $content;
        }
        
        return '';
    }
    
    public function parse($value_in, &$value_out) {
        $this->value = $value_in;
        $value_out = $value_in;
        return true;
    }
    
    public function getContent() {
        return $this->content;
    }
    
    public function getErrorMessage() {
        if ($this->error != CP_PAYLINK_NO_ERROR) {
            if (!is_null($this->error_message)) {
                if (array_key_exists($this->error, $this->error_message)) {
                    return $this->error_message[$this->error];
                } else {
                    return $GLOBALS[CP_PAYLINK_DEFAULT_ERROR_MESSAGE][$this->error];
                }
            } else {
                return $GLOBALS[CP_PAYLINK_DEFAULT_ERROR_MESSAGE][$this->error];
            }
        } else {
            return '';
        }
    }
    
    public function setContent($content = null) {
        $this->content = $content;
    }
}

class cp_paylink_text_field extends cp_paylink_field {
    public $pattern;
   
    public function __construct($id, $name, $label, $placeholder = '', $pattern = '', $order = 99, $content = null, $passthrough = false) {
        parent::__construct($id, $name, $label, $placeholder, $order, $content, $passthrough);
        $this->pattern = $pattern;
    }
    
    public function parse($value_in, &$value_out) {
        $r = parent::parse($value_in, $value_out);
        if ($r) {
            // See PPWD-3, and PPWD-13
            if (strlen($value_out) == 0x00) {
                $this->error = CP_PAYLINK_TEXT_FIELD_PARSE_ERROR_EMPTY_STRING;
            }
        }
        return true;
    }
}

class cp_paylink_checkbox_field extends cp_paylink_field {
    public function __construct($id, $name, $label, $order = 99, $content = null, $passthrough = false) {
        parent::__construct($id, $name, $label, '', $order, $content, $passthrough);
    }
    
    public function parse($value_in, &$value_out) {
        $value = ($value_in === 'on');
        return parent::parse($value, $value_out);
    }
}

class cp_paylink_amount_field extends cp_paylink_text_field {
    private $decimal_places, $minimum, $maximum;
    
    private static function _parse_amount($in, &$out, $decimal_places = null)
    {
        $_in = trim($in);
        $_out = 0;
        $i = 0; $i_max = strlen($_in);
        
        if ($i_max <= 0x00) {
            return CP_PAYLINK_AMOUNT_PARSE_ERROR_EMPTY_STRING;
        }
        
        while ($i < $i_max) {
            $c = ord($_in[$i]);
            if ($c >= 48 && $c <= 57) {
                $_out = ($_out * 10) + ($c - 48);
                $i++;
            } else if ($c == ord('.')) {
                break;
            } else {
                return CP_PAYLINK_AMOUNT_PARSE_ERROR_INVALID_CHARACTER;
            }
        }
        
        $_out *= 100;
        
        if ($i >= $i_max) {
            $out = $_out;
            return CP_PAYLINK_NO_ERROR;
        }
        
        if ($c == ord('.')) {
            $i++;
            $pence = 0;
            
            if (!is_null($decimal_places) && $i_max > $i + $decimal_places) {
                return CP_PAYLINK_AMOUNT_PARSE_ERROR_INVALID_PRECISION;
            }
            
            $j = $decimal_places;
            while ($i < $i_max) {
                $c = ord($_in[$i]);
                if ($c >= 48 && $c <= 57) {
                    $pence = ($pence * 10) + ($c - 48);
                    $i++; $j--;
                } else {
                    return CP_PAYLINK_AMOUNT_PARSE_ERROR_INVALID_CHARACTER;
                }
            }

            if ($j > 0x00) { $pence = $pence * pow(10, $j); }
            
            $_out += $pence;
        }
        
        $out = $_out;
        return CP_PAYLINK_NO_ERROR;
    }
    
    public function __construct($id, $name, $label, $placeholder = '', $order = 99, $decimal_places = null, $minimum = null, $maximum = null) {
        parent::__construct($id, $name, $label, $placeholder, null, $order, null, false);
        if (is_null($decimal_places)) {
            $this->decimal_places = null;
        } else {
            $this->decimal_places = intval($decimal_places);
        }

        if (is_null($minimum)) {
            $this->minimum = null;
        } else {
            $r = self::_parse_amount($minimum, $this->minimum, $decimal_places);
            if (!$r) {;
                // TODO: raise exception
            }
        }

        if (is_null($maximum)) {
            $this->maximum = null;
        } else {
            $r = self::_parse_amount($maximum, $this->maximum, $decimal_places);
            if (!$r) {
                // TODO: raise exception
            }
        }
    }
     
    public function parse($value_in, &$value_out, $decimal_places = null) {
        $_val = 0;
        if (!parent::parse($value_in, $_val)) {
            // this should relate to an upstream error - TODO: restructure
            // parse functionality to return more finely grained errors
            // at all levels (rather than boolean true / false values).
            $this->error = CP_PAYLINK_NO_ERROR;
            return false;
        }
       
        $_decimal_places = (!is_null($decimal_places)?$decimal_places:$this->decimal_places);
        $r = self::_parse_amount($_val, $value_out, $_decimal_places);
        if ($r == CP_PAYLINK_NO_ERROR) {
            if (!is_null($this->minimum) && $value_out < $this->minimum) {
                $this->error = CP_PAYLINK_AMOUNT_PARSE_ERROR_BELOW_MINIMUM_VALUE;
                return false;
            } else if (!is_null($this->maximum) && $value_out > $this->maximum) {
                $this->error = CP_PAYLINK_AMOUNT_PARSE_ERROR_ABOVE_MAXIMUM_VALUE;
                return false;
            } else {
                $this->error = CP_PAYLINK_NO_ERROR;
                return true;
            }
        } else {
            $this->error = $r;
            return false;
        }
    }
}

class cp_paylink_customer_name_field extends cp_paylink_text_field {
    public function parse($value_in, &$value_out) {
        $r = parent::parse($value_in, $value_out);
        if ($r) {
            if (strlen($value_out) == 0x00) {
                $this->error = CP_PAYLINK_NAME_FIELD_PARSE_ERROR_EMPTY_STRING;
            } else {
                $matches = array();
                $r = preg_match(CP_PAYLINK_NAME_REGEX, $this->value, $matches);
                if ($r) {
                    $value_out = array(
                            'salutation' => $matches[1],
                            'first-name' => $matches[2],
                            'middle-initial' => $matches[3],
                            'last-name' => $matches[4]
                        );
                } else {
                    $this->error = CP_PAYLINK_NAME_FIELD_PARSE_ERROR_NOT_VALID;
                }
            }
        }
        return $r;
    }
}

class cp_paylink_email_field extends cp_paylink_text_field {
    public function parse($value_in, &$value_out) {
        $r = parent::parse($value_in, $value_out);
        if (!$r) { return $r;}      
        if (strlen($value_out) == 0x00) {
            $this->error = CP_PAYLINK_EMAIL_ADDRESS_FIELD_PARSE_ERROR_EMPTY_STRING;
        } else if (!CityPay_Validation::validateEmailAddress($this->value)) {
            $this->error = CP_PAYLINK_EMAIL_ADDRESS_FIELD_PARSE_ERROR_NOT_VALID;
        }
        
        return $r;
    }
}

class cp_paylink_identifier_field extends cp_paylink_text_field {
    public function parse($value_in, &$value_out) {
        $r = parent::parse($value_in, $value_out);
        if ($r) {
            if (strlen($value_out) == 0x00) {
                $this->error = CP_PAYLINK_IDENTIFIER_FIELD_PARSE_ERROR_EMPTY_STRING;
            } else {
                $r = preg_match(CP_PAYLINK_IDENTIFIER_REGEX, $this->value);
                if ($r) {
                    $value_out = $this->value;
                } else {
                    $this->error = CP_PAYLINK_IDENTIFIER_FIELD_PARSE_ERROR_NOT_VALID;
                }
            }
        }
        return $r;
    }
}

class cp_paylink_accept_terms_and_conditions_checkbox_field extends cp_paylink_checkbox_field {
    public function parse($value_in, &$value_out) {
        $r = parent::parse($value_in, $value_out);        
        if ($r) {
            if (!$value_out) {
                $this->error = CP_PAYLINK_TERMS_AND_CONDITIONS_NOT_ACCEPTED;
                return false;
            }
        }
       
        return $r;
    }
}

function cp_paylink_payform_amount_field($attrs, $content = null) {
    $a = shortcode_atts(
            array(
                    'label' => '',
                    'name' => '',
                    'order' => 99,
                    'placeholder' => '',
                    'decimal-places' => 2,
                    'minimum' => null,
                    'maximum' => null,
                    'id' => null,
                    'passthrough' => false
                ),
            $attrs
        );
    
    $field = new cp_paylink_amount_field(
            $a['id'],
            $a['name'],
            $a['label'],
            $a['placeholder'],
            $a['order'],
            $a['decimal-places'],
            $a['minimum'],
            $a['maximum']
        );
    
    if (!is_null($field) && !is_null($content)) {
        add_shortcode('error-message', array($field, 'configure_error_message'));
        $_content = do_shortcode($content);
        remove_shortcode('error-message');
        $field->setContent($_content);
    }
        
    cp_paylink_config_stack()->set(
            $a['name'],
            $field
        );
    
    return '';
}

function cp_paylink_payform_field($attrs, $content = null) {
    $a = shortcode_atts(
            array(
                    'label' => '',
                    'name' => '',
                    'order' => 99,
                    'placeholder' => '',
                    'pattern' => '',
                    'type' => 'text',
                    'id' => null,
                    'passthrough' => false
                ),
            $attrs
        );
    
    switch ($a['type'])
    {    
    case 'customer-name':
        $field = new cp_paylink_customer_name_field(
                $a['id'],
                $a['name'],
                $a['label'], 
                $a['placeholder'],
                null,
                $a['order'],
                null,
                (bool) $a['passthrough']
            );
        break;
        
    case 'email-address':
        $field = new cp_paylink_email_field(
                $a['id'],
                $a['name'],
                $a['label'],
                $a['placeholder'],
                null,
                $a['order'],
                null,
                (bool) $a['passthrough']
            );
        break;
    
    case 'identifier':
        $field = new cp_paylink_identifier_field(
                $a['id'],
                $a['name'],
                $a['label'],
                $a['placeholder'],
                null,
                $a['order'],
                null,
                (bool) $a['passthrough']
            );
        break;
            
    case 'text':
    default:
        $field = new cp_paylink_text_field(
                $a['id'],
                $a['name'],
                $a['label'],
                $a['placeholder'],
                $a['pattern'],
                $a['order'],
                null,
                (bool) $a['passthrough']
            );
        break;
    }
    
    if (!is_null($field) && !is_null($content)) {
        add_shortcode('error-message', array($field, 'configure_error_message'));
        $_content = do_shortcode($content);
        remove_shortcode('error-message');
        $field->setContent($_content);
    }
    
    cp_paylink_config_stack()->set($field->name, $field);
    
    return '';
}

function cp_paylink_payform_checkbox_field($attrs, $content = null) {
    $a = shortcode_atts(
            array(
                    'label' => '',
                    'name' => '',
                    'order' => 99,
                    'type' => 'checkbox',
                    'id' => null,
                    'passthrough' => null
            ),
            $attrs
        );
    
    switch ($a['type'])
    { 
    case 'accept-terms-and-conditions':
        $field = new cp_paylink_accept_terms_and_conditions_checkbox_field(
                $a['id'],
                $a['name'],
                $a['label'],
                $a['order'],
                null,
                (is_null($a['passthrough'])?true:($a['passthrough'] === "true"))
            );
        break;
        
    case 'checkbox':
    default:
        $field = new cp_paylink_checkbox_field(
                $a['id'],
                $a['name'],
                $a['label'],
                $a['order'],
                null,
                (bool) $a['passthrough']
            );
        break;
        
    }
        
    if (!is_null($field) && !is_null($content)) {
        add_shortcode('error-message', array($field, 'configure_error_message'));
        $_content = do_shortcode($content);
        remove_shortcode('error-message');
        $field->setContent($_content);
    }
    
    cp_paylink_config_stack()->set($field->name, $field);
}

function cp_paylink_shortcode_sink($attrs, $content = null) {
    if (!is_null($content)) {
        //cp_paylink_config_stack()->push_new();
        do_shortcode($content);
    }
    return '';
}

class cp_paylink_tag {
    public $tag;
    public $attrs;
    public $start;
    public $end;
    public $tag_type;
    public $is_matched;

    public function __construct($tag, $attrs, $start, $end, $tag_type) {
        $this->tag = $tag;
        $this->attrs = $attrs;
        $this->start = $start;
        $this->end = $end;
        $this->tag_type = $tag_type;
    }
}

class cp_paylink_attr {
    public $name;
    public $value;

    public function __construct($name, $value) {
        $this->name = $name;
        $this->value = $value;
    }
}

class cp_paylink_text {
    public $text;

    public function __construct($text) {
        $this->text = $text;
    }
}

function cp_paylink_shortcode_passthrough($attrs, $content = null) {
    $a = shortcode_atts(
            array(),
            $attrs
        );
    
    if (!is_null($content)) {
        $s = CityPay_Filter::cp_paylink_trim_p_and_br_tags($content);
        return do_shortcode($s);
    } else {
        return '';
    }
}

function cp_paylink_payform_display_text_field_default($field) {
    $s = '<div class="form-group" id="'
        .$field->id
        .'"><label class="com-sm-2 control-label">'
        .$field->label
        .'</label><div class="col-sm-10"><input class="form-control" name="'
        .$field->name
        .'" type="text" value="';

    $s .= $field->value;              

    $s .= '" placeholder="'
        .$field->placeholder
        .'">';

    $e = $field->getErrorMessage();
    if (!empty($e)) {
        $s .= '<span id="error"><em> '.$e.'</em></span>';
    }

    $s .= '</div></div>';
    
    return $s;
}

function cp_paylink_payform_display_checkbox_field_default($field) {
    $s = '<div class="form-group" id="'
        .(!is_null($field->id)?$field->id:'')
        .'"><label class="com-sm-3 control-label">'
        .$field->label
        .'</label><div class="col-sm-10"><input class="form-control" name="'
        .$field->name
        .'" type="checkbox"'
        .($field->value?' checked="on"':'')
        .' />';
    $c = null;         
    $c .= $field->getContent();
    if (!empty($c)) {
        $s .= $c;
    }

    $e = $field->getErrorMessage();
    if (!empty($e)) {
        $s .= '<span id="error"><em>'.$e.'</em></span>';
    }

    $s .= '</div></div>';
    
    return $s;
}

function cp_paylink_payform_display_default($attrs, $content = null) {
    // if a configuration has been specified
    $current_url = get_permalink();        
    $s = trim($content)
       .'<form role="form" id="billPaymentForm" class="form-horizontal uk-form" method="POST" action="'
       .add_query_arg('cp_paylink', 'pay', $current_url)
       .'"><input type="hidden" name="cp_paylink_pay" value="Y">';

    $config = cp_paylink_config_stack()->peek();
    
    //
    // Sort the fieldlist appearing in the PayForm configuration according to
    // the order attribute provided.
    //
    usort($config, 'cp_paylink_payform_field_config_sort');

    foreach ($config as $field) {
        if ($field instanceof cp_paylink_text_field) {
            if (function_exists('cp_paylink_payform_display_text_field_custom')) {
                $s .= cp_paylink_payform_display_text_field_custom($field);
            } else {
                $s .= cp_paylink_payform_display_text_field_default($field);
            }
        } elseif ($field instanceof cp_paylink_checkbox_field) {
            if (function_exists('cp_paylink_payform_display_checkbox_field_custom')) {
                $s .= cp_paylink_payform_display_checkbox_field_custom($field);
            } else {
                $s .= cp_paylink_payform_display_checkbox_field_default($field);
            }
        }
    }

    $s .= '<button type="submit" class="uk-button uk-button-primary uk-button-large">'
       .$attrs['submit']
       . '</button></form>';

    return $s;
}

function cp_paylink_payform_display($attrs, $content = null) {
    $a = shortcode_atts(
            array('submit' => __('Pay', 'cp_paylink_pay')),
            $attrs
        );
    
    if (is_single() || is_page())
    {
        if (function_exists('cp_paylink_payform_display_custom')) {
            return cp_paylink_payform_display_custom($a, $content);
        } else {
            return cp_paylink_payform_display_default($a, $content);
        }
    } else {
        return '';
    }
}

function cp_paylink_payform_on_page_load($attrs, $content = null) {
    //
    //  If shortcode contains nested shortcodes, process these before
    //  processing the immediate form.
    //
    if (!is_null($content)) {
        $s = CityPay_Filter::cp_paylink_trim_p_and_br_tags($content);
        return do_shortcode($s);
    } else {
        return '';
    }
}

function cp_paylink_action_pay() {
    require_once('includes/class-citypay-logger.php');
    require_once('includes/class-citypay-paylink.php');

    $page_id = get_query_var('page_id');
    $page_post = get_post($page_id);

    add_shortcode('citypay-payform-amount-field', 'cp_paylink_payform_amount_field');
    add_shortcode('citypay-payform-checkbox-field', 'cp_paylink_payform_checkbox_field');
    add_shortcode('citypay-payform-field', 'cp_paylink_payform_field');
    add_shortcode('citypay-payform-on-error', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-on-page-load', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-success', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-failure', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-cancel', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform', 'cp_paylink_shortcode_sink');

    do_shortcode($page_post->post_content);
    
    $merchant_id = get_option(CP_PAYLINK_MERCHANT_ID);
    $licence_key = get_option(CP_PAYLINK_LICENCE_KEY);
    $test_mode = get_option(CP_PAYLINK_ENABLE_TEST_MODE);
    $identifier_out = '';
    $email_out = '';
    $name_out = '';
    $amount_out = 0;
    $accept_terms_and_conditions_out = false;
    
    $f1 = cp_paylink_config_stack()->get('identifier');
    $identifier_in = filter_input(INPUT_POST, 'identifier', FILTER_DEFAULT, FILTER_REQUIRE_SCALAR);
    $f1_valid = (!is_null($f1) && !is_null($identifier_in)) && $f1->parse($identifier_in, $identifier_out);
    
    $f2 = cp_paylink_config_stack()->get('email');
    $email_in = filter_input(INPUT_POST, 'email', FILTER_DEFAULT, FILTER_REQUIRE_SCALAR);
    $f2_valid = (!is_null($f2) && !is_null($email_in)) && $f2->parse($email_in, $email_out);
    
    // Note: Name field had to be renamed to customer-name, as name field is
    // a Wordpress field that (presumably) relates to the name of either a link
    // or a particular blog post (using the slug). Consequently, on entering
    // text into the 'name' page, Wordpress was attempting to resolve the
    // name in preference to the page identifier with the result that a page
    // not found error / template page was being generated and output.
    //
    // May require an element of white / black listing on field names to avoid
    // this situation, particularly if caused by end users' inadvertent use of
    // special keywords.
    $f3 = cp_paylink_config_stack()->get('customer-name');
    $name_in = filter_input(INPUT_POST, 'customer-name', FILTER_DEFAULT, FILTER_REQUIRE_SCALAR);
    $f3_valid = (!is_null($f3) && !is_null($name_in)) && $f3->parse($name_in, $name_out);
 
    $f4 = cp_paylink_config_stack()->get('amount');
    $amount_in = filter_input(INPUT_POST, 'amount', FILTER_DEFAULT, FILTER_REQUIRE_SCALAR);
    $f4_valid = (!is_null($f4) && !is_null($amount_in)) && $f4->parse($amount_in, $amount_out);
       
    $f5 = cp_paylink_config_stack()->get('accept-terms-and-conditions');
    $accept_terms_and_conditions_in = filter_input(INPUT_POST, 'accept-terms-and-conditions', FILTER_DEFAULT, FILTER_REQUIRE_SCALAR);
    $f5_valid = (!is_null($f5)) && $f5->parse($accept_terms_and_conditions_in, $accept_terms_and_conditions_out);
    
    if (!$f1_valid || !$f2_valid || !$f3_valid || !$f4_valid || !$f5_valid) { 
        return CP_PAYLINK_PROCESSING_ERROR_DATA_INPUT_ERROR;
    }
   
    if (get_option('permalink_structure')) {
        $current_url = get_permalink($page_id);
    } else {
        $current_url = add_query_arg('page_id', $page_id, get_home_url());
    }
    
    $postback_url = add_query_arg(CP_PAYLINK_DISPATCHER, 'postback', $current_url);
    $success_url = add_query_arg(CP_PAYLINK_DISPATCHER, 'success', $current_url);
    $failure_url = add_query_arg(CP_PAYLINK_DISPATCHER, 'failure', $current_url);
    
    $logger = new CityPay_Logger(__FILE__);
    $paylink = new CityPay_PayLink($logger);   
    $paylink->setRequestCart(
            $merchant_id,
            $licence_key,
            $identifier_out,
            $amount_out,
            ''
        );
       
    $paylink->setRequestAddress(
            $name_out['first-name'], 
            $name_out['last-name'],
            '', '', '', '', '', '',
            $email_out,
            ''
        );
    
    $paylink->setRequestClient('Wordpress', get_bloginfo('version', 'raw'));
    $paylink->setRequestConfig(
            $test_mode,
            $postback_url,
            $success_url,
            $failure_url
        );
       
    $merchant_email = get_option(CP_PAYLINK_MERCHANT_EMAIL_ADDRESS);
    if (!empty($merchant_email)) {
        $paylink->setRequestMerchant($merchant_email);
        $enable_merchant_email = get_option(CP_PAYLINK_ENABLE_MERCHANT_EMAIL, false);
        if (!$enable_merchant_email) {
            $paylink->setRequestConfigOption('BYPASS_MERCHANT_EMAIL');
        }
    }
    
    $fields = &cp_paylink_config_stack()->getFields();
    foreach ($fields as $field) {
        if ($field->passthrough === true) {
            $paylink->setCustomParameter(
                    $field->name,
                    $field->value,
                    array('fieldType' => 'hidden')
                );
        }
    }
    
    try {
        $url = $paylink->getPaylinkURL();
        wp_redirect($url);
        exit;
    } catch (Exception $e) {
        return CP_PAYLINK_PROCESSING_ERROR_PAYLINK_ERROR;
    }
}

function cp_paylink_init() {    
    if (isset($_GET[CP_PAYLINK_DISPATCHER])) {
        add_filter('query_vars', 'cp_paylink_add_query_vars_filter');
        add_action('template_redirect', 'cp_paylink_template_redirect_dispatcher');
    } else {
        add_shortcode('citypay-payform-display', 'cp_paylink_payform_display');
        add_shortcode('citypay-payform-amount-field', 'cp_paylink_payform_amount_field');
        add_shortcode('citypay-payform-checkbox-field', 'cp_paylink_payform_checkbox_field');
        add_shortcode('citypay-payform-field', 'cp_paylink_payform_field');
        add_shortcode('citypay-payform-on-error', 'cp_paylink_shortcode_sink');
        add_shortcode('citypay-payform-on-page-load', 'cp_paylink_payform_on_page_load');
        add_shortcode('citypay-payform-on-redirect-success', 'cp_paylink_shortcode_sink');
        add_shortcode('citypay-payform-on-redirect-failure', 'cp_paylink_shortcode_sink');
        add_shortcode('citypay-payform-on-redirect-cancel', 'cp_paylink_shortcode_sink');
        add_shortcode('citypay-payform', 'cp_paylink_shortcode_passthrough');
        add_action('admin_menu', 'cp_paylink_administration');
        //add_filter('wp_headers', array('cp_paylinkjs_send_cors_headers'));
        add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'cp_paylink_settings_link');
    }
}

function cp_paylink_wp_loaded() {
    return;
}

function cp_paylink_template_redirect_on_redirect_failure()
{
    $page_id = get_query_var('page_id');
    $page_post = get_post($page_id);

    add_shortcode('citypay-payform-amount-field', 'cp_paylink_payform_amount_field');
    add_shortcode('citypay-payform-checkbox-field', 'cp_paylink_payform_checkbox_field');
    add_shortcode('citypay-payform-field', 'cp_paylink_payform_field');
    add_shortcode('citypay-payform-on-error', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-on-page-load', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-success', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-failure', 'cp_paylink_shortcode_passthrough');
    add_shortcode('citypay-payform-on-redirect-cancel', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform', 'cp_paylink_shortcode_passthrough');
    
    do_shortcode($page_post->post_content);
}

function cp_paylink_template_redirect_on_postback()
{
    ob_clean();
    header('HTTP/1.1 200 OK');
    exit;
}

function cp_paylink_template_redirect_on_redirect_success()
{
    $page_id = get_query_var('page_id');
    $page_post = get_post($page_id);

    add_shortcode('citypay-payform-amount-field', 'cp_paylink_payform_amount_field');
    add_shortcode('citypay-payform-checkbox-field', 'cp_paylink_payform_checkbox_field');
    add_shortcode('citypay-payform-field', 'cp_paylink_payform_field');
    add_shortcode('citypay-payform-on-error', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-on-page-load', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-success', 'cp_paylink_shortcode_passthrough');
    add_shortcode('citypay-payform-on-redirect-failure', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-cancel', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform', 'cp_paylink_shortcode_passthrough');

    do_shortcode($page_post->post_content);
}

function cp_paylink_template_redirect_dispatcher() {
    
    //echo '<pre>'.$_GET[CP_PAYLINK_DISPATCHER].'</pre>';
    
    if (isset($_GET[CP_PAYLINK_DISPATCHER])) {
        $action = $_GET[CP_PAYLINK_DISPATCHER];
        switch ($action)
        {
        case 'pay':
            $r = cp_paylink_action_pay();
            if ($r != CP_PAYLINK_NO_ERROR) {
                remove_shortcode('citypay-payform');
                remove_shortcode('citypay-payform-on-redirect-success');
                remove_shortcode('citypay-payform-on-redirect-failure');
                remove_shortcode('citypay-payform-on-redirect-cancel');
                remove_shortcode('citypay-payform-on-page-load');
                remove_shortcode('citypay-payform-on-error');
                remove_shortcode('citypay-payform-display');
                remove_shortcode('citypay-payform-field');
                remove_shortcode('citypay-payform-checkbox-field');
                remove_shortcode('citypay-payform-amount-field');
                
                add_shortcode('citypay-payform-display', 'cp_paylink_payform_display');
                add_shortcode('citypay-payform-amount-field', 'cp_paylink_shortcode_sink');
                add_shortcode('citypay-payform-checkbox-field', 'cp_paylink_shortcode_sink');
                add_shortcode('citypay-payform-field', 'cp_paylink_shortcode_sink');
                
                switch ($r)
                {
                case CP_PAYLINK_PROCESSING_ERROR_DATA_INPUT_ERROR:
                    add_shortcode('citypay-payform-on-error', 'cp_paylink_shortcode_sink');
                    add_shortcode('citypay-payform-on-page-load', 'cp_paylink_shortcode_passthrough');
                    break;
                    
                case CP_PAYLINK_PROCESSING_ERROR_PAYLINK_ERROR:
                    add_shortcode('citypay-payform-on-error', 'cp_paylink_shortcode_passthrough');
                    add_shortcode('citypay-payform-on-page-load', 'cp_paylink_shortcode_sink');
                    break;
                }
                
                add_shortcode('citypay-payform-on-redirect-success', 'cp_paylink_shortcode_sink');
                add_shortcode('citypay-payform-on-redirect-failure', 'cp_paylink_shortcode_sink');
                add_shortcode('citypay-payform-on-redirect-cancel', 'cp_paylink_shortcode_sink');
                add_shortcode('citypay-payform', 'cp_paylink_shortcode_passthrough');
                add_action('admin_menu', 'cp_paylink_administration');
                //add_filter('wp_headers', array('cp_paylinkjs_send_cors_headers'));
                add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'cp_paylink_settings_link');
            }
            break;

        case 'postback':
            cp_paylink_template_redirect_on_postback();
            break;

        case 'success':
            cp_paylink_template_redirect_on_redirect_success();
            break;

        case 'failure':
            cp_paylink_template_redirect_on_redirect_failure();
            break;

        default:
            break;
        }
    }
}

function cp_paylink_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=cp-paylink-settings">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

function cp_paylink_administration() {
    add_options_page( 
        __('CityPay PayLink WP', 'cp-paylink-wp'),
        __('CityPay PayLink WP', 'cp-paylink-wp'),
        'manage_options',
        'cp-paylink-settings',
        'cp_paylink_settings_page'
    );
}

function cp_paylink_settings_merchant_id() {
    $option = get_option(CP_PAYLINK_MERCHANT_ID);
    echo "<input type='text' id='"
        . CP_PAYLINK_MERCHANT_ID
        . "' name='"
        . CP_PAYLINK_MERCHANT_ID
        . "' value='${option}' size='16'></input>";
}

function cp_paylink_settings_licence_key() {
    $option = get_option(CP_PAYLINK_LICENCE_KEY);
    echo "<input type='text' id='"
        . CP_PAYLINK_LICENCE_KEY
        . "' name='"
        . CP_PAYLINK_LICENCE_KEY
        . "' value='${option}' size='16'></input>";
}

function cp_paylink_settings_merchant_email_address() {
    $option = get_option(CP_PAYLINK_MERCHANT_EMAIL_ADDRESS);
    echo "<input type='text' id='"
        . CP_PAYLINK_MERCHANT_EMAIL_ADDRESS
        . "' name='"
        . CP_PAYLINK_MERCHANT_EMAIL_ADDRESS
        . "' value='${option}' size='60'></input>";
}

function cp_paylink_settings_enable_merchant_email() {
    $option = get_option(CP_PAYLINK_ENABLE_MERCHANT_EMAIL, false);
    echo "<input type='checkbox' id='"
        . CP_PAYLINK_ENABLE_MERCHANT_EMAIL
        . "' name='"
        . CP_PAYLINK_ENABLE_MERCHANT_EMAIL
        . "'"
        . ($option?' checked':'')
        . '></input>';
}

function cp_paylink_settings_enable_test_mode() {
    $option = get_option(CP_PAYLINK_ENABLE_TEST_MODE, true);
    echo "<input type='checkbox' id='"
        . CP_PAYLINK_ENABLE_TEST_MODE
        . "' name='"
        . CP_PAYLINK_ENABLE_TEST_MODE
        . "' "
        . ($option?' checked':'')
        . "></input> Generate transactions using test mode"
        . "<p class='description'>Use this whilst testing your integration. "
        . "You must disable test mode when you are ready to take live "
        . "transactions.</p>";
}

function cp_paylink_settings_enable_debug_mode() {
    $option = get_option(CP_PAYLINK_ENABLE_DEBUG_MODE, true);
    echo "<input type='checkbox' id='"
        . CP_PAYLINK_ENABLE_DEBUG_MODE
        . "' name='"
        . CP_PAYLINK_ENABLE_DEBUG_MODE
        . "'"
        . ($option?' checked':'')
        . "></input> Enable logging<p class='description'>Log payment events, "
        . "such as postback requests, inside <code>"
        . CityPay_Logger::logFilePathName(__FILE__)
        . "</code>.</p>";
}

function cp_paylink_settings_validate_merchant_id($input) {
    if (!CityPay_Validation::validateMerchantId($input)) {
        $output = get_option(CP_PAYLINK_MERCHANT_ID);
        add_settings_error(
            'merchant-id',
            'invalid-merchant-id',
            __('Invalid merchant identifier provided.', 'invalid-merchant-id'),
            'error'
        );
    } else {
        $output = $input;
    }
    
    return apply_filters('cp_paylink_settings_validate_merchant_id', $output, $output);
}

function cp_paylink_settings_validate_licence_key($input) {
    if (!CityPay_Validation::validateLicenceKey($input)) {
        $output = get_option(CP_PAYLINK_LICENCE_KEY);
        add_settings_error(
            'licence-key',
            'invalid-licence-key',
            __('Invalid licence key provided.', 'invalid-licence-key'),
            'error'
        );
    } else {
        $output = $input;
    }
    
    return apply_filters('cp_paylink_settings_validate_licence_key', $output, $output);
}

function cp_paylink_settings_validate_merchant_email_address($input) {
    if (!CityPay_Validation::validateEmailAddress($input) && !empty($input)) {
        $output = get_option(CP_PAYLINK_MERCHANT_EMAIL_ADDRESS);
        add_settings_error(
            'merchant-email-address',
            'invalid-email-address',
            __('Invalid email address provided.', 'invalid-email-address'),
            'error'
        );
    } else {
        $output = $input;
    }
    
    return apply_filters('cp_paylink_settings_validate_merchant_email_address', $output, $output);
}

function cp_paylink_settings_validate_enable_merchant_email($input) {
    if (!CityPay_Validation::validateCheckboxValue($input)) {
        $output = get_option(CP_PAYLINK_ENABLE_MERCHANT_EMAIL);
        add_settings_error(
            'enable-merchant-email',
            'invalid-checkbox-value',
            __('Invalid checkbox value for enable merchant email setting.', 'invalid-checkbox-value'),
            'error'
        );
    } else {
        $output = $input;
    }
    
    return apply_filters('cp_paylink_settings_validate_enable_merchant_email', $output, $output);
}

function cp_paylink_settings_validate_enable_test_mode($input) {
    if (!CityPay_Validation::validateCheckboxValue($input)) {
        $output = get_option(CP_PAYLINK_ENABLE_TEST_MODE);
        add_settings_error(
            'enable-test-mode',
            'invalid-checkbox-value',
            __('Invalid checkbox value for enable test mode setting.', 'invalid-checkbox-value'),
            'error'
        );
    } else {
        $output = $input;
    }
    
    return apply_filters('cp_paylink_settings_validate_enable_test_mode', $output, $output);
}

function cp_paylink_settings_validate_enable_debug_mode($input) {
    if (!CityPay_Validation::validateCheckboxValue($input)) {
        $output = get_option(CP_PAYLINK_ENABLE_DEBUG_MODE);
        add_settings_error(
            'enable-debug-mode',
            'invalid-checkbox-value',
            __('Invalid checkbox value for enable debug mode setting.', 'invalid-checkbox-value'),
            'error'
        );
    } else {
        $output = $input;
    }
    
    return apply_filters('cp_paylink_settings_validate_enable_debug_mode', $output, $output);
}

function cp_paylink_settings_main_section_text($input) {
    echo "Main section text";
}

function cp_paylink_admin_init() {
    
    add_settings_section(
        'main_section',
        'Main Settings',
        'cp_paylink_settings_main_section_text',
        'cp-paylink-settings'
    );
    
    add_settings_field(
        CP_PAYLINK_MERCHANT_ID,
        'Merchant identifier',
        'cp_paylink_settings_merchant_id',
        'cp-paylink-settings',
        'main_section',
        array(
            'label_for' => 'Merchant identifier'
        )
    );
    
    add_settings_field(
        'licence-key',
        'Licence Key',
        'cp_paylink_settings_licence_key',
        'cp-paylink-settings',
        'main_section',
        array(
            'label_for' => 'Licence key'
        )
    );
    
    //add_settings_group();
    add_settings_field(
        'merchant-email-address',
        'Email address',
        'cp_paylink_settings_merchant_email_address',
        'cp-paylink-settings',
        'main_section',
        array(
            'label_for' => 'Email address'
        )
    );
    
    add_settings_field(
        'enable-merchant-email',
        'Enable merchant email',
        'cp_paylink_settings_enable_merchant_email',
        'cp-paylink-settings',
        'main_section',
        array(
            'label_for' => 'Enable merchant email'
        )
    );
    
    add_settings_field(
        'enable-test-mode',
        'Enable test mode',
        'cp_paylink_settings_enable_test_mode',
        'cp-paylink-settings',
        'main_section',
        array(
            'label_for' => 'Enable test mode'
        )
    );
    
    add_settings_field(
        'enable-debug-mode',
        'Enable debug mode',
        'cp_paylink_settings_enable_debug_mode',
        'cp-paylink-settings',
        'main_section',
        array(
            'label_for' => 'Enable debug mode'
        )
    );
    
    register_setting('cp-paylink-settings', CP_PAYLINK_MERCHANT_ID, 'cp_paylink_settings_validate_merchant_id');
    register_setting('cp-paylink-settings', CP_PAYLINK_LICENCE_KEY, 'cp_paylink_settings_validate_licence_key');
    register_setting('cp-paylink-settings', CP_PAYLINK_MERCHANT_EMAIL_ADDRESS, 'cp_paylink_settings_validate_merchant_email_address');
    register_setting('cp-paylink-settings', CP_PAYLINK_ENABLE_MERCHANT_EMAIL, 'cp_paylink_settings_validate_enable_merchant_email');
    register_setting('cp-paylink-settings', CP_PAYLINK_ENABLE_TEST_MODE, 'cp_paylink_settings_validate_enable_test_mode');
    register_setting('cp-paylink-settings', CP_PAYLINK_ENABLE_DEBUG_MODE, 'cp_paylink_settings_validate_enable_debug_mode');
}

function cp_paylink_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
 
    echo '<div class=""><h1>'
        .__('CityPay PayLink for WordPress', 'cp-paylink-wp')
        .'</h1>';
    
    settings_errors();
    
    echo '<form method="post" action="options.php">';
    
    settings_fields('cp-paylink-settings');
    do_settings_sections('cp-paylink-settings');
    submit_button();
    
    echo '</form></div>';
}

function cp_paylink_exempt_shortcodes_from_texturize($shortcodes) {
    $shortcodes[] = 'citypay-payform';
    return $shortcodes;
}

add_action('init', 'cp_paylink_init');
add_action('admin_init', 'cp_paylink_admin_init');
add_action('wp_loaded', 'cp_paylink_wp_loaded');
add_filter('no_texturize_shortcodes', 'cp_paylink_exempt_shortcodes_from_texturize');