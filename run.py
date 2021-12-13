#!/usr/bin/python3 -B
import sys
import os

from Utils import arg, Bootstrap
from Detector.DetectorManager import *
from Analyzer.analyzer import *

args = arg.parse()
target = args.target
DOC_ROOT = args.doc_root
rabbitmq_ip = args.rabbitmq_ip
hook_extension = args.hook_extension
php_ver = args.php_ver
all_files = args.all
cpus = args.cpus

# Get Class List
analyzer = Analyzer(target, rabbitmq_ip)
class_list = analyzer.class_list

# Make hooking file
B = Bootstrap()
validator_md5 = B.makePharValidator(DOC_ROOT)
B.makeHookFile(rabbitmq_ip, DOC_ROOT, validator_md5,
               hook_extension, php_ver, class_list)

# Start Detection
DM = DetectorManager(target, rabbitmq_ip, php_ver, all_files, cpus)
DM.startManager(1, rabbitmq_ip) # Thread count

# Usage
# Need to link Detection stage and chain generation stage
exit()
# ------------
