<?php
/**
 * Plugin Name: CityPay PayLink PayForm WP
 * Plugin URI: http://citypay.com/paylink
 * Description: Include an arbitrary payment processing form.
 * Version: 1.2.1
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

define('CP_PAYLINK_VERSION', '1.2.1');
define('CP_PAYLINK_DISPATCHER', 'cp_paylink');
define('CP_PAYLINK_MERCHANT_ID', 'cp_paylink_merchant_id');
define('CP_PAYLINK_LICENCE_KEY', 'cp_paylink_licence_key');
define('CP_PAYLINK_IDENTIFIER_PREFIX', 'cp_paylink_identifier_prefix');
define('CP_PAYLINK_MERCHANT_EMAIL_ADDRESS', 'cp_paylink_merchant_email_address');
define('CP_PAYLINK_ENABLE_MERCHANT_EMAIL', 'cp_paylink_enable_merchant_email');
define('CP_PAYLINK_ENABLE_TEST_MODE', 'cp_paylink_enable_test_mode');
define('CP_PAYLINK_ENABLE_DEBUG_MODE', 'cp_paylink_enable_debug_mode');

define('CP_PAYLINK_OPT_VERSION', 'cp_paylink_version');

define('CP_PAYLINK_NAME_REGEX', '/^\s*\b(?:(Mr|Mrs|Miss|Dr)\b\.?+)?+\s*\b([\w\-]+)\b\s+\b(\b\w\b)?\s*([\w\-\s]+?)\s*$/i');
define('CP_PAYLINK_IDENTIFIER_REGEX', '/^[^\s]{5,}$/');

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
    => __('This field cannot be empty'),
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

global $redirect_success_url;
global $redirect_failure_url;


define('CP_PAYLINK_PROCESSING_ERROR_DATA_INPUT_ERROR', 0x05);
define('CP_PAYLINK_PROCESSING_ERROR_PAYLINK_ERROR', 0x06);

function cp_paylink_install()
{
    $current_version = get_option(CP_PAYLINK_OPT_VERSION);
    switch ($current_version) {
        case "1.1.1":
            break;

        default:
            $test_mode = get_option('cp_paylink_test_mode');
            if (!is_null($test_mode)) {
                add_option(CP_PAYLINK_ENABLE_TEST_MODE, $test_mode);
                delete_option('cp_paylink_test_mode');
            }

            $debug_mode = get_option('cp_paylink_debug_mode');
            if (!is_null($debug_mode)) {
                add_option(CP_PAYLINK_ENABLE_DEBUG_MODE, $debug_mode);
                delete_option('cp_paylink_debug_mode');
            }
    }

    update_option(CP_PAYLINK_OPT_VERSION, CP_PAYLINK_VERSION);
}

function cp_paylink_config_stack()
{
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

function cp_paylink_send_cors_headers($headers)
{
    error_log("send_cors_headers: " . $headers);
    $headers['Access-Control-Allow-Origin'] = "https://secure.citypay.com";
    return $headers;
}

function cp_paylink_add_query_vars_filter($vars)
{
    $vars[] = "cp_paylink";
    return $vars;
}

function cp_paylink_payform_field_config_sort($val1, $val2)
{
    if ($val1->order > $val2->order) {
        return 1;
    } elseif ($val1->order < $val2->order) {
        return -1;
    } else {
        return 0;
    }
}

class cp_paylink_field
{
    public $id, $label, $name, $order, $placeholder;
    private $value, $content;
    public $passthrough;
    public $error, $error_message;

    public function __construct($identifier, $name, $label, $placeholder = '', $order = 99, $content = null, $passthrough = false)
    {
        $this->id = $identifier;
        $this->name = $name;
        $this->label = $label;
        $this->placeholder = $placeholder;
        $this->order = $order;
        $this->content = $content;
        $this->passthrough = $passthrough;
    }

    public function configure_error_message($attrs, $content = null)
    {
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

    /**
     * The parse method is responsible for parsing the field input to
     * generate the internal state for the object.
     *
     * @param type $value_in
     *   the value to be parsed.
     *
     * @return boolean
     *   returns true if the parsing process was successful, and false
     *   if it was not successful.
     */
    public function parse($value_in)
    {
        $this->value = $value_in;
        return true;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getErrorMessage()
    {
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

    public function getValue()
    {
        return $this->value;
    }

    public function setContent($content = null)
    {
        $this->content = $content;
    }

    public function setValue($value = null)
    {
        $this->value = $value;
    }
}

class cp_paylink_text_field extends cp_paylink_field
{
    public $pattern;
    private $optional;

    public function __construct($identifier, $name, $label, $placeholder = '', $pattern = '', $order = 99, $content = null, $passthrough = false, $optional = false)
    {
        parent::__construct($identifier, $name, $label, $placeholder, $order, $content, $passthrough);
        $this->pattern = $pattern;
        $this->optional = $optional;
    }

    public function parse($value_in)
    {
        parent::parse($value_in);
        return true; // pass through the upstream parse process and let the individual implementations validate
    }

    public function isOptional()
    {
        return $this->optional;
    }
}

class cp_paylink_checkbox_field extends cp_paylink_field
{
    public function __construct($identifier, $name, $label, $order = 99, $content = null, $passthrough = false)
    {
        parent::__construct($identifier, $name, $label, '', $order, $content, $passthrough);
    }

    public function isChecked()
    {
        return (parent::getValue() === 'on');
    }
}

class cp_paylink_amount_field extends cp_paylink_text_field
{
    private $amount, $decimal_places, $minimum, $maximum;

    private static function _parse_amount($in, &$out, $decimal_places = null)
    {

        $_in = trim($in);
        $_out = 0;
        $index = 0;
        $i_max = strlen($_in);

        if ($i_max <= 0x00) {
            return CP_PAYLINK_AMOUNT_PARSE_ERROR_EMPTY_STRING;
        }

        while ($index < $i_max) {
            $c = ord($_in[$index]);
            if ($c >= 48 && $c <= 57) {
                $_out = ($_out * 10) + ($c - 48);
                $index++;
            } else if ($c === ord('.')) {
                break;
            } else {
                return CP_PAYLINK_AMOUNT_PARSE_ERROR_INVALID_CHARACTER;
            }
        }

        $_out *= 100;

        if ($index >= $i_max) {
            $out = $_out;
            return CP_PAYLINK_NO_ERROR;
        }

        if ($c == ord('.')) {
            $index++;
            $pence = 0;

            if (!is_null($decimal_places) && $i_max > $index + $decimal_places) {
                return CP_PAYLINK_AMOUNT_PARSE_ERROR_INVALID_PRECISION;
            }

            $j = $decimal_places;
            while ($index < $i_max) {
                $c = ord($_in[$index]);
                if ($c >= 48 && $c <= 57) {
                    $pence = ($pence * 10) + ($c - 48);
                    $index++;
                    $j--;
                } else {
                    return CP_PAYLINK_AMOUNT_PARSE_ERROR_INVALID_CHARACTER;
                }
            }

            if ($j > 0x00) {
                $pence = $pence * pow(10, $j);
            }

            $_out += $pence;
        }

        $out = $_out;
//        echo "<br/>PPPLLLL RETURNING NO ERROR:" . $in;
        return CP_PAYLINK_NO_ERROR;
    }

    public function __construct($identifier, $name, $label, $placeholder = '', $order = 99, $decimal_places = null, $minimum = null, $maximum = null)
    {
        parent::__construct($identifier, $name, $label, $placeholder, null, $order, null, false, false);
        if (is_null($decimal_places)) {
            $this->decimal_places = null;
        } else {
            $this->decimal_places = intval($decimal_places);
        }

        if (is_null($minimum)) {
            $this->minimum = null;
        } else {
            $r = self::_parse_amount($minimum, $this->minimum, $decimal_places);
            if (!$r) {
                ;
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

    public function parse($value_in, $decimal_places = null)
    {
        parent::parse($value_in);

        $_decimal_places = (!is_null($decimal_places) ? $decimal_places : $this->decimal_places);
        $result = self::_parse_amount(parent::getValue(), $this->amount, $_decimal_places);
        if ($result == CP_PAYLINK_NO_ERROR) {
            if (!empty($this->minimum) && $this->amount < $this->minimum) {
                $this->error = CP_PAYLINK_AMOUNT_PARSE_ERROR_BELOW_MINIMUM_VALUE;
                return false;
            } else if (!empty($this->maximum) && $this->amount > $this->maximum) {
                $this->error = CP_PAYLINK_AMOUNT_PARSE_ERROR_ABOVE_MAXIMUM_VALUE;
                return false;
            } else {
                $this->error = CP_PAYLINK_NO_ERROR;
                return true;
            }
        } else {
            $this->error = $result;
            return false;
        }
    }

    public function getAmount()
    {
        return $this->amount;
    }
}

class cp_paylink_customer_name_field extends cp_paylink_text_field
{
    public $salutation;
    public $first_name;
    public $middle_initial;
    public $last_name;

    public function parse($value_in)
    {
        if (!parent::parse($value_in)) {
            return false;
        }
        if (strlen(parent::getValue()) == 0x00) {
            $this->error = CP_PAYLINK_NAME_FIELD_PARSE_ERROR_EMPTY_STRING;
            return false;
        } else {
            $matches = array();

            if (preg_match(CP_PAYLINK_NAME_REGEX, parent::getValue(), $matches)) {
                $this->salutation = $matches[1];
                $this->first_name = $matches[2];
                $this->middle_initial = $matches[3];
                $this->last_name = $matches[4];
                return true;
            } else {
                $this->error = CP_PAYLINK_NAME_FIELD_PARSE_ERROR_NOT_VALID;
                return false;
            }
        }
    }
}

class cp_paylink_email_field extends cp_paylink_text_field
{
    public function parse($value_in)
    {
        if (!parent::parse($value_in)) {
            return false;
        }
        if (strlen(trim(parent::getValue())) == 0x00) {
            $this->error = CP_PAYLINK_EMAIL_ADDRESS_FIELD_PARSE_ERROR_EMPTY_STRING;
            return false;
        } else if (!CityPay_Validation::validateEmailAddress(trim(parent::getValue()))) {
            $this->error = CP_PAYLINK_EMAIL_ADDRESS_FIELD_PARSE_ERROR_NOT_VALID;
            return false;
        }
        return true;
    }
}

class cp_paylink_identifier_field extends cp_paylink_text_field
{
    public function parse($value_in)
    {
        if (!parent::parse($value_in)) {
            return false;
        }
        if (strlen($this->getValue()) == 0x00) {
            $this->error = CP_PAYLINK_IDENTIFIER_FIELD_PARSE_ERROR_EMPTY_STRING;
            return false;
        } else {

            if (preg_match_all(CP_PAYLINK_IDENTIFIER_REGEX, get_option(CP_PAYLINK_IDENTIFIER_PREFIX) . parent::getValue()) &&
                mb_strlen(get_option(CP_PAYLINK_IDENTIFIER_PREFIX) . parent::getValue()) > 4 &&
                mb_strlen(get_option(CP_PAYLINK_IDENTIFIER_PREFIX) . parent::getValue()) < 51) {
                return true;
            } else {
                $this->error = CP_PAYLINK_IDENTIFIER_FIELD_PARSE_ERROR_NOT_VALID;
                return false;
            }
        }
    }
}

class cp_paylink_accept_terms_and_conditions_checkbox_field extends cp_paylink_checkbox_field
{
    public function parse($value_in)
    {
        if (!parent::parse($value_in)) {
            return false;
        }
        if (!parent::isChecked()) {
            $this->error = CP_PAYLINK_TERMS_AND_CONDITIONS_NOT_ACCEPTED;
            return false;
        }
        return true;
    }
}

function cp_paylink_payform_amount_field($attrs, $content = null)
{
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

    cp_paylink_config_stack()->set($field->name, $field);

    return '';
}

function cp_paylink_payform_field($attrs, $content = null)
{
    $a = shortcode_atts(
        array(
            'label' => '',
            'name' => '',
            'order' => 99,
            'placeholder' => '',
            'pattern' => '',
            'type' => 'text',
            'id' => null,
            'passthrough' => false,
            'optional' => false
        ),
        $attrs
    );

    switch ($a['type']) {
        case 'customer-name':
            $field = new cp_paylink_customer_name_field(
                $a['id'],
                $a['name'],
                $a['label'],
                $a['placeholder'],
                null,
                $a['order'],
                null,
                (bool)$a['passthrough']
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
                (bool)$a['passthrough']
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
                (bool)$a['passthrough']
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
                (bool)$a['passthrough'],
                (bool)$a['optional']
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

function cp_paylink_payform_checkbox_field($attrs, $content = null)
{
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

    switch ($a['type']) {
        case 'accept-terms-and-conditions':
            $field = new cp_paylink_accept_terms_and_conditions_checkbox_field(
                $a['id'],
                $a['name'],
                $a['label'],
                $a['order'],
                null,
                (is_null($a['passthrough']) ? true : ($a['passthrough'] === "true"))
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
                (bool)$a['passthrough']
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

function cp_paylink_shortcode_sink($attrs, $content = null)
{
    if (!is_null($content)) {
        //cp_paylink_config_stack()->push_new();
        do_shortcode($content);
    }
    return '';
}

class cp_paylink_tag
{
    public $tag;
    public $attrs;
    public $start;
    public $end;
    public $tag_type;
    public $is_matched;

    public function __construct($tag, $attrs, $start, $end, $tag_type)
    {
        $this->tag = $tag;
        $this->attrs = $attrs;
        $this->start = $start;
        $this->end = $end;
        $this->tag_type = $tag_type;
    }
}

class cp_paylink_attr
{
    public $name;
    public $value;

    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }
}

class cp_paylink_text
{
    public $text;

    public function __construct($text)
    {
        $this->text = $text;
    }
}

function cp_paylink_shortcode_passthrough($attrs, $content = null)
{
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

function cp_paylink_payform_display_text_field_default($field)
{
    $s = '<div class="form-group" id="'
        . $field->id
        . '"><label class="com-sm-2 control-label">'
        . $field->label
        . '</label><div class="col-sm-10"><input class="form-control" name="'
        . $field->name
        . '" type="text" value="';

    $s .= $field->getValue();

    $s .= '" placeholder="'
        . $field->placeholder
        . '">';

    $e = $field->getErrorMessage();
    if (!empty($e)) {
        $s .= '<span id="error"><em> ' . $e . '</em></span>';
    }

    $s .= '</div></div>';

    return $s;
}

function cp_paylink_payform_display_checkbox_field_default($field)
{
    $s = '<div class="form-group" id="'
        . (!is_null($field->id) ? $field->id : '')
        . '"><label class="com-sm-3 control-label">'
        . $field->label
        . '</label><div class="col-sm-10"><input class="form-control" name="'
        . $field->name
        . '" type="checkbox"'
        . ($field->isChecked() ? ' checked="on"' : '')
        . ' />';
    $c = null;
    $c .= $field->getContent();
    if (!empty($c)) {
        $s .= $c;
    }

    $e = $field->getErrorMessage();
    if (!empty($e)) {
        $s .= '<span id="error"><em>' . $e . '</em></span>';
    }

    $s .= '</div></div>';

    return $s;
}

function cp_paylink_payform_display_default($attrs, $content = null)
{
    // if a configuration has been specified
    $current_url = get_permalink();
    $s = trim($content)
        . '<form role="form" id="billPaymentForm" class="form-horizontal uk-form" method="POST" action="'
        . add_query_arg('cp_paylink', 'pay', $current_url)
        . '"><input type="hidden" name="cp_paylink_pay" value="Y">';

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
        . $attrs['submit']
        . '</button></form>';

    return $s;
}

function cp_paylink_payform_display($attrs, $content = null)
{
    $a = shortcode_atts(
        array('submit' => __('Pay', 'cp_paylink_pay')),
        $attrs
    );

    if (is_single() || is_page()) {
        if (function_exists('cp_paylink_payform_display_custom')) {
            return cp_paylink_payform_display_custom($a, $content);
        } else {
            return cp_paylink_payform_display_default($a, $content);
        }
    } else {
        return '';
    }
}

function cp_paylink_payform_on_page_load($attrs, $content = null)
{
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

function cp_paylink_action_pay()
{
    require_once('includes/class-citypay-logger.php');
    require_once('includes/class-citypay-paylink.php');

    $page_id = get_query_var('page_id');
    $page_post = get_post($page_id);

    add_shortcode('citypay-payform-amount-field', 'cp_paylink_payform_amount_field');
    add_shortcode('citypay-payform-checkbox-field', 'cp_paylink_payform_checkbox_field');
    add_shortcode('citypay-payform-field', 'cp_paylink_payform_field');
    add_shortcode('citypay-payform-on-error', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-on-page-load', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-success', 'redirect_url_success');
    add_shortcode('citypay-payform-on-redirect-failure', 'redirect_url_failure');
    add_shortcode('citypay-payform-on-redirect-cancel', 'redirect_url_cancel');
    add_shortcode('citypay-payform', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform', 'cp_paylink_shortcode_passthrough');
    add_shortcode('citypay-pay-btn', 'cp_paylink_standalone_button');

    do_shortcode($page_post->post_content);

    $merchant_id = get_option(CP_PAYLINK_MERCHANT_ID);
    $licence_key = get_option(CP_PAYLINK_LICENCE_KEY);
    $identifier_prefix = get_option(CP_PAYLINK_IDENTIFIER_PREFIX);
    $test_mode = get_option(CP_PAYLINK_ENABLE_TEST_MODE);

    $fields = &cp_paylink_config_stack()->getFields();

    foreach ($fields as $key => $field) {
        $field->parse(filter_input(INPUT_POST, $key, FILTER_DEFAULT, FILTER_REQUIRE_SCALAR));
    }

    $f_identifier = $fields['identifier'];
//    $f_identifier_valid = (!is_null($f_identifier) && !empty($f_identifier->getValue()));
//    $f_identifier_invalid = (is_null($f_identifier) || empty($f_identifier->getValue()) );


    $f_email = $fields['email'];
//    $f_email_valid = (!is_null($f_email) && !empty($f_email->getValue()));
//    $f_email_invalid = (is_null($f_email) || empty($f_email->getValue()));


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
    $f_name = $fields['customer-name'];
//    $f_name_valid = (!is_null($f_name) && !empty($f_name->getValue()));
//    $f_name_invalid = (is_null($f_name) || empty($f_name->getValue()));


    $f_amount = $fields['amount'];
//    $f_amount_valid = (!is_null($f_amount) && !empty($f_amount->getValue()));
//    $f_amount_invalid = (is_null($f_amount) || empty($f_amount->getValue()));

    $f_tnc = $fields['accept-terms-and-conditions'];
//    $f_tnc_valid = (!is_null($f_tnc) && $f_tnc->isChecked());
    $f_tnc_invalid = (is_null($f_tnc) || !$f_tnc->isChecked());

    $fN_valid = true;
    foreach ($fields as $key => $field) {
        if ($field instanceof cp_paylink_text_field) {
            $fN_valid = ($fN_valid && ($field->isOptional() || (!($field->isOptional() || empty($field->getValue())))));
        }
    }

    $fields_have_error = false;
    foreach ($fields as $key => $field) {
        if ($field instanceof cp_paylink_text_field) {

            if ($fields_have_error == false)
                $fields_have_error = ($field->getErrorMessage() != '');
//            echo "<li>".trim($field->getErrorMessage())." - ".(int)$fields_have_error."</li>";
        }
    }

//    echo "<br/> - ident: ".$f_identifier_invalid."<br/> - email:".$f_email_invalid."<br/> - name:".$f_name_invalid."<br/> - amount:".$f_amount_invalid."<br/> - tnc: ".$f_tnc_invalid."<br/> - fN:".!$fN_valid."<hr/>";
//    echo "<b>F: E: " . (int)$fields_have_error . "</b><br/>";

    if ($fields_have_error || $f_tnc_invalid) {
        return CP_PAYLINK_PROCESSING_ERROR_DATA_INPUT_ERROR;
    }


//    if ($f_identifier_invalid || $f_email_invalid || $f_name_invalid || $f_amount_invalid || $f_tnc_invalid || !$fN_valid) {
//        return CP_PAYLINK_PROCESSING_ERROR_DATA_INPUT_ERROR;
//    }

    if (get_option('permalink_structure')) {
        $current_url = get_permalink($page_id);
    } else {
        $current_url = add_query_arg('page_id', $page_id, get_home_url());
    }


    $postback_url = add_query_arg(CP_PAYLINK_DISPATCHER, 'postback', $current_url);

    $success_url = get_option('$redirect_success_url');

    $failure_url = get_option('$redirect_failure_url');


    $logger = new CityPay_Logger(__FILE__);
    $paylink = new CityPay_PayLink_WP($logger);

    $identifier = $f_identifier->getValue();
    $amount = $f_amount->getAmount();

    $paylink->setRequestCart(
        $merchant_id,
        $licence_key,
        $identifier_prefix,
        $identifier,
        $amount,
        ''
    );

    $email = $f_email->getValue();
    $paylink->setRequestAddress(
        $f_name->first_name,
        $f_name->last_name,
        '', '', '', '', '', '',
        trim($email),
        ''
    );

    $plugin_data = get_file_data(__FILE__, array('Version'));

    $paylink->setRequestClient(
        'Wordpress',
        get_bloginfo('version', 'raw'),
        'PayLink-PayForm',
        CP_PAYLINK_VERSION
    );

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

    foreach ($fields as $key => $field) {
        if ($field->passthrough === true) {
            $paylink->setCustomParameter(
                $field->name,
                $field->getValue(),
                array('fieldType' => 'hidden')
            );
        }
    }

    try {
        $url = $paylink->getPaylinkURL();
        wp_redirect($url);
        exit;
    } catch (Exception $e) {
//        echo $e;
        return CP_PAYLINK_PROCESSING_ERROR_PAYLINK_ERROR;
    }
}

function redirect_url_success($atts)
{
    $url = shortcode_atts(
        array(
            'url' => add_query_arg(CP_PAYLINK_DISPATCHER, 'success', get_permalink(get_query_var('page_id'))),
        ),
        $atts
    );
//    extract(shortcode_atts(array(
//        'url' => add_query_arg(CP_PAYLINK_DISPATCHER, 'success', get_permalink(get_query_var('page_id'))),
//    ), $atts));

    update_option('$redirect_success_url', $url['url']);


    return '';
}

function redirect_url_failure($atts)
{
    $url = shortcode_atts(
        array(
            'url' => add_query_arg(CP_PAYLINK_DISPATCHER, 'failure', get_permalink(get_query_var('page_id'))),
        ),
        $atts
    );
//    extract(shortcode_atts(array(
//        'url' => add_query_arg(CP_PAYLINK_DISPATCHER, 'failure', get_permalink(get_query_var('page_id'))),
//    ), $atts));

    update_option('$redirect_failure_url', $url['url']);
    return '';
}

function cp_paylink_init()
{
    ob_clean();
    ob_start();
    if (isset($_GET[CP_PAYLINK_DISPATCHER])) {
        add_filter('query_vars', 'cp_paylink_add_query_vars_filter');
        add_action('template_redirect', 'cp_paylink_template_redirect_dispatcher');
    } else {
        add_shortcode('citypay-payform-display', 'cp_paylink_payform_display');
        add_shortcode('citypay-payform-amount-field', 'cp_paylink_payform_amount_field');
        add_shortcode('citypay-payform-checkbox-field', 'cp_paylink_payform_checkbox_field');
        add_shortcode('citypay-payform-text-field', 'cp_paylink_payform_text_field');
        add_shortcode('citypay-payform-field', 'cp_paylink_payform_field');
        add_shortcode('citypay-payform-on-error', 'cp_paylink_shortcode_sink');
        add_shortcode('citypay-payform-on-page-load', 'cp_paylink_payform_on_page_load');
        add_shortcode('citypay-payform-on-redirect-success', 'cp_paylink_shortcode_sink');
        add_shortcode('citypay-payform-on-redirect-failure', 'cp_paylink_shortcode_sink');
        add_shortcode('citypay-payform-on-redirect-cancel', 'cp_paylink_shortcode_sink');
        add_shortcode('citypay-payform', 'cp_paylink_shortcode_passthrough');
        add_shortcode('citypay-pay-btn', 'cp_paylink_standalone_button');
        add_action('admin_menu', 'cp_paylink_administration');
        //add_filter('wp_headers', array('cp_paylinkjs_send_cors_headers'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cp_paylink_settings_link');
    }

}

function cp_paylink_wp_loaded()
{
    return;
}

function cp_paylink_template_redirect_on_redirect_failure()
{
    $page_id = get_query_var('page_id');
    $page_post = get_post($page_id);

    add_shortcode('citypay-payform-amount-field', 'cp_paylink_payform_amount_field');
    add_shortcode('citypay-payform-checkbox-field', 'cp_paylink_payform_checkbox_field');
    add_shortcode('citypay-payform-text-field', 'cp_paylink_payform_text_field');
    add_shortcode('citypay-payform-field', 'cp_paylink_payform_field');
    add_shortcode('citypay-payform-on-error', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-on-page-load', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-success', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-failure', 'cp_paylink_shortcode_passthrough');
    add_shortcode('citypay-payform-on-redirect-cancel', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform', 'cp_paylink_shortcode_passthrough');
    add_shortcode('citypay-pay-btn', 'cp_paylink_standalone_button');


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
    add_shortcode('citypay-payform-text-field', 'cp_paylink_payform_text_field');
    add_shortcode('citypay-payform-field', 'cp_paylink_payform_field');
    add_shortcode('citypay-payform-on-error', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-on-page-load', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-success', 'cp_paylink_shortcode_passthrough');
    add_shortcode('citypay-payform-on-redirect-failure', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-cancel', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform', 'cp_paylink_shortcode_passthrough');
    add_shortcode('citypay-pay-btn', 'cp_paylink_standalone_button');


    do_shortcode($page_post->post_content);
}

function cp_paylink_make_payment()
{
    $r = cp_paylink_action_pay();

//    echo $r . CP_PAYLINK_NO_ERROR;

    if ($r == CP_PAYLINK_NO_ERROR) {
        return;
    }

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
    remove_shortcode('citypay-pay-btn');

    add_shortcode('citypay-payform-display', 'cp_paylink_payform_display');
    add_shortcode('citypay-payform-amount-field', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-checkbox-field', 'cp_paylink_shortcode_sink');
    add_shortcode('citypay-payform-field', 'cp_paylink_shortcode_sink');

    switch ($r) {
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
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cp_paylink_settings_link');
}

function cp_paylink_template_redirect_dispatcher()
{

    if (isset($_GET[CP_PAYLINK_DISPATCHER])) {
        $action = $_GET[CP_PAYLINK_DISPATCHER];
        switch ($action) {
            case 'pay':
                cp_paylink_make_payment();
                break;

            case 'pay_btn':
                cp_paylink_action_pay_btn();
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

function cp_paylink_settings_link($links)
{
    $settings_link = '<a href="options-general.php?page=cp-paylink-settings">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

function cp_paylink_administration()
{
    add_options_page(
        __('CityPay PayLink for WordPress', 'cp-paylink-wp'),
        __('CityPay PayLink for WordPress', 'cp-paylink-wp'),
        'manage_options',
        'cp-paylink-settings',
        'cp_paylink_settings_page'
    );
}

function cp_paylink_settings_merchant_id()
{
    $option = get_option(CP_PAYLINK_MERCHANT_ID);
    echo "<input type='text' id='"
        . CP_PAYLINK_MERCHANT_ID
        . "' name='"
        . CP_PAYLINK_MERCHANT_ID
        . "' value='${option}' size='20'></input>";
}

function cp_paylink_settings_licence_key()
{
    $option = get_option(CP_PAYLINK_LICENCE_KEY);
    echo "<input type='text' id='"
        . CP_PAYLINK_LICENCE_KEY
        . "' name='"
        . CP_PAYLINK_LICENCE_KEY
        . "' value='${option}' size='20'></input>";
}

function cp_paylink_settings_merchant_email_address()
{
    $option = get_option(CP_PAYLINK_MERCHANT_EMAIL_ADDRESS);
    echo "<input type='text' id='"
        . CP_PAYLINK_MERCHANT_EMAIL_ADDRESS
        . "' name='"
        . CP_PAYLINK_MERCHANT_EMAIL_ADDRESS
        . "' value='${option}' size='60'></input>";
}

function cp_paylink_settings_enable_merchant_email()
{
    $option = get_option(CP_PAYLINK_ENABLE_MERCHANT_EMAIL, false);
    echo "<input type='checkbox' id='"
        . CP_PAYLINK_ENABLE_MERCHANT_EMAIL
        . "' name='"
        . CP_PAYLINK_ENABLE_MERCHANT_EMAIL
        . "'"
        . ($option ? ' checked' : '')
        . '></input>';
}

function cp_paylink_settings_identifier_prefix()
{
    $option = get_option(CP_PAYLINK_IDENTIFIER_PREFIX);
    echo "<input type='text' id='"
        . CP_PAYLINK_IDENTIFIER_PREFIX
        . "' name='"
        . CP_PAYLINK_IDENTIFIER_PREFIX
        . "' value='${option}' size='20' placeholder='(optional)'></input>";
}

function cp_paylink_settings_enable_test_mode()
{
    $option = get_option(CP_PAYLINK_ENABLE_TEST_MODE, true);
    echo "<input type='checkbox' id='"
        . CP_PAYLINK_ENABLE_TEST_MODE
        . "' name='"
        . CP_PAYLINK_ENABLE_TEST_MODE
        . "' "
        . ($option ? ' checked' : '')
        . "></input> Generate transactions using test mode"
        . "<p class='description'>Use this whilst testing your integration. "
        . "You must disable test mode when you are ready to take live "
        . "transactions.</p>";
}

function cp_paylink_settings_enable_debug_mode()
{
    $option = get_option(CP_PAYLINK_ENABLE_DEBUG_MODE, true);
    echo "<input type='checkbox' id='"
        . CP_PAYLINK_ENABLE_DEBUG_MODE
        . "' name='"
        . CP_PAYLINK_ENABLE_DEBUG_MODE
        . "'"
        . ($option ? ' checked' : '')
        . "></input> Enable logging<p class='description'>Log payment events, "
        . "such as postback requests, inside <code>"
        . CityPay_Logger::logFilePathName(__FILE__)
        . "</code>.</p>";
}

function cp_paylink_settings_validate_merchant_id($input)
{
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

function cp_paylink_settings_validate_licence_key($input)
{
    if (!CityPay_Validation::validateLicenceKey($input)) {
        $output = get_option(CP_PAYLINK_LICENCE_KEY);
        add_settings_error(
            'cp-paylink-settings',
            'invalid-licence-key',
            __('Invalid licence key provided.', 'invalid-licence-key'),
            'error'
        );
    } else {
        $output = $input;
    }

    return apply_filters('cp_paylink_settings_validate_identifier_prefix', $output, $output);
}

function cp_paylink_settings_validate_identifier_prefix($input)
{

    if ($input) {
        $output = $input;
    } else {
        $output = '';
    }


    return apply_filters('cp_paylink_settings_validate_identifier_prefix', $output, $output);
}

function cp_paylink_settings_validate_merchant_email_address($input)
{
    if (!CityPay_Validation::validateEmailAddress($input) && !empty($input)) {
        $output = get_option(CP_PAYLINK_MERCHANT_EMAIL_ADDRESS);
        add_settings_error(
            'cp-paylink-settings',
            'invalid-email-address',
            __('Invalid email address provided.', 'invalid-email-address'),
            'error'
        );
    } else {
        $output = $input;
    }

    return apply_filters('cp_paylink_settings_validate_merchant_email_address', $output, $output);
}

function cp_paylink_settings_validate_enable_merchant_email($input)
{
    if (!CityPay_Validation::validateCheckboxValue($input)) {
        $output = get_option(CP_PAYLINK_ENABLE_MERCHANT_EMAIL);
        add_settings_error(
            'cp-paylink-settings',
            'invalid-enable-merchant-email-checkbox-value',
            __('Invalid checkbox value for enable merchant email setting.', 'invalid-checkbox-value'),
            'error'
        );
    } else {
        $output = $input;
    }

    return apply_filters('cp_paylink_settings_validate_enable_merchant_email', $output, $output);
}

function cp_paylink_settings_validate_enable_test_mode($input)
{
    if (!CityPay_Validation::validateCheckboxValue($input)) {
        $output = get_option(CP_PAYLINK_ENABLE_TEST_MODE);
        add_settings_error(
            'cp-paylink-settings',
            'invalid-enable-test-mode-checkbox-value',
            __('Invalid checkbox value for enable test mode setting.', 'invalid-checkbox-value'),
            'error'
        );
    } else {
        $output = $input;
    }

    return apply_filters('cp_paylink_settings_validate_enable_test_mode', $output, $output);
}

function cp_paylink_settings_validate_enable_debug_mode($input)
{
    if (!CityPay_Validation::validateCheckboxValue($input)) {
        $output = get_option(CP_PAYLINK_ENABLE_DEBUG_MODE);
        add_settings_error(
            'cp-paylink-settings',
            'invalid-enable-debug-mode-checkbox-value',
            __('Invalid checkbox value for enable debug mode setting.', 'invalid-checkbox-value'),
            'error'
        );
    } else {
        $output = $input;
    }

    return apply_filters('cp_paylink_settings_validate_enable_debug_mode', $output, $output);
}

function cp_paylink_settings_main_section_text($input)
{
}

function cp_paylink_admin_init()
{

    add_settings_section(
        'main_section',
        'Main Settings',
        'cp_paylink_settings_main_section_text',
        'cp-paylink-settings'
    );

    add_settings_field(
        CP_PAYLINK_MERCHANT_ID,
        'Merchant Identifier',
        'cp_paylink_settings_merchant_id',
        'cp-paylink-settings',
        'main_section',
        array(
            'label_for' => 'Merchant identifier'
        )
    );

    add_settings_field(
        'licence-key',
        'Client Licence Key',
        'cp_paylink_settings_licence_key',
        'cp-paylink-settings',
        'main_section',
        array(
            'label_for' => 'Licence key'
        )
    );

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
        'identifier-prefix',
        'Identifier Prefix',
        'cp_paylink_settings_identifier_prefix',
        'cp-paylink-settings',
        'main_section',
        array(
            'label_for' => 'Identifier prefix'
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
    register_setting('cp-paylink-settings', CP_PAYLINK_IDENTIFIER_PREFIX, 'cp_paylink_settings_validate_identifier_prefix');
    register_setting('cp-paylink-settings', CP_PAYLINK_MERCHANT_EMAIL_ADDRESS, 'cp_paylink_settings_validate_merchant_email_address');
    register_setting('cp-paylink-settings', CP_PAYLINK_ENABLE_MERCHANT_EMAIL, 'cp_paylink_settings_validate_enable_merchant_email');
    register_setting('cp-paylink-settings', CP_PAYLINK_ENABLE_TEST_MODE, 'cp_paylink_settings_validate_enable_test_mode');
    register_setting('cp-paylink-settings', CP_PAYLINK_ENABLE_DEBUG_MODE, 'cp_paylink_settings_validate_enable_debug_mode');
}

function cp_paylink_settings_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    echo '<div class="wrap"><h1>'
        . __('CityPay PayLink for WordPress', 'cp-paylink-wp')
        . '</h1><form method="post" action="options.php">';

    settings_fields('cp-paylink-settings');
    do_settings_sections('cp-paylink-settings');
    submit_button();

    echo '</form></div>';
}

function cp_paylink_exempt_shortcodes_from_texturize($shortcodes)
{
    $shortcodes[] = 'citypay-payform';
    return $shortcodes;
}

function checkBtnSubmit($amount, $identifier, $description){
    //handle form submit
    if (isset($_POST['identifier']) && !isset($_POST['amount'])) {
        if ($_POST['identifier'] === $identifier) {
            cp_paylink_create_token($amount, $identifier, $description);
        }
    }
}

function checkBtnTransResponse($identifier){
    if (isset($_GET['payment-result']) && isset($_POST['identifier'])) {
        if ($_GET['payment-result'] === 'success' && substr($_POST['identifier'], 0,-13) === $identifier) {
            ?>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>
            <script>
                Swal.fire({
                    title: 'Payment Successful!',
                    type: 'success',
                    confirmButtonText: 'Ok'
                })
            </script>
            <?php

        } else if ($_GET['payment-result'] === 'failed' && substr($_POST['identifier'], 0,-13) === $identifier) {
            ?>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>
            <script>
                Swal.fire({
                    title: 'Payment Failed!',
                    type: 'error',
                    confirmButtonText: 'Ok'
                })
            </script>
            <?php
        }
    }
}

function cp_paylink_standalone_button($attrs)
{
    $a = shortcode_atts(
        array(
            'label' => 'Pay with CityPay',
            'amount' => 0,
            'identifier' => 'Identifier',
            'description' => 'Product Description',
        ),
        $attrs
    );
    $amount = $a['amount'];
    $identifier = $a['identifier'];
    $description = $a['description'];

    //handle form submit
    checkBtnSubmit($amount, $identifier, $description);

    checkBtnTransResponse($identifier);
//    if (isset($_POST['identifier']) && !isset($_POST['amount'])) {
//        if ($_POST['identifier'] === $a['identifier']) {
//            cp_paylink_create_token($a['amount'], $a['identifier'], $a['description']);
//        }
//    }

    //handle transaction response
//    if (isset($_GET['payment-result']) && isset($_POST['identifier'])) {
//        if ($_GET['payment-result'] === 'success' && substr($_POST['identifier'], 0,-13) === $a['identifier']) {
//            ?>
<!--            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>-->
<!--            <script>-->
<!--                Swal.fire({-->
<!--                    title: 'Payment Successful!',-->
<!--                    type: 'success',-->
<!--                    confirmButtonText: 'Ok'-->
<!--                })-->
<!--            </script>-->
<!--            --><?php
//
//        } else if ($_GET['payment-result'] === 'failed' && substr($_POST['identifier'], 0,-13) === $a['identifier']) {
//            ?>
<!--            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>-->
<!--            <script>-->
<!--                Swal.fire({-->
<!--                    title: 'Payment Failed!',-->
<!--                    type: 'error',-->
<!--                    confirmButtonText: 'Ok'-->
<!--                })-->
<!--            </script>-->
<!--            --><?php
//        }
//    }

    //form displayed from shortcode
    $sc_output= '<form action="" method="post">'
        . '<input type="hidden" name="identifier" value= ' . $identifier . ' />'
        . '<button type="submit" class="uk-button uk-button-primary uk-button-large">'
        . $attrs['label']
        . '</button>'
        . '</form>';

    return $sc_output;

}

function cp_paylink_create_token($amount, $identifier, $description)
{
    require_once('includes/class-citypay-logger.php');
    require_once('includes/class-citypay-paylink.php');


    $merchant_id = get_option(CP_PAYLINK_MERCHANT_ID);
    $licence_key = get_option(CP_PAYLINK_LICENCE_KEY);
    $identifier_prefix = get_option(CP_PAYLINK_IDENTIFIER_PREFIX);
    $test_mode = get_option(CP_PAYLINK_ENABLE_TEST_MODE);


    $page_id = get_query_var('page_id');
    if (get_option('permalink_structure')) {
        $current_url = get_permalink($page_id);
    } else {
        $current_url = add_query_arg('page_id', $page_id, get_home_url());
    }

    $postback_url = null;
    $success_url = $current_url . '?payment-result=success';
    $failure_url = $current_url . '?payment-result=failed';

    $logger = new CityPay_Logger(__FILE__);
    $paylink = new CityPay_PayLink_WP($logger);


    $paylink->setRequestCart(
        $merchant_id,
        $licence_key,
        $identifier_prefix,
        uniqid($identifier),
        $amount,
        ''
    );

    $paylink->setRequestAddress(
        '',
        '',
        '', '', '', '', '', '',
        '',
        ''
    );

    $paylink->setRequestClient(
        'Wordpress',
        get_bloginfo('version', 'raw'),
        'PayLink-PayForm',
        CP_PAYLINK_VERSION
    );

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

//    $paylink->setCustomParameter("Shipping Address", '', array('placeholder' => 'Address', 'fieldType' =>'text'));
//    $paylink->setCustomParameter("Shipping Postcode", '', array('placeholder' => 'Postcode', 'fieldType' =>'text'));
//    $paylink->setCustomParameter("Shipping Country", '', array('placeholder' => 'Country', 'fieldType' =>'text'));

    if ($description) {
        $paylink->setCustomParameter(
            "Description",
            $description,
            array('fieldType' => 'text',
                'label' => 'Description',
                'locked' => true));
    }

    try {
        $url = $paylink->getPaylinkURL();
        wp_redirect($url);
        exit;
    } catch (Exception $e) {
//        echo $e;
        exit;
    }
}

add_action('init', 'cp_paylink_init');
add_action('admin_init', 'cp_paylink_admin_init');
add_action('wp_loaded', 'cp_paylink_wp_loaded');
add_filter('no_texturize_shortcodes', 'cp_paylink_exempt_shortcodes_from_texturize');

register_activation_hook(__FILE__, 'cp_paylink_install');