#! /bin/sh
_CURR_DIR=`pwd`
_TMP_DIR=`mktemp -d /tmp/deploy.XXX` || exit 1
echo $_TMP_DIR
mkdir $_TMP_DIR/citypay
cp -r README.md paylink.php includes $_TMP_DIR/citypay
rm $_TMP_DIR/citypay/includes/class-citypay-pattern.php
cd $_TMP_DIR
zip -r paylink.zip citypay/*
tar -cvf paylink.tar citypay/*
gzip paylink.tar
cd $_CURR_DIR
mv $_TMP_DIR/paylink.zip $_TMP_DIR/paylink.tar.gz $_CURR_DIR/deploy

if [ -d $_TMP_DIR ]; then
  rm -r $_TMP_DIR 
fi
