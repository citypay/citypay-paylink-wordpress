<?php

add_action('wp_enqueue_scripts', 'theme_enqueue_styles');
add_action('wp_enqueue_scripts', 'paylink_javascript');

add_filter( 'wp_headers', array('send_cors_headers'));

/*        'http://'.$admin_origin['host'],
        'https://'.$admin_origin['host'],
        'http://'.$home_origin['host'],
        'https://'.$home_origin['host']));*/

function theme_enqueue_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri().'/style.css');
    wp_enqueue_style('child-style', get_stylesheet_uri(), array('parent-style'));
}

function paylink_javascript() {
    wp_enqueue_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js');
    wp_enqueue_script('paylink', 'https://secure.citypay.com/paylink3/js/paylink-api-1.0.0.min.js', array('jquery'));
}

function send_cors_headers( $headers )
{
    $headers['Access-Control-Allow-Origin'] = get_http_origin().",http://localhost:8080,https://localhost:8080";
    return $headers;
}
