#!/bin/bash

# the wordpress entry point looks for a file starting with apache2 hence the name, this file actually runs the setup process just before running apache in the foreground
echo ========== Initial Plugin List =============
wp --allow-root plugin list
echo ============================================

cd wp-content/plugins
echo ========== Loading CityPay Wordpress Plugin List =============
wget https://github.com/citypay/citypay-paylink-wordpress/archive/${CITYPAY_PLUGIN_VERSION}.zip
unzip citypay-paylink-wordpress-${CITYPAY_PLUGIN_VERSION}.zip
rm citypay-paylink-wordpress-${CITYPAY_PLUGIN_VERSION}.zip
chown -R www-data:www-data ./*
cd ../../
echo ========== Activate CityPay Wordpress Plugin =================
wp --allow-root plugin activate citypay-paylink-wordpress
echo =================================================================




echo ========== Updated Plugin List =============
wp --allow-root plugin list
echo ============================================

# run apache in the foreground...
apache2-foreground


