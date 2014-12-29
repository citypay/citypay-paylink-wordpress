<?php

add_action('wp_enqueue_scripts', 'cp_paylinkjs_theme_enqueue_styles');
add_action('wp_enqueue_scripts', 'cp_paylinkjs_theme_enqueue_javascript');

add_filter( 'wp_headers', array('cp_paylinkjs_send_cors_headers_x'));

/*        'http://'.$admin_origin['host'],
        'https://'.$admin_origin['host'],
        'http://'.$home_origin['host'],
        'https://'.$home_origin['host']));*/

function cp_paylinkjs_theme_enqueue_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri().'/style.css');
    wp_enqueue_style('child-style', get_stylesheet_uri(), array('parent-style'));
}

function cp_paylinkjs_theme_enqueue_javascript() {
    wp_enqueue_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js');
    wp_enqueue_script('paylink', get_stylesheet_directory_uri().'/paylink-api-1.0.0.js', array('jquery'));
}

function cp_paylinkjs_send_cors_headers_x($headers)
{
    error_log("send_cors_headers: ".$headers);
    $headers['Access-Control-Allow-Origin'] = "https://secure.citypay.com";
    return $headers;
}
