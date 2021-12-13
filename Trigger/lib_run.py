#!/usr/bin/python3
import requests
import sys
import base64

if len(sys.argv) < 2:
    print ('[Usage] {} [application_path]'.format(sys.argv[0]))
    sys.exit()

ROOT = "http://127.0.0.1/"
URL = sys.argv[1]
URL = URL.split('/')[-2] if URL.endswith('/') else URL.split('/')[-1]
inp = base64.b64encode(b'O:17:"dummy_class_r353t":1:{s:12:"used_methods";a:0:{}}')
FULL_URL = ROOT + URL+"/?input="+inp.decode('utf-8')
print (FULL_URL)
r = requests.post(FULL_URL)
print(r.text)
