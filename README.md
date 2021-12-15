# FUGIO

FUGIO is the first automatic exploit generation (AEG) tool for PHP object injection (POI) vulnerabilities. 
When exploiting a POI vulnerability, an attacker crafts an injection object by carefully choosing 
its property values to invoke a chain of existing class methods or functions (gadgets) for finally triggering 
a sensitive function with attack payloads. The technique used in composing this exploit object is 
called property-oriented programming (POP).
FUGIO identifies feasible POP gadget chains considering the availability of gadgets and 
their caller-callee relationships via static and dynamic analyses.
FUGIO then conducts a feedback-driven fuzzing campaign for each identified POP chain, thus producing exploit objects.
For more details, please refer to our [paper](https://www.usenix.org/conference/usenixsecurity22/presentation/park-sunnyeo),
"FUGIO: Automatic Exploit Generation for PHP Object Injection Vulnerabilities", which will appear in USENIX Security 2022.

## Installation
FUGIO is tested on a machine running Ubuntu 18.04. Python 3 and PHP (5.4, 5.6, or 7.2) are required to run FUGIO.
We provide three Docker images depending on PHP versions in [FUGIO-artifact](https://github.com/WSP-LAB/FUGIO-artifact).
If you use the given Docker images, follow the instructions in [Prepare Docker containers](https://github.com/WSP-LAB/FUGIO-artifact#prepare-docker-containers)
and then go to the [phase 2]().

### Phase 1
* Clone git repo
  ```
  git clone --recurse-submodules https://github.com/WSP-LAB/FUGIO.git
  cd FUGIO
  ```

* Install Docker
  - To install Docker CE, please follow the instructions in this 
  [link](https://docs.docker.com/install/linux/docker-ce/ubuntu/).
  - For our scripts not to ask you for sudo password, we assumed that
    you run Docker commands as a non-root user. Please follow the instructions in
    this [link](https://docs.docker.com/install/linux/linux-postinstall/).
  
* Run RabbitMQ Docker
  - You can set up RabbitMQ by running `./run_rabbitmq.sh`.
    - Username: fugio
    - Password: fugio_password
    - RabbitMQ Management port: 15672

* Install Python 3 pip
  ```
  sudo apt-get install -y python3-pip
  ```

* Install PHP Composer
  ```
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
  php composer-setup.php && \
  php -r "unlink('composer-setup.php');" && \
  mv composer.phar /usr/local/bin/composer
  ```

* Install PHP libraries
  - PHP 5.4: we installed PHP 5.4 by compiling source code
    ```
    sudo apt-get install -y autoconf apache2-dev libxml2-dev libbz2-dev \
    libcurl4-gnutls-dev libjpeg-dev libpng-dev libmcrypt-dev
    sudo ln -s /usr/include/x86_64-linux-gnu/curl /usr/local/include/

    wget -O /tmp/bison-2.6.tar.gz http://ftp.gnu.org/gnu/bison/bison-2.6.tar.gz
    tar -xvf /tmp/bison-2.6.tar.gz -C /tmp
    cd /tmp/bison-2.6
    ./configure --prefix=/usr/local/bison --with-libiconv-prefix=/usr/local/libiconv/
    make
    sudo make install
    sudo ln -s /usr/local/bison/bin/bison /usr/bin/bison

    git clone https://github.com/openssl/openssl.git /tmp/openssl
    cd /tmp/openssl
    git checkout OpenSSL_1_0_2-stable
    ./config shared
    make
    sudo make install

    git clone https://github.com/php/php-src.git /tmp/php-src
    cd /tmp/php-src
    git checkout PHP-5.4
    ./buildconf
    ./configure --with-mysql --with-zlib --with-gd --with-mhash --with-mcrypt \
        --with-curl --with-openssl --with-zlib --with-jpeg-dir --with-png-dir --with-gettext \
        --with-pcre-regex --with-pdo-mysql --enable-calendar --enable-exif --with-bz2 \
        --enable-ftp --enable-mbstring --enable-shmop --enable-soap --enable-bcmath \
        --enable-sockets --enable-wddx --enable-zip --with-mysqli --with-apxs2=/usr/bin/apxs2
    make
    sudo make install
    sudo cp php.ini-production /usr/local/lib/php.ini
    ```
  - PHP 5.6
    ```
    sudo apt-get install -y php5.6-dev php5.6-bcmath php5.6-mbstring php5.6-xml
    ```
  - PHP 7.2
    ```
    sudo apt-get install -y php7.2-dev php7.2-bcmath php7.2-mbstring php7.2-xml
    ```

### Phase 2
* Install dependencies
  - We provide scripts for installing all dependencies and changing PHP settings.
  Run `./install_XX.sh` depending on the version of PHP.
    - PHP 5.4: `./install_54.sh`
    - PHP 5.6: `./install_56.sh`
    - PHP 7.2: `./install_72.sh`

* Prepare the target application source code and its running service
  - In [FUGIO-artifact](https://github.com/WSP-LAB/FUGIO-artifact), we provide 30 PHP applications
  that are evaluated in our paper. If you want to use it, refer to 
  [Build benchmarks](https://github.com/WSP-LAB/FUGIO-artifact#2-build-benchmarks).

* Setting for monitoring POI vulnerabilities
  - Add `.htaccess` file for monitoring POI vulnerabilities by running `./htaccess.py on`.
  If you want to stop monitoring, run `htaccess.py off`.

## Usage
* Execute FUGIO using `./run.py`.
  ```
  # ./run.py
  usage: run.py [-h] [--all] [--rabbitmq_ip RABBITMQ_IP]
                [--php_ver {5,7}] [--hook_extension {uopz,runkit}] [--cpus CPUS]
                target
  ```
  - `rabbitmq_ip`: the IP address of RabbitMQ 
  - `php_ver`: the version of PHP, choose 5 or 7 (default 5)
  - `hook_extension`: the library for using hooks to PHP built-in functions, choose uopz or runkit (default uopz)\
  _Since some applications such as CubeCart conflicts with uopz library, we also support runkit._
  - `cpus`: the number of CPU cores for assigning to run FUGIO (default all CPU cores)
  - `target`: the path of the target application source code
  - `all`: enable if you want to consider all gadgets regardless of their availability (default false)\
  _In [Dahse et al.](https://dl.acm.org/doi/abs/10.1145/2660267.2660363), the authors assume that all existing classes 
  are loadable when there exists at least one autoloader callback in an application. Although this assumption is 
  no longer valid because this bug was patched in PHP 5.4.24 and 5.5.8, we added this option for a fair comparison 
  to FUGIO in the paper._
  
  For more concrete examples, refer to scripts `run_FUGIO_XX.sh`.
  
  If you run the command, FUGIO starts to analyze the source code of the target application.
  At the first run, FUGIO generates a dump file in `Files/dump_files`.
  It is for reducing time to analyze the target source code when you run FUGIO again for the same application.
  If the source code of the target application changed, you need to delete its dump file and run the script again.
  
* Monitor POI vulnerabilities
  After FUGIO finishes analyzing the source code, FUGIO starts to monitor a POI vulnerability.
  We can trigger POI vulnerabilities using crawlers, spiders, or manual browsing.
  In `Trigger` folder, we provide scripts for triggering POI vulnerabilities of our benchmarks; you can find the details
  in [FUGIO-artifact](https://github.com/WSP-LAB/FUGIO-artifact#3-2-trigger-poi-vulnerabilities-in-the-second-terminal).
  
## Results
All outputs are generated in the `Files/fuzzing/[app_path.time]/PUT/` directory.
- `put-head.php` and `put-body.php`: a PUT file 
- `inst_PUT.php`: an instrumented PUT file for fuzzing the target application
- `procX_X_X_X_X_X.chain`: an identified POP chain
- `PROBABLY_EXPLOITABLE`: a directory for probably exploitable exploit objects (payloads)
- `EXPLOITABLE`: a directory for exploitable exploit objects (payloads)
