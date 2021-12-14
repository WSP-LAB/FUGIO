# evalhook
Stefan Esser

# How to install
## On Debian/Ubuntu

```
sudo apt-get install php5-dev build-essential git
git clone https://github.com/unreturned/evalhook
cd evalhook
phpize
./configure
make
sudo make install
```

# How to use

```
php -d extension=evalhook.so file.php
```
