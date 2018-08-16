#!/bin/bash

mkdir -p citypay-paylink-wordpress
cp -R src/* citypay-paylink-wordpress/
cp readme.* citypay-paylink-wordpress/

VERSION=$(awk '/Version: /{print $NF}' src/paylink.php)
echo $VERSION

zip -r citypay-paylink-wordpress-$VERSION.zip citypay-paylink-wordpress \
 && rm -rf citypay-paylink-wordpress && mkdir -p dist && mv citypay-paylink-wordpress-$VERSION.zip dist
