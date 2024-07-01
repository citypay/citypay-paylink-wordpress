<?php

/**
 * Plugin Name: CityPay PayLink PayForm WP
 * Plugin URI: http://citypay.com/paylink
 * Description: Include an arbitrary payment processing form.
 * Version: 1.3.0
 * Author: CityPay Limited
 * Author URI: http://citypay.com
 *  License: GPL v2 or later
 *  License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') or die;

require_once('includes/class-citypay-library.php');
require_once('includes/class-citypay-logger.php');
require_once('includes/class-citypay-stack.php');
require_once('includes/class-citypay-filter.php');
require_once('includes/class-citypay-validation.php');
require_once('includes/class-citypay-paylink.php');

define('CPPP_VERSION', '1.3.0');
define('CPPP_DISPATCHER', 'cp_paylink');
define('CPPP_MERCHANT_ID', 'CPPP_merchant_id');
define('CPPP_LICENCE_KEY', 'CPPP_licence_key');
define('CPPP_IDENTIFIER_PREFIX', 'CPPP_identifier_prefix');
define('CPPP_POSTBACK_URL', 'CPPP_postback_url');
define('CPPP_MERCHANT_EMAIL_ADDRESS', 'CPPP_merchant_email_address');
define('CPPP_ENABLE_MERCHANT_EMAIL', 'CPPP_enable_merchant_email');
define('CPPP_ENABLE_TEST_MODE', 'CPPP_enable_test_mode');
define('CPPP_ENABLE_DEBUG_MODE', 'CPPP_enable_debug_mode');

define('CPPP_OPT_VERSION', 'CPPP_version');

define('CPPP_NAME_REGEX', '/^\s*\b(?:(Mr|Mrs|Miss|Dr)\b\.?+)?+\s*\b([\w\-]+)\b\s+\b(\b\w\b)?\s*([\w\-\s]+?)\s*$/i');
define('CPPP_IDENTIFIER_REGEX', '/^[^\s]{5,}$/');

define('CPPP_NO_ERROR', 0x00);

define('CPPP_AMOUNT_PARSE_ERROR', -1);
define('CPPP_AMOUNT_PARSE_ERROR_EMPTY_STRING', -2);
define('CPPP_AMOUNT_PARSE_ERROR_INVALID_CHARACTER', -3);
define('CPPP_AMOUNT_PARSE_ERROR_INVALID_PRECISION', -4);
define('CPPP_AMOUNT_PARSE_ERROR_BELOW_MINIMUM_VALUE', -5);
define('CPPP_AMOUNT_PARSE_ERROR_ABOVE_MAXIMUM_VALUE', -6);

define('CPPP_DEFAULT_MINIMUM_AMOUNT', 0);

define('CPPP_TEXT_FIELD_PARSE_ERROR_EMPTY_STRING', -100);

define('CPPP_EMAIL_ADDRESS_FIELD_PARSE_ERROR_EMPTY_STRING', -200);
define('CPPP_EMAIL_ADDRESS_FIELD_PARSE_ERROR_NOT_VALID', -201);

define('CPPP_IDENTIFIER_FIELD_PARSE_ERROR_EMPTY_STRING', -300);
define('CPPP_IDENTIFIER_FIELD_PARSE_ERROR_NOT_VALID', -301);

define('CPPP_NAME_FIELD_PARSE_ERROR_EMPTY_STRING', -400);
define('CPPP_NAME_FIELD_PARSE_ERROR_NOT_VALID', -401);

define('CPPP_TERMS_AND_CONDITIONS_NOT_ACCEPTED', -500);

define('CPPP_DEFAULT_ERROR_MESSAGE', 'CPPP_default_error_messages');


$CPPP_default_error_messages = array(
    'CPPP_TEXT_FIELD_PARSE_ERROR_EMPTY_STRING'
    => __('This field cannot be empty'),
    'CPPP_IDENTIFIER_FIELD_PARSE_ERROR_EMPTY_STRING'
    => __('This field cannot be empty.'),
    'CPPP_IDENTIFIER_FIELD_PARSE_ERROR_NOT_VALID'
    => __('This field does not contain an acceptable value.'),
    'CPPP_NAME_FIELD_PARSE_ERROR_EMPTY_STRING'
    => __('This field cannot be empty.'),
    'CPPP_NAME_FIELD_PARSE_ERROR_NOT_VALID'
    => __('This field does not contain an acceptable value. Please enter a person\'s name of the form <b>&lt;firstname&gt; &lt;lastname&gt;</b>.'),
    'CPPP_EMAIL_ADDRESS_FIELD_PARSE_ERROR_EMPTY_STRING'
    => __('This field cannot be empty.'),
    'CPPP_EMAIL_ADDRESS_FIELD_PARSE_ERROR_NOT_VALID'
    => __('This field does not contain an acceptable value. Please enter a valid email address of the form <b>&lt;name&gt;@&lt;domain-name&gt;</b>.'),
    'CPPP_AMOUNT_PARSE_ERROR_EMPTY_STRING'
    => __('This field cannot be empty.'),
    'CPPP_AMOUNT_PARSE_ERROR_INVALID_CHARACTER'
    => __('This field contains an invalid character; only numeric digits and a decimal point are acceptable.'),
    'CPPP_AMOUNT_PARSE_ERROR_INVALID_PRECISION'
    => __('This field contains a value with too many digits appearing after the decimal point, and is therefore unacceptable.'),
    'CPPP_AMOUNT_PARSE_ERROR_BELOW_MINIMUM_VALUE'
    => __('This field contains an amount that is below the lowest acceptable value for transactions processed using this service.'),
    'CPPP_AMOUNT_PARSE_ERROR_ABOVE_MAXIMUM_VALUE'
    => __('This field contains an amount that is more than the maxmimum acceptable value for transactions processed using this service.'),
    'CPPP_TERMS_AND_CONDITIONS_NOT_ACCEPTED'
    => __('You must accept the terms and conditions to use this service.')
);

global $CPPP_redirect_success_url;
global $CPPP_redirect_failure_url;


define('CPPP_PROCESSING_ERROR_DATA_INPUT_ERROR', 0x05);
define('CPPP_PROCESSING_ERROR_PAYLINK_ERROR', 0x06);

function CPPP_install()
{
    $current_version = get_option(CPPP_OPT_VERSION);
    switch ($current_version) {
        case "1.1.1":
            break;

        default:
            $test_mode = get_option('CPPP_test_mode');
            if (!is_null($test_mode)) {
                add_option(CPPP_ENABLE_TEST_MODE, $test_mode);
                delete_option('CPPP_test_mode');
            }

            $debug_mode = get_option('CPPP_debug_mode');
            if (!is_null($debug_mode)) {
                add_option(CPPP_ENABLE_DEBUG_MODE, $debug_mode);
                delete_option('CPPP_debug_mode');
            }
    }

    update_option(CPPP_OPT_VERSION, CPPP_VERSION);
}

function CPPP_config_stack()
{
    static $CPPP_config_stack = NULL;
    if (is_null($CPPP_config_stack)) {
        $CPPP_config_stack = new CPPP_config_stack();
    }
    return $CPPP_config_stack;
}

function CPPP_send_cors_headers($headers)
{
    error_log("send_cors_headers: " . $headers);
    $headers['Access-Control-Allow-Origin'] = "https://secure.citypay.com";
    return $headers;
}

function CPPP_add_query_vars_filter($vars)
{
    $vars[] = "cp_paylink";
    return $vars;
}

function CPPP_payform_field_config_sort($val1, $val2)
{
    if ($val1->order > $val2->order) {
        return 1;
    } elseif ($val1->order < $val2->order) {
        return -1;
    } else {
        return 0;
    }
}

class CPPP_field
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

    public function CPPP_configure_error_message($attrs, $content = null)
    {
        $a = shortcode_atts(
            array(
                'handle' => ''
            ),
            $attrs
        );

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
        if ($this->error != CPPP_NO_ERROR) {
            if (!is_null($this->error_message)) {
                if (array_key_exists($this->error, $this->error_message)) {
                    return $this->error_message[$this->error];
                } else {
                    return $GLOBALS[CPPP_DEFAULT_ERROR_MESSAGE][$this->error];
                }
            } else {
                return $GLOBALS[CPPP_DEFAULT_ERROR_MESSAGE][$this->error];
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

class CPPP_text_field extends CPPP_field
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

class CPPP_checkbox_field extends CPPP_field
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

class CPPP_amount_field extends CPPP_text_field
{
    private $amount, $decimal_places, $minimum, $maximum;

    private static function _parse_amount($in, &$out, $decimal_places = null)
    {

        $_in = trim($in);
        $_out = 0;
        $index = 0;
        $i_max = strlen($_in);

        if ($i_max <= 0x00) {
            return CPPP_AMOUNT_PARSE_ERROR_EMPTY_STRING;
        }

        while ($index < $i_max) {
            $c = ord($_in[$index]);
            if ($c >= 48 && $c <= 57) {
                $_out = ($_out * 10) + ($c - 48);
                $index++;
            } else if ($c === ord('.')) {
                break;
            } else {
                return CPPP_AMOUNT_PARSE_ERROR_INVALID_CHARACTER;
            }
        }

        $_out *= 100;

        if ($index >= $i_max) {
            $out = $_out;
            return CPPP_NO_ERROR;
        }

        if ($c == ord('.')) {
            $index++;
            $pence = 0;

            if (!is_null($decimal_places) && $i_max > $index + $decimal_places) {
                return CPPP_AMOUNT_PARSE_ERROR_INVALID_PRECISION;
            }

            $j = $decimal_places;
            while ($index < $i_max) {
                $c = ord($_in[$index]);
                if ($c >= 48 && $c <= 57) {
                    $pence = ($pence * 10) + ($c - 48);
                    $index++;
                    $j--;
                } else {
                    return CPPP_AMOUNT_PARSE_ERROR_INVALID_CHARACTER;
                }
            }

            if ($j > 0x00) {
                $pence = $pence * pow(10, $j);
            }

            $_out += $pence;
        }

        $out = $_out;
//        echo "<br/>PPPLLLL RETURNING NO ERROR:" . $in;
        return CPPP_NO_ERROR;
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
        if ($result == CPPP_NO_ERROR) {
            if (!empty($this->minimum) && $this->amount < $this->minimum) {
                $this->error = CPPP_AMOUNT_PARSE_ERROR_BELOW_MINIMUM_VALUE;
                return false;
            } else if (!empty($this->maximum) && $this->amount > $this->maximum) {
                $this->error = CPPP_AMOUNT_PARSE_ERROR_ABOVE_MAXIMUM_VALUE;
                return false;
            } else {
                $this->error = CPPP_NO_ERROR;
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

class CPPP_customer_name_field extends CPPP_text_field
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
            $this->error = CPPP_NAME_FIELD_PARSE_ERROR_EMPTY_STRING;
            return false;
        } else {
            $matches = array();
            if (preg_match(CPPP_NAME_REGEX, parent::getValue(), $matches)) {
                $this->salutation = $matches[1];
                $this->first_name = $matches[2];
                $this->middle_initial = $matches[3];
                $this->last_name = $matches[4];
                return true;
            } else {
                $this->error = CPPP_NAME_FIELD_PARSE_ERROR_NOT_VALID;
                return false;
            }
        }
    }
}

class CPPP_email_field extends CPPP_text_field
{
    public function parse($value_in)
    {
        if (!parent::parse($value_in)) {
            return false;
        }
        if (strlen(trim(parent::getValue())) == 0x00) {
            $this->error = CPPP_EMAIL_ADDRESS_FIELD_PARSE_ERROR_EMPTY_STRING;
            return false;
        } else if (!CPPP_Validation::validateEmailAddress(trim(parent::getValue()))) {
            $this->error = CPPP_EMAIL_ADDRESS_FIELD_PARSE_ERROR_NOT_VALID;
            return false;
        }
        return true;
    }
}

class CPPP_identifier_field extends CPPP_text_field
{
    public function parse($value_in)
    {
        if (!parent::parse($value_in)) {
            return false;
        }
        if (strlen($this->getValue()) == 0x00) {
            $this->error = CPPP_IDENTIFIER_FIELD_PARSE_ERROR_EMPTY_STRING;
            return false;
        } else {

            if (preg_match_all(CPPP_IDENTIFIER_REGEX, get_option(CPPP_IDENTIFIER_PREFIX) . parent::getValue()) &&
                mb_strlen(get_option(CPPP_IDENTIFIER_PREFIX) . parent::getValue()) > 4 &&
                mb_strlen(get_option(CPPP_IDENTIFIER_PREFIX) . parent::getValue()) < 51) {
                return true;
            } else {
                $this->error = CPPP_IDENTIFIER_FIELD_PARSE_ERROR_NOT_VALID;
                return false;
            }
        }
    }
}

class CPPP_accept_terms_and_conditions_checkbox_field extends CPPP_checkbox_field
{
    public function parse($value_in)
    {
        if (!parent::parse($value_in)) {
            return false;
        }
        if (!parent::isChecked()) {
            $this->error = CPPP_TERMS_AND_CONDITIONS_NOT_ACCEPTED;
            return false;
        }
        return true;
    }
}

function CPPP_payform_amount_field($attrs, $content = null)
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

    $field = new CPPP_amount_field(
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
        add_shortcode('error-message', array($field, 'CPPP_configure_error_message'));
        $_content = do_shortcode($content);
        remove_shortcode('error-message');
        $field->setContent($_content);
    }

    CPPP_config_stack()->set($field->name, $field);

    return '';
}

function CPPP_payform_field($attrs, $content = null)
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
            $field = new CPPP_customer_name_field(
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
            $field = new CPPP_email_field(
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
            $field = new CPPP_identifier_field(
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
            $field = new CPPP_text_field(
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
        add_shortcode('error-message', array($field, 'CPPP_configure_error_message'));
        $_content = do_shortcode($content);
        remove_shortcode('error-message');
        $field->setContent($_content);
    }

    CPPP_config_stack()->set($field->name, $field);

    return '';
}

function CPPP_payform_checkbox_field($attrs, $content = null)
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
            $field = new CPPP_accept_terms_and_conditions_checkbox_field(
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
            $field = new CPPP_checkbox_field(
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
        add_shortcode('error-message', array($field, 'CPPP_configure_error_message'));
        $_content = do_shortcode($content);
        remove_shortcode('error-message');
        $field->setContent($_content);
    }

    CPPP_config_stack()->set($field->name, $field);
}

function CPPP_shortcode_sink($attrs, $content = null)
{
    if (!is_null($content)) {
        do_shortcode($content);
    }
    return '';
}

class CPPP_tag
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

class CPPP_attr
{
    public $name;
    public $value;

    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }
}

class CPPP_text
{
    public $text;

    public function __construct($text)
    {
        $this->text = $text;
    }
}

function CPPP_shortcode_passthrough($attrs, $content = null)
{
    $a = shortcode_atts(
        array(),
        $attrs
    );

    if (!is_null($content)) {
        $s = CPPP_Filter::CPPP_trim_p_and_br_tags($content);
        return do_shortcode($s);
    } else {
        return '';
    }
}

function CPPP_payform_display_text_field_default($field)
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

function CPPP_payform_display_checkbox_field_default($field)
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

function CPPP_payform_display_default($attrs, $content = null)
{
    // if a configuration has been specified
    $current_url = get_permalink();
    $s = trim($content)
        . '<form role="form" id="billPaymentForm" class="form-horizontal uk-form" method="POST" action="'
        . add_query_arg('cp_paylink', 'pay', $current_url)
        . '"><input type="hidden" name="CPPP_pay" value="Y">';

    $config = CPPP_config_stack()->peek();

    //
    // Sort the fieldlist appearing in the PayForm configuration according to
    // the order attribute provided.
    //
    usort($config, 'CPPP_payform_field_config_sort');

    foreach ($config as $field) {
        if ($field instanceof CPPP_text_field) {
            if (function_exists('CPPP_payform_display_text_field_custom')) {
                $s .= CPPP_payform_display_text_field_custom($field);
            } else {
                $s .= CPPP_payform_display_text_field_default($field);
            }
        } elseif ($field instanceof CPPP_checkbox_field) {
            if (function_exists('CPPP_payform_display_checkbox_field_custom')) {
                $s .= CPPP_payform_display_checkbox_field_custom($field);
            } else {
                $s .= CPPP_payform_display_checkbox_field_default($field);
            }
        }
    }

    $s .= '<button type="submit" class="uk-button uk-button-primary uk-button-large">'
        . $attrs['submit']
        . '</button></form>';

    return $s;
}

function CPPP_payform_display($attrs, $content = null)
{
    $a = shortcode_atts(
        array('submit' => __('Pay', 'CPPP_pay')),
        $attrs
    );

    if (is_single() || is_page()) {
        if (function_exists('CPPP_payform_display_custom')) {
            return CPPP_payform_display_custom($a, $content);
        } else {
            return CPPP_payform_display_default($a, $content);
        }
    } else {
        return '';
    }
}

function CPPP_payform_on_page_load($attrs, $content = null)
{
    //
    //  If shortcode contains nested shortcodes, process these before
    //  processing the immediate form.
    //
    if (!is_null($content)) {
        $s = CPPP_Filter::CPPP_trim_p_and_br_tags($content);
        return do_shortcode($s);
    } else {
        return '';
    }
}

function CPPP_action_pay()
{
    require_once('includes/class-citypay-logger.php');
    require_once('includes/class-citypay-paylink.php');

    $page_id = get_query_var('page_id');
    $page_post = get_post($page_id);

    add_shortcode('citypay-payform-amount-field', 'CPPP_payform_amount_field');
    add_shortcode('citypay-payform-checkbox-field', 'CPPP_payform_checkbox_field');
    add_shortcode('citypay-payform-field', 'CPPP_payform_field');
    add_shortcode('citypay-payform-on-error', 'CPPP_shortcode_sink');
    add_shortcode('citypay-payform-on-page-load', 'CPPP_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-success', 'redirect_url_success');
    add_shortcode('citypay-payform-on-redirect-failure', 'redirect_url_failure');
    add_shortcode('citypay-payform-on-redirect-cancel', 'redirect_url_cancel');
    add_shortcode('citypay-payform', 'CPPP_shortcode_sink');
    add_shortcode('citypay-payform', 'CPPP_shortcode_passthrough');
    add_shortcode('citypay-pay-btn', 'CPPP_standalone_button');

    do_shortcode($page_post->post_content);

    $merchant_id = get_option(CPPP_MERCHANT_ID);
    $licence_key = get_option(CPPP_LICENCE_KEY);
    $identifier_prefix = get_option(CPPP_IDENTIFIER_PREFIX);
    $test_mode = get_option(CPPP_ENABLE_TEST_MODE);

    $fields = &CPPP_config_stack()->getFields();

    foreach ($fields as $key => $field) {
        $field->parse(filter_input(INPUT_POST, $key, FILTER_DEFAULT, FILTER_REQUIRE_SCALAR));
    }

    $f_identifier = $fields['identifier'];
    $f_email = $fields['email'];
    $f_name = $fields['customer-name'];
    $f_amount = $fields['amount'];
    $f_tnc = $fields['accept-terms-and-conditions'];
    $f_tnc_invalid = (is_null($f_tnc) || !$f_tnc->isChecked());

    $fN_valid = true;
    foreach ($fields as $key => $field) {
        if ($field instanceof CPPP_text_field) {
            $fN_valid = ($fN_valid && ($field->isOptional() || (!($field->isOptional() || empty($field->getValue())))));
        }
    }

    $fields_have_error = false;
    foreach ($fields as $key => $field) {
        if ($field instanceof CPPP_text_field) {
            if ($fields_have_error == false)
                $fields_have_error = ($field->getErrorMessage() != '');
        }
    }

    if ($fields_have_error || $f_tnc_invalid) {
        return CPPP_PROCESSING_ERROR_DATA_INPUT_ERROR;
    }

    if (get_option('permalink_structure')) {
        $current_url = get_permalink($page_id);
    } else {
        $current_url = add_query_arg('page_id', $page_id, get_home_url());
    }

    $cp_pl_postback_url = get_option(CPPP_POSTBACK_URL);

    if ($cp_pl_postback_url) {
        $postback_url = $cp_pl_postback_url;
    } else {
        $postback_url = add_query_arg(CPPP_DISPATCHER, 'postback', $current_url);
    }

    $success_url = get_option('$CPPP_redirect_success_url');

    $failure_url = get_option('$CPPP_redirect_failure_url');


    $logger = new CPPP_Logger(__FILE__);
    $paylink = new CPPP_WP($logger);

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

    $paylink->setRequestClient(
        'Wordpress',
        get_bloginfo('version', 'raw'),
        'PayLink-PayForm',
        CPPP_VERSION
    );

    $paylink->setRequestConfig(
        $test_mode,
        $postback_url,
        $success_url,
        $failure_url
    );

    $merchant_email = get_option(CPPP_MERCHANT_EMAIL_ADDRESS);
    if (!empty($merchant_email)) {
        $paylink->setRequestMerchant($merchant_email);
        $enable_merchant_email = get_option(CPPP_ENABLE_MERCHANT_EMAIL, false);
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
        return CPPP_PROCESSING_ERROR_PAYLINK_ERROR;
    }
}

function redirect_url_success($atts)
{
    $url = shortcode_atts(
        array(
            'url' => add_query_arg(CPPP_DISPATCHER, 'success', get_permalink(get_query_var('page_id'))),
        ),
        $atts
    );

    update_option('$CPPP_redirect_success_url', $url['url']);

    return '';
}

function redirect_url_failure($atts)
{
    $url = shortcode_atts(
        array(
            'url' => add_query_arg(CPPP_DISPATCHER, 'failure', get_permalink(get_query_var('page_id'))),
        ),
        $atts
    );

    update_option('$CPPP_redirect_failure_url', $url['url']);

    return '';
}

function CPPP_init()
{
    ob_clean();
    ob_start();
    if (isset($_GET[CPPP_DISPATCHER])) {
        add_filter('query_vars', 'CPPP_add_query_vars_filter');
        add_action('template_redirect', 'CPPP_template_redirect_dispatcher');
    } else {
        add_shortcode('citypay-payform-display', 'CPPP_payform_display');
        add_shortcode('citypay-payform-amount-field', 'CPPP_payform_amount_field');
        add_shortcode('citypay-payform-checkbox-field', 'CPPP_payform_checkbox_field');
        add_shortcode('citypay-payform-text-field', 'CPPP_payform_text_field');
        add_shortcode('citypay-payform-field', 'CPPP_payform_field');
        add_shortcode('citypay-payform-on-error', 'CPPP_shortcode_sink');
        add_shortcode('citypay-payform-on-page-load', 'CPPP_payform_on_page_load');
        add_shortcode('citypay-payform-on-redirect-success', 'CPPP_shortcode_sink');
        add_shortcode('citypay-payform-on-redirect-failure', 'CPPP_shortcode_sink');
        add_shortcode('citypay-payform-on-redirect-cancel', 'CPPP_shortcode_sink');
        add_shortcode('citypay-payform', 'CPPP_shortcode_passthrough');
        add_shortcode('citypay-pay-btn', 'CPPP_standalone_button');
        add_action('admin_menu', 'CPPP_administration');
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'CPPP_settings_link');
    }
}

function CPPP_wp_loaded()
{
    return;
}

function CPPP_template_redirect_on_redirect_failure()
{
    $page_id = get_query_var('page_id');
    $page_post = get_post($page_id);

    add_shortcode('citypay-payform-amount-field', 'CPPP_payform_amount_field');
    add_shortcode('citypay-payform-checkbox-field', 'CPPP_payform_checkbox_field');
    add_shortcode('citypay-payform-text-field', 'CPPP_payform_text_field');
    add_shortcode('citypay-payform-field', 'CPPP_payform_field');
    add_shortcode('citypay-payform-on-error', 'CPPP_shortcode_sink');
    add_shortcode('citypay-payform-on-page-load', 'CPPP_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-success', 'CPPP_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-failure', 'CPPP_shortcode_passthrough');
    add_shortcode('citypay-payform-on-redirect-cancel', 'CPPP_shortcode_sink');
    add_shortcode('citypay-payform', 'CPPP_shortcode_passthrough');
    add_shortcode('citypay-pay-btn', 'CPPP_standalone_button');

    do_shortcode($page_post->post_content);
}

function CPPP_template_redirect_on_postback()
{
    $logger = new CPPP_Logger(__FILE__);
    $paylink = new CPPP_WP($logger);
    $post_back_data = $paylink->getPostbackData();
    $paylink->debugLog("Postback data " . print_r($post_back_data, true));
    ob_clean();
    header('HTTP/1.1 200 OK');
    exit;
}

function CPPP_template_redirect_on_redirect_success()
{
    $page_id = get_query_var('page_id');
    $page_post = get_post($page_id);

    add_shortcode('citypay-payform-amount-field', 'CPPP_payform_amount_field');
    add_shortcode('citypay-payform-checkbox-field', 'CPPP_payform_checkbox_field');
    add_shortcode('citypay-payform-text-field', 'CPPP_payform_text_field');
    add_shortcode('citypay-payform-field', 'CPPP_payform_field');
    add_shortcode('citypay-payform-on-error', 'CPPP_shortcode_sink');
    add_shortcode('citypay-payform-on-page-load', 'CPPP_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-success', 'CPPP_shortcode_passthrough');
    add_shortcode('citypay-payform-on-redirect-failure', 'CPPP_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-cancel', 'CPPP_shortcode_sink');
    add_shortcode('citypay-payform-shortcode-passthrough', 'CPPP_shortcode_passthrough');
    add_shortcode('citypay-payform-pay-btn', 'CPPP_standalone_button');

    do_shortcode($page_post->post_content);
}

function CPPP_action_pay_btn()
{
    if (isset($_POST['cp_nonce_field']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cp_nonce_field'])))) {
        if (isset($_POST['identifier']) && isset($_POST['amount']) && isset($_POST['description'])) {
            $identifier = sanitize_text_field($_POST['identifier']);
            $amount = sanitize_text_field($_POST['amount']);
            $description = sanitize_text_field($_POST['description']);
            CPPP_create_token($amount, $identifier, $description);
        }
    } else {
        wp_die('Nonce verification failed', 'Nonce Error', array('response' => 403));
    }
}

function CPPP_make_payment()
{
    $r = CPPP_action_pay();

    if ($r == CPPP_NO_ERROR) {
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

    add_shortcode('citypay-payform-display', 'CPPP_payform_display');
    add_shortcode('citypay-payform-amount-field', 'CPPP_shortcode_sink');
    add_shortcode('citypay-payform-checkbox-field', 'CPPP_shortcode_sink');
    add_shortcode('citypay-payform-field', 'CPPP_shortcode_sink');

    switch ($r) {
        case CPPP_PROCESSING_ERROR_DATA_INPUT_ERROR:
            add_shortcode('citypay-payform-on-error', 'CPPP_shortcode_sink');
            add_shortcode('citypay-payform-on-page-load', 'CPPP_shortcode_passthrough');
            break;

        case CPPP_PROCESSING_ERROR_PAYLINK_ERROR:
            add_shortcode('citypay-payform-on-error', 'CPPP_shortcode_passthrough');
            add_shortcode('citypay-payform-on-page-load', 'CPPP_shortcode_sink');
            break;
    }

    add_shortcode('citypay-payform-on-redirect-success', 'CPPP_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-failure', 'CPPP_shortcode_sink');
    add_shortcode('citypay-payform-on-redirect-cancel', 'CPPP_shortcode_sink');
    add_shortcode('citypay-payform', 'CPPP_shortcode_passthrough');
    add_action('admin_menu', 'CPPP_administration');
    //add_filter('wp_headers', array('cp_paylinkjs_send_cors_headers'));
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'CPPP_settings_link');
}

function CPPP_template_redirect_dispatcher()
{
    if (isset($_GET[CPPP_DISPATCHER])) {
        $action = sanitize_text_field($_GET[CPPP_DISPATCHER]);
        switch ($action) {
            case 'pay':
                CPPP_make_payment();
                break;

            case 'pay_btn':
                CPPP_action_pay_btn();
                break;

            case 'postback':
                CPPP_template_redirect_on_postback();
                break;

            case 'success':
                CPPP_template_redirect_on_redirect_success();
                break;

            case 'failure':
                CPPP_template_redirect_on_redirect_failure();
                break;

            default:
                break;
        }
    }
}

function CPPP_settings_link($links)
{
    $settings_link = '<a href="options-general.php?page=cp-paylink-settings">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

function CPPP_administration()
{
    add_options_page(
        __('CityPay PayLink for WordPress', 'cp-paylink-wp'),
        __('CityPay PayLink for WordPress', 'cp-paylink-wp'),
        'manage_options',
        'cp-paylink-settings',
        'CPPP_settings_page'
    );
}

function CPPP_settings_merchant_id()
{
    $option = get_option(CPPP_MERCHANT_ID);
    echo "<input type='text' id='"
        . esc_attr(CPPP_MERCHANT_ID)
        . "' name='"
        . esc_attr(CPPP_MERCHANT_ID)
        . "' value='${option}' size='20'></input>";
}

function CPPP_settings_licence_key()
{
    $option = esc_attr(get_option(CPPP_LICENCE_KEY));
    echo "<input type='text' id='"
        . esc_attr(CPPP_LICENCE_KEY)
        . "' name='"
        . esc_attr(CPPP_LICENCE_KEY)
        . "' value='${option}' size='20'></input>";
}

function CPPP_settings_merchant_email_address()
{
    $option = esc_attr(get_option(CPPP_MERCHANT_EMAIL_ADDRESS));
    echo "<input type='text' id='"
        . esc_attr(CPPP_MERCHANT_EMAIL_ADDRESS)
        . "' name='"
        . esc_attr(CPPP_MERCHANT_EMAIL_ADDRESS)
        . "' value='${option}' size='60'>";
}

function CPPP_settings_enable_merchant_email()
{
    $option = esc_attr(get_option(CPPP_ENABLE_MERCHANT_EMAIL, false));
    echo "<input type='checkbox' id='"
        . esc_attr(CPPP_ENABLE_MERCHANT_EMAIL)
        . "' name='"
        . esc_attr(CPPP_ENABLE_MERCHANT_EMAIL)
        . "'"
        . ($option ? ' checked' : '')
        . '></input>';
}

function CPPP_settings_identifier_prefix()
{
    $option = esc_attr(get_option(CPPP_IDENTIFIER_PREFIX));
    echo "<input type='text' id='"
        . esc_attr(CPPP_IDENTIFIER_PREFIX)
        . "' name='"
        . esc_attr(CPPP_IDENTIFIER_PREFIX)
        . "' value='${option}' size='20' placeholder='(optional)'></input>";
}

function CPPP_settings_postback_url()
{
    $option = esc_attr(get_option(CPPP_POSTBACK_URL));
    echo "<input type='text' id='"
        . esc_attr(CPPP_POSTBACK_URL)
        . "' name='"
        . esc_attr(CPPP_POSTBACK_URL)
        . "' value='${option}' size='60' placeholder='(optional)'></input>";
}

function CPPP_settings_enable_test_mode()
{
    $option = esc_attr(get_option(CPPP_ENABLE_TEST_MODE, true));
    echo "<input type='checkbox' id='"
        . esc_attr(CPPP_ENABLE_TEST_MODE)
        . "' name='"
        . esc_attr(CPPP_ENABLE_TEST_MODE)
        . "' "
        . ($option ? ' checked' : '')
        . "></input> Generate transactions using test mode"
        . "<p class='description'>Use this whilst testing your integration. "
        . "You must disable test mode when you are ready to take live "
        . "transactions.</p>";
}

function CPPP_settings_enable_debug_mode()
{
    $option = esc_attr(get_option(CPPP_ENABLE_DEBUG_MODE, true));
    echo "<input type='checkbox' id='"
        . esc_attr(CPPP_ENABLE_DEBUG_MODE)
        . "' name='"
        . esc_attr(CPPP_ENABLE_DEBUG_MODE)
        . "'"
        . ($option ? ' checked' : '')
        . "></input> Enable logging<p class='description'>Log payment events, "
        . "such as postback requests, inside <code>"
        . CPPP_Logger::logFilePathName(__FILE__)
        . "</code>.</p>";
}

function CPPP_settings_validate_merchant_id($input)
{
    if (!CPPP_Validation::validateMerchantId($input)) {
        $output = get_option(CPPP_MERCHANT_ID);
        add_settings_error(
            'merchant-id',
            'invalid-merchant-id',
            __('Invalid merchant identifier provided.', 'invalid-merchant-id'),
            'error'
        );
    } else {
        $output = $input;
    }

    return apply_filters('CPPP_settings_validate_merchant_id', $output, $output);
}

function CPPP_settings_validate_licence_key($input)
{
    if (!CPPP_Validation::validateLicenceKey($input)) {
        $output = get_option(CPPP_LICENCE_KEY);
        add_settings_error(
            'cp-paylink-settings',
            'invalid-licence-key',
            __('Invalid licence key provided.', 'invalid-licence-key'),
            'error'
        );
    } else {
        $output = $input;
    }

    return apply_filters('CPPP_settings_validate_identifier_prefix', $output, $output);
}

function CPPP_settings_validate_postback_url($input)
{
    if (!CPPP_Validation::validatePostbackUrl($input)) {
        $output = get_option(CPPP_POSTBACK_URL);
        add_settings_error(
            'cp-paylink-settings',
            'invalid-postback-url',
            __('Invalid Postback URL', 'invalid-postback-url'),
            'error'
        );
    } else {
        $output = $input;
    }

    return apply_filters('CPPP_settings_validate_postback_url', $output, $output);
}

function CPPP_settings_validate_identifier_prefix($input)
{

    if ($input) {
        $output = $input;
    } else {
        $output = '';
    }


    return apply_filters('CPPP_settings_validate_identifier_prefix', $output, $output);
}

function CPPP_settings_validate_merchant_email_address($input)
{
    if (!CPPP_Validation::validateEmailAddress($input) && !empty($input)) {
        $output = get_option(CPPP_MERCHANT_EMAIL_ADDRESS);
        add_settings_error(
            'cp-paylink-settings',
            'invalid-email-address',
            __('Invalid email address provided.', 'invalid-email-address'),
            'error'
        );
    } else {
        $output = $input;
    }

    return apply_filters('CPPP_settings_validate_merchant_email_address', $output, $output);
}

function CPPP_settings_validate_enable_merchant_email($input)
{
    if (!CPPP_Validation::validateCheckboxValue($input)) {
        $output = get_option(CPPP_ENABLE_MERCHANT_EMAIL);
        add_settings_error(
            'cp-paylink-settings',
            'invalid-enable-merchant-email-checkbox-value',
            __('Invalid checkbox value for enable merchant email setting.', 'invalid-checkbox-value'),
            'error'
        );
    } else {
        $output = $input;
    }

    return apply_filters('CPPP_settings_validate_enable_merchant_email', $output, $output);
}

function CPPP_settings_validate_enable_test_mode($input)
{
    if (!CPPP_Validation::validateCheckboxValue($input)) {
        $output = get_option(CPPP_ENABLE_TEST_MODE);
        add_settings_error(
            'cp-paylink-settings',
            'invalid-enable-test-mode-checkbox-value',
            __('Invalid checkbox value for enable test mode setting.', 'invalid-checkbox-value'),
            'error'
        );
    } else {
        $output = $input;
    }

    return apply_filters('CPPP_settings_validate_enable_test_mode', $output, $output);
}

function CPPP_settings_validate_enable_debug_mode($input)
{
    if (!CPPP_Validation::validateCheckboxValue($input)) {
        $output = get_option(CPPP_ENABLE_DEBUG_MODE);
        add_settings_error(
            'cp-paylink-settings',
            'invalid-enable-debug-mode-checkbox-value',
            __('Invalid checkbox value for enable debug mode setting.', 'invalid-checkbox-value'),
            'error'
        );
    } else {
        $output = $input;
    }

    return apply_filters('CPPP_settings_validate_enable_debug_mode', $output, $output);
}

function CPPP_settings_main_section_text($input)
{
}

function CPPP_admin_init()
{

    add_settings_section(
        'CPPP_main_section',
        'Main Settings',
        'CPPP_settings_main_section_text',
        'cp-paylink-settings'
    );

    add_settings_field(
        CPPP_MERCHANT_ID,
        'Merchant Identifier',
        'CPPP_settings_merchant_id',
        'cp-paylink-settings',
        'CPPP_main_section',
        array(
            'label_for' => 'Merchant identifier'
        )
    );

    add_settings_field(
        'CPPP_licence_key',
        'Client Licence Key',
        'CPPP_settings_licence_key',
        'cp-paylink-settings',
        'CPPP_main_section',
        array(
            'label_for' => 'Licence key'
        )
    );

    add_settings_field(
        'CPPP_merchant_email_address',
        'Email address',
        'CPPP_settings_merchant_email_address',
        'cp-paylink-settings',
        'CPPP_main_section',
        array(
            'label_for' => 'Email address'
        )
    );

    add_settings_field(
        'CPPP_enable_merchant_email',
        'Enable merchant email',
        'CPPP_settings_enable_merchant_email',
        'cp-paylink-settings',
        'CPPP_main_section',
        array(
            'label_for' => 'Enable merchant email'
        )
    );

    add_settings_field(
        'CPPP_identifier_prefix',
        'Identifier Prefix',
        'CPPP_settings_identifier_prefix',
        'cp-paylink-settings',
        'CPPP_main_section',
        array(
            'label_for' => 'Identifier prefix'
        )
    );

    add_settings_field(
        'CPPP_postback_url',
        'Postback URL',
        'CPPP_settings_postback_url',
        'cp-paylink-settings',
        'CPPP_main_section',
        array(
            'label_for' => 'Postback URL'
        )
    );

    add_settings_field(
        'CPPP_enable_test_mode',
        'Enable test mode',
        'CPPP_settings_enable_test_mode',
        'cp-paylink-settings',
        'CPPP_main_section',
        array(
            'label_for' => 'Enable test mode'
        )
    );

    add_settings_field(
        'CPPP_enable_debug_mode',
        'Enable debug mode',
        'CPPP_settings_enable_debug_mode',
        'cp-paylink-settings',
        'CPPP_main_section',
        array(
            'label_for' => 'Enable debug mode'
        )
    );


    register_setting('cp-paylink-settings', CPPP_MERCHANT_ID, 'CPPP_settings_validate_merchant_id');
    register_setting('cp-paylink-settings', CPPP_LICENCE_KEY, 'CPPP_settings_validate_licence_key');
    register_setting('cp-paylink-settings', CPPP_IDENTIFIER_PREFIX, 'CPPP_settings_validate_identifier_prefix');
    register_setting('cp-paylink-settings', CPPP_POSTBACK_URL, 'CPPP_settings_validate_postback_url');
    register_setting('cp-paylink-settings', CPPP_MERCHANT_EMAIL_ADDRESS, 'CPPP_settings_validate_merchant_email_address');
    register_setting('cp-paylink-settings', CPPP_ENABLE_MERCHANT_EMAIL, 'CPPP_settings_validate_enable_merchant_email');
    register_setting('cp-paylink-settings', CPPP_ENABLE_TEST_MODE, 'CPPP_settings_validate_enable_test_mode');
    register_setting('cp-paylink-settings', CPPP_ENABLE_DEBUG_MODE, 'CPPP_settings_validate_enable_debug_mode');
}

function CPPP_settings_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    echo '<div class="wrap"><h1>'
        . esc_html__('CityPay PayLink for WordPress', 'cp-paylink-wp')
        . '</h1><form method="post" action="options.php">';

    settings_fields('cp-paylink-settings');
    do_settings_sections('cp-paylink-settings');
    submit_button();

    echo '</form></div>';
}

function CPPP_exempt_shortcodes_from_texturize($shortcodes)
{
    array_push($shortcodes, 'citypay-payform-shortcode-sink', 'citypay-payform-shortcode-passthrough', 'citypay-payform');

    return $shortcodes;
}


function CPPP_standalone_button($attrs)
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

    $current_url = get_permalink();
    $form_action = add_query_arg('cp_paylink', 'pay_btn', $current_url);

    $sc_output = '<form action="' . $form_action . '" method="post">'
        . '<input type="hidden" name="amount" value= "' . $amount . '" />'
        . '<input type="hidden" name="identifier" value= "' . $identifier . '" />'
        . '<input type="hidden" name="description" value= "' . $description . '" />'
        . wp_nonce_field(-1, 'cp_nonce_field', true, false)
        . '<button type="submit" class="uk-button uk-button-primary uk-button-large">'
        . $attrs['label']
        . '</button>'
        . '</form>';

    return $sc_output;
}

function CPPP_create_token($amount, $identifier, $description)
{
    require_once('includes/class-citypay-logger.php');
    require_once('includes/class-citypay-paylink.php');


    $merchant_id = get_option(CPPP_MERCHANT_ID);
    $licence_key = get_option(CPPP_LICENCE_KEY);
    $identifier_prefix = get_option(CPPP_IDENTIFIER_PREFIX);
    $test_mode = get_option(CPPP_ENABLE_TEST_MODE);


    $page_id = get_query_var('page_id');
    if (get_option('permalink_structure')) {
        $current_url = get_permalink($page_id);
    } else {
        $current_url = add_query_arg('page_id', $page_id, get_home_url());
    }

    $cp_pl_postback_url = get_option(CPPP_POSTBACK_URL);

    if ($cp_pl_postback_url) {
        $postback_url = $cp_pl_postback_url;
    } else {
        $postback_url = add_query_arg(CPPP_DISPATCHER, 'postback', $current_url);
    }

    $success_url = $current_url . '?payment-result=success';
    $failure_url = $current_url . '?payment-result=failed';

    $logger = new CPPP_Logger(__FILE__);
    $paylink = new CPPP_WP($logger);

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
        'PayLink-Standalone-Button',
        CPPP_VERSION
    );

    $paylink->setRequestConfig(
        $test_mode,
        $postback_url,
        $success_url,
        $failure_url
    );

    $merchant_email = get_option(CPPP_MERCHANT_EMAIL_ADDRESS);
    if (!empty($merchant_email)) {
        $paylink->setRequestMerchant($merchant_email);
        $enable_merchant_email = get_option(CPPP_ENABLE_MERCHANT_EMAIL, false);
        if (!$enable_merchant_email) {
            $paylink->setRequestConfigOption('BYPASS_MERCHANT_EMAIL');
        }
    }

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

add_action('init', 'CPPP_init');
add_action('admin_init', 'CPPP_admin_init');
add_action('wp_loaded', 'CPPP_wp_loaded');
add_filter('no_texturize_shortcodes', 'CPPP_exempt_shortcodes_from_texturize');

register_activation_hook(__FILE__, 'CPPP_install');