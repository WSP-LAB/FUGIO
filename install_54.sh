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

cd Lib/runkit
phpize
./configure
make && make install
cd ../..

cd Lib/uopz
phpize
./configure
make && make install
cd ../..

cd Lib/pcntl54
phpize
./configure
make && make install
cd ../..

cd Lib/php-jsond
phpize
./configure
make && make install
cd ../..

grep -qF -- "extension=evalhook.so" /usr/local/lib/php.ini || echo "extension=evalhook.so" >> /usr/local/lib/php.ini
grep -qF -- "extension=uopz.so" /usr/local/lib/php.ini || echo "extension=uopz.so" >> /usr/local/lib/php.ini
grep -qF -- "extension=pcntl.so" /usr/local/lib/php.ini || echo "extension=pcntl.so" >> /usr/local/lib/php.ini
grep -qF -- "extension=jsond.so" /usr/local/lib/php.ini || echo "extension=jsond.so" >> /usr/local/lib/php.ini
grep -qF -- "; extension=runkit.so" /usr/local/lib/php.ini || echo "; extension=runkit.so" >> /usr/local/lib/php.ini
grep -qF -- "; runkit.internal_override=1" /usr/local/lib/php.ini || echo "; runkit.internal_override=1" >> /usr/local/lib/php.ini
sed -i "s/;phar.readonly = On/phar.readonly = Off/g" /usr/local/lib/php.ini
