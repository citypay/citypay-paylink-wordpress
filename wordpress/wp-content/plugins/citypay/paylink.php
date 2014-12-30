<?php
/**
 * Plugin Name: CityPay Paylink WP
 * Plugin URI: http://citypay.com/paylink
 * Description: Include an arbitrary payment processing form.
 * Version: 1.0.0
 * Author: CityPay Limited
 * Author URI: http://citypay.com
 */

defined('ABSPATH') or die;

define(CP_PAYLINK_DISPATCHER, 'cp_paylink');
define(CP_PAYLINK_MERCHANT_ID, 'cp_paylink_merchant_id');
define(CP_PAYLINK_LICENCE_KEY, 'cp_paylink_licence_key');
define(CP_PAYLINK_TEST_MODE, 'cp_paylink_test_mode');
define(CP_PAYLINK_DEBUG_MODE, 'cp_paylink_debug_mode');

require_once('includes/stack.php');

function cp_paylink_config_stack() {
    static $cp_paylink_config_stack = NULL;
    if (is_null($cp_paylink_config_stack)) {
        $cp_paylink_config_stack = new cp_paylink_config_stack();
    }
    return $cp_paylink_config_stack;
}

function cp_paylink_enqueue_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri().'/style.css');
    wp_enqueue_style('child-style', get_stylesheet_uri(), array('parent-style'));
}

function cp_paylink_enqueue_javascript() {
    wp_enqueue_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js');
    wp_enqueue_script('paylink', 'https://secure.citypay.com/paylink3/js/paylink-api-1.0.0.min.js', array('jquery'));
}

function cp_paylink_send_cors_headers($headers) {
    error_log("send_cors_headers: ".$headers);
    $headers['Access-Control-Allow-Origin'] = "https://secure.citypay.com";
    return $headers;
}

function cp_paylink_payform_field_config_sort($v1, $v2) {
    $v1_order = (int) $v1['order'];
    $v2_order = (int) $v2['order'];
    
    if ($v1_order> $v2_order) {
        return 1;
    } else if ($v1_order < $v2_order) {
        return -1;
    } else return 0;
}

function cp_paylink_payform_field($attrs) {
    $a = shortcode_atts(
            array(
                    'name' => '',
                    'label' => '',
                    'placeholder' => '',
                    'pattern' => '',
                    'order' => 99
                ),
            $attrs
        );
        
    cp_paylink_config_stack()->set(
            $a['name'],
            array(
                    'label' => $a['label'],
                    'placeholder' => $a['placeholder'],
                    'pattern' => $a['pattern'],
                    'order' => $a['order']
                )
        );
    
    return;
}

function cp_paylink_payform($attrs, $content = null) {
    $a = shortcode_atts(
            array('form' => ''),
            $attrs
        );
    
    //
    //  If shortcode contains nested shortcodes, process these before
    //  processing the immediate form.
    //
    if (!is_null($content)) {
        cp_paylink_config_stack()->push_new();
        $content = do_shortcode($content);
    }
 
    if (is_single() || is_page())
    {
        // if a configuration has been specified
        $current_url = add_query_arg($wp->query_string, '', home_url($wp->request));
        $s = '<form role="form" id="billPaymentForm" class="form-horizontal" method="POST" action="'
           .add_query_arg('cp_paylink', 'pay', $current_url)
           .'"><input type="hidden" name="cp_paylink_pay" value="Y">';
                
        $config = cp_paylink_config_stack()->peek();
        // sort config according to the order attribute
        
        usort($config, cp_paylink_payform_field_config_sort);
        
        foreach ($config as $key => $value) {
            $s .= '<div class="form-group">'
                .'<label class="com-sm-2 control-label">'
                .$value['label']
                .'</label><div class="col-sm-10"><input class="form-control" name="" type="text">'
                .'</div></div>';
        }
        
        $s .= '<button type="submit">Pay</button></form>';
    }
    else
    {
        $s = '';
    }
        
    if (!is_null($content))
    {
        cp_paylink_config_stack()->pop();
    }
    
    return $s;
}

function cp_paylink_validate_identifier($pattern, $value) {
    return 'tia-maria-'.$value;
}

function cp_paylink_validate_amount($value) {
    return 2300;
}

function cp_paylink_action_pay() {
    require_once('includes/logger.php');
    require_once('includes/paylink.php');

    $merchant_id = get_option(CP_PAYLINK_MERCHANT_ID);
    $licence_key = get_option(CP_PAYLINK_LICENCE_KEY);
    $test_mode = get_option(CP_PAYLINK_TEST_MODE);

    $identifier_in = filter_input(INPUT_POST, 'identifier', FILTER_REQUIRE_SCALAR);
    $identifier = cp_paylink_validate_identifier('', $identifier_in);

    $amount_in = filter_input(INPUT_POST, 'amount', FILTER_REQUIRE_SCALAR);
    $amount = cp_paylink_validate_amount($amount_in);

    $current_url = add_query_arg($wp->query_string, '', home_url($wp->request));
    $postback_url = add_query_arg(CP_PAYLINK_DISPATCHER, 'postback', $current_url);
    $return_url = add_query_arg(CP_PAYLINK_DISPATCHER, 'success', $current_url);
    $cancel_url = add_query_arg(CP_PAYLINK_DISPATCHER, 'cancel', $current_url);

    $x = new CityPay_PayLink(new logger());
    $x->setRequestCart(
            $merchant_id,
            $licence_key,
            $identifier,
            $amount
        );
    $x->setRequestClient('Wordpress', get_bloginfo('version', 'raw'));
    $x->setRequestConfig(
            $test_mode,
            $postback_url,
            $return_url,
            $cancel_url
        );
    try {
        $url = $x->getPaylinkURL();
        wp_redirect($url);
        exit;
    } catch (Exception $ex) {
        echo $ex;
    }
}

