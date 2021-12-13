#!/usr/bin/python3

import os
import sys

SITE = 'http://127.0.0.1'
data = {
  'contao': ['contao_poc.py', 'k.jones', 'kevinjones'],
  'piwik': ['piwik_poc.py', 'admin', 'asdf1234'],
  'glpi': ['glpi_poc.py', 'glpi', 'glpi'],
  'joomla': ['joomla_poc.py'],
  'cubecart': ['cubecart_poc.py'],
  'cmsmadesimple': ['cmsmadesimple_poc.py', 'admin', 'asdf1234'],
  'owa': ['owa_poc.py'],  
  'vanilla': ['vanilla_poc.py', 'admin', 'asdf1234'],
}

if len(sys.argv) != 2:
  print ('[Usage] {} [app_path]'.format(sys.argv[0]))
  sys.exit()

ROOT = "http://127.0.0.1"
APP_PATH = sys.argv[1][:-1] if sys.argv[1].endswith('/') else sys.argv[1]
APP_NAME = os.path.basename(APP_PATH)

for k, v in data.items():
  if k in APP_NAME:
    script = v[0]
    args = " ".join(v[1:])
    cmd = 'python3 {} {}/{} {}'.format(script, ROOT, APP_NAME, args)
    print(cmd)
    os.system(cmd)
    break

