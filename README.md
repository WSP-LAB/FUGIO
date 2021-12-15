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
  - PHP 5.4
    ```
    sudo apt-get install -y 
    ```
  - PHP 5.6
    ```
    sudo apt-get install -y 
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

