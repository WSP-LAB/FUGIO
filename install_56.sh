#!/bin/bash
pip3 install -r requirements.txt

cd Lib/rabbitmq_php
composer install
cd ../..

cd Lib/PHP-Parser
composer install
cd ../..

cd Lib/evalhook
phpize
./configure
make && make install
cd ../..

cd Lib/uopz
phpize
./configure
make && make install
cd ../..

cd Lib/pcntl56
phpize
./configure
make && make install
cd ../..

grep -qF -- "extension=evalhook.so" /etc/php/5.6/apache2/php.ini || echo "extension=evalhook.so" >> /etc/php/5.6/apache2/php.ini
grep -qF -- "extension=uopz.so" /etc/php/5.6/apache2/php.ini || echo "extension=uopz.so" >> /etc/php/5.6/apache2/php.ini
grep -qF -- "extension=uopz.so" /etc/php/5.6/cli/php.ini || echo "extension=uopz.so" >> /etc/php/5.6/cli/php.ini
grep -qF -- "extension=pcntl.so" /etc/php/5.6/apache2/php.ini || echo "extension=pcntl.so" >> /etc/php/5.6/apache2/php.ini

