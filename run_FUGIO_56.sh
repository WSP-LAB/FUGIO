#!/bin/bash

BASEDIR=$(dirname "$0")
HOSTIP=172.17.0.1

$BASEDIR/run.py --rabbitmq_ip=$HOSTIP $@
