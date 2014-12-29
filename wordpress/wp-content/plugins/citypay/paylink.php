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

define(CP_PAYLINK_MERCHANT_ID, 'cp_paylink_merchant_id');
define(CP_PAYLINK_LICENCE_KEY, 'cp_paylink_licence_key');
define(CP_PAYLINK_TEST_MODE, 'cp_paylink_test_mode');
define(CP_PAYLINK_DEBUG_MODE, 'cp_paylink_debug_mode');

function cp_paylink_enqueue_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri().'/style.css');
    wp_enqueue_style('child-style', get_stylesheet_uri(), array('parent-style'));
}

function cp_paylink_enqueue_javascript() {
    wp_enqueue_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js');
    wp_enqueue_script('paylink', 'https://secure.citypay.com/paylink3/js/paylink-api-1.0.0.min.js', array('jquery'));
}

function cp_paylink_send_cors_headers($headers)
{
    error_log("send_cors_headers: ".$headers);
    $headers['Access-Control-Allow-Origin'] = "https://secure.citypay.com";
    return $headers;
}


function cp_paylink_payform($attrs) {
    
    $a = shortcode_atts(
            array('form' => ''),
            $attrs
        );
    
    $mode_option_value = get_option(CP_PAYLINKJS_PAYFORM_MODE_OPTION_NAME, true);
    if ($mode_option_value) {
        $domain_key = get_option(CP_PAYLINKJS_PAYFORM_TEST_DOMAIN_KEY_OPTION_NAME, '');
    } else {
        $domain_key = get_option(CP_PAYLINKJS_PAYFORM_LIVE_DOMAIN_KEY_OPTION_NAME, '');
    }
      
    if (is_single() or is_page())
    {
        $s = '<div id="cp_paylinkjs_payform"></div><script type="text/javascript">$ = jQuery;';
        
        $s .= 'var paylinkJs = new Paylink("'
            .$domain_key
            .'", {form: {';
        
        if ($a['form'] == '')
        {
            $s .= 'name: {label: "Full Name", order: 1},'
                .'identifier: { placeholder: "AC9999",'
                .'pattern: "[A-Za-z]{2}[0-9]{4}",'
                .'label: "Account No", order: 2}, '
                .'amount: {label: "Total Amount", order: 3}';
        }
        else
        {
            $s .= $a['form'];
        }
                        
        $s .= '}});paylinkJs.billPayment("#cp_paylinkjs_payform");</script>';
        
                    
        return $s;
    }
    
    
    return 'other';
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
        __('CityPay PayLink WP', 'cp-paylink-wp'),
        __('CityPay PayLink WP', 'cp-paylink-wp'),
        'manage_options',
        'cp-paylink-settings',
        'cp_paylink_settings_page'
    );
}


function cp_paylink_settings_page()
{
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

$plugin = plugin_basename(__FILE__);

//add_filter('wp_headers', array('cp_paylinkjs_send_cors_headers'));
add_filter('plugin_action_links_'.$plugin, 'cp_paylink_settings_link');

add_shortcode('citypay-paylink-payform', 'cp_paylink_payform');