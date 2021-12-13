#!/bin/bash

BASEDIR=$(dirname "$0")
HOSTIP=172.17.0.1

sed -ri -e "s/^; extension=runkit.so/extension=runkit.so/" /usr/local/lib/php.ini
sed -ri -e "s/^; runkit.internal_override=1/runkit.internal_override=1/" /usr/local/lib/php.ini
sed -ri -e "s/^; zend_extension=\/usr\/local\/lib\/php\/extensions\/no-debug-non-zts-20100525\/ioncube_loader_lin_5.4.so/zend_extension=\/usr\/local\/lib\/php\/extensions\/no-debug-non-zts-20100525\/ioncube_loader_lin_5.4.so/" /usr/local/lib/php.ini
sed -ri -e "s/^extension=uopz.so/; extension=uopz.so/" /usr/local/lib/php.ini
apache2ctl restart

$BASEDIR/run.py --rabbitmq_ip=$HOSTIP --hook_extension=runkit $@
