#!/usr/bin/python3

import os
import sys

if len(sys.argv) != 2:
    print("[Usage] {} [org/ccs/phpggc]".format(sys.argv[0]))
    sys.exit()

if sys.argv[1] == "org":
    os.system("cp Analyzer/chain.py.org Analyzer/chain.py")
    os.system("cp Proxy/proxy.py.org Proxy/proxy.py")

elif sys.argv[1] == "ccs":
    os.system("cp Analyzer/chain.py.ccs Analyzer/chain.py")

elif sys.argv[1] == "phpggc":
    os.system("cp Analyzer/chain.py.phpggc Analyzer/chain.py")
    os.system("cp Proxy/proxy.py.phpggc Proxy/proxy.py")

else:
    print("[Usage] {} [org/ccs/phpggc]".format(sys.argv[0]))