function cp_paylink_dispatcher() {
    //
    if (isset($_GET[CP_PAYLINK_DISPATCHER])) {
        $action = $_GET[CP_PAYLINK_DISPATCHER];
        switch ($action)
        {
        case 'pay':
            cp_paylink_action_pay();
            break;

        case 'postback':
            break;

        case 'success':
            break;

        case 'cancel':
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

function cp_paylink_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    $hidden_field_name = 'cp_paylink_payform_hidden_field';
    
    $merchant_id_option_value = get_option(CP_PAYLINK_MERCHANT_ID, '');
    $licence_key_option_value = get_option(CP_PAYLINK_LICENCE_KEY, '');
    $test_mode_option_value = get_option(CP_PAYLINK_TEST_MODE, true);
    $debug_mode_option_value = get_option(CP_PAYLINK_DEBUG_MODE, true);
    
    echo '<div class="">';
    echo '<h2>'.__( 'CityPay PayLink WP', 'cp-paylink-wp').'</h2>';

    if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y') {
       
        $merchant_id_option_value = filter_input(INPUT_POST, CP_PAYLINK_MERCHANT_ID, FILTER_DEFAULT, FILTER_REQUIRE_SCALAR);
        update_option(CP_PAYLINK_MERCHANT_ID, $merchant_id_option_value);
        
        $licence_key_option_value = filter_input(INPUT_POST, CP_PAYLINK_LICENCE_KEY, FILTER_DEFAULT, FILTER_REQUIRE_SCALAR);
        update_option(CP_PAYLINK_LICENCE_KEY, $licence_key_option_value);
        
        $test_mode_option_value = (filter_input(INPUT_POST, CP_PAYLINK_TEST_MODE, FILTER_DEFAULT, FILTER_REQUIRE_SCALAR) == 'on');  
        update_option(CP_PAYLINK_TEST_MODE, $test_mode_option_value);
        
        $debug_mode_option_value = (filter_input(INPUT_POST, CP_PAYLINK_DEBUG_MODE, FILTER_DEFAULT, FILTER_REQUIRE_SCALAR) == 'on');  
        update_option(CP_PAYLINK_DEBUG_MODE, $debug_mode_option_value);
        
        echo '<div class="updated below-h2"><p><strong>'
            .__('Updated settings saved.', 'updated-settings-saved')
            .'</strong></p></div>';
    }
    
    ?>
    
<form name="form1" method="post" action="">
<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
<table class="form-table">
    <tbody>
    <tr>
        <th class="titledesc"><label><?php _e("Merchant ID", 'merchant-id'); ?></label></th>
        <td class="forminp"><input type="text" name="<?php echo CP_PAYLINK_MERCHANT_ID; ?>" value="<?php echo $merchant_id_option_value; ?>" size="16"></input></td>
    </tr>
    <tr>
        <th class="titledesc"><label><?php _e("Licence key", 'licence-key'); ?></label></th>
        <td class="forminp"><input type="text" name="<?php echo CP_PAYLINK_LICENCE_KEY; ?>" value="<?php echo $licence_key_option_value; ?>" size="16"></input></td>
    </tr>
    <tr>
        <th class="titledesc"><label><?php _e("Test Mode", 'test-mode'); ?></label></th>
        <td class="forminp">
            <fieldset>
                <label><input type="checkbox" name="<?php echo CP_PAYLINK_TEST_MODE; ?>" <?php echo ($test_mode_option_value?'checked':''); ?>></input>
                    Generate transactions using test mode
                </label><p class="description">Use this whilst testing your integration. You must disable test mode when you are ready to take live transactions.</p>
            </fieldset>
        </td>
    </tr>
    <tr>
        <th class="titledesc"><label><?php _e("Debug Mode", 'debug-mode'); ?></label></th>
        <td class="forminp">
            <fieldset>
                <label><input type="checkbox" name="<?php echo CP_PAYLINK_DEBUG_MODE; ?>" <?php echo ($debug_mode_option_value?'checked':''); ?>></input>
                    Enable logging
                </label><p class="description">Log payment events, such as postback requests, inside <code>XXXXX</code>.</p>
            </fieldset>
        </td>
    </tr>
    </tbody>
</table>
<p class="submit">
<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
</p>

</form>
    
    <?php
    
    echo '</div>';
}

add_action('wp_enqueue_scripts', 'cp_paylink_enqueue_styles');
add_action('wp_enqueue_scripts', 'cp_paylink_enqueue_javascript');
add_action('admin_menu', 'cp_paylink_administration');

add_action('init', 'cp_paylink_dispatcher');

$plugin = plugin_basename(__FILE__);

//add_filter('wp_headers', array('cp_paylinkjs_send_cors_headers'));
add_filter('plugin_action_links_'.$plugin, 'cp_paylink_settings_link');

add_shortcode('citypay-payform-field', 'cp_paylink_payform_field');
add_shortcode('citypay-payform', 'cp_paylink_payform');
