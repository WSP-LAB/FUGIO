#!/bin/bash

USER=fugio
PASS=fugio_password
docker run -d --name rabbitmq -p 5672:5672 -p 15672:15672 -p 25672:25672 \
	--restart=unless-stopped \
	-e RABBITMQ_DEFAULT_USER=$USER \
	-e RABBITMQ_DEFAULT_PASS=$PASS \
	rabbitmq:management

