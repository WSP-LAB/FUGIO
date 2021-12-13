#!/usr/bin/python3

import os
import sys

SITE = 'http://127.0.0.1'
PHAR = '/app/phar_validator/dummy_class_r353t.png'

if len(sys.argv) != 2:
  print ('Usage: {} [app_path]'.format(sys.argv[0]))
  sys.exit()

APP_PATH = sys.argv[1][:-1] if sys.argv[1].endswith('/') else sys.argv[1]
APP_NAME = os.path.basename(APP_PATH)

if APP_NAME == "wordpress-WooCommerce-3.4.0":
    script = 'woocommerce_poc.py'
    args = ['admin', 'asdf1234', PHAR]
elif "wordpress" in APP_NAME:
    script = 'wordpress_poc.py'
    args = ['admin', 'asdf1234', PHAR]

cmd = 'python3 {} {}/{} {}'.format(script, SITE, APP_NAME, ' '.join(args))
print(cmd)
os.system(cmd)
