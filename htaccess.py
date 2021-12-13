#!/usr/bin/python3

import os
import sys

if len(sys.argv) != 2:
    print("[Usage] {} [on/off]".format(sys.argv[0]))
    sys.exit()

if sys.argv[1] == "on":
    os.system('echo "php_value auto_prepend_file /FUGIO/Files/hook_sensitive_functions.php" > /app/.htaccess')
elif sys.argv[1] == "off":
    os.system('rm /app/.htaccess')
else:
    print("[Usage] {} [on/off]".format(sys.argv[1]))
