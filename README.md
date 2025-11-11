# Amananet Backend Installation Guide

This guide provides step-by-step instructions for setting up the Amananet PHP backend environment.

---

## Table of Contents
1. [System Requirements](#system-requirements)
2. [PHP Installation](#php-installation)
3. [Composer Installation](#composer-installation)
4. [RabbitMQ and Erlang](#rabbitmq-and-erlang)
5. [PostgreSQL Installation](#postgresql-installation)
6. [Nginx with Brotli](#nginx-with-brotli)
7. [Elasticsearch](#elasticsearch)
8. [Image Processing Libraries](#image-processing-libraries)
9. [System Libraries for PDF/HTML Generation](#system-libraries-for-pdfhtml-generation)
10. [Project Setup](#project-setup)
11. [Symfony Commands](#symfony-commands)
---

## System Requirements
- Ubuntu 22.04+ (or compatible Debian-based system)
- PHP 8.4
- PostgreSQL
- RabbitMQ
- Nginx
- Elasticsearch
- Required PHP extensions and libraries (listed below)
---

## PHP Installation

```bash
sudo add-apt-repository ppa:ondrej/php -y
apt update && apt -y upgrade;
sudo apt install -y \
acl \
unzip \
php8.4-zip \
php8.4-pdo \
php8.4-mysql \
php8.4-igbinary \
php8.4-redis \
php8.4-apcu \
php8.4-fpm \
php8.4-dom \
php8.4-xsl \
php8.4-xml \
php8.4-intl \
php8.4-opcache \
php8.4-imagick \
php8.4-dev \
php8.4-curl \
php8.4-ds \
php8.4-mbstring \
php8.4-bcmath \
php8.4-gd \
php8.4-pgsql \
php8.4-amqp \
redis-server;
```

## Composer Installation

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '<SHA384_HASH>') { echo 'Installer verified'.PHP_EOL; } else { echo 'Installer corrupt'.PHP_EOL; unlink('composer-setup.php'); exit(1); }"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
```

## RabbitMQ and Erlang

### Add GPG key
```bash
curl -1sLf "https://keys.openpgp.org/vks/v1/by-fingerprint/0A9AF2115F4687BD29803A206B73A36E6026DFCA" | sudo gpg --dearmor | sudo tee /usr/share/keyrings/com.rabbitmq.team.gpg > /dev/null
```
### Add repository
```bash
sudo tee /etc/apt/sources.list.d/rabbitmq.list <<EOF
deb [arch=amd64 signed-by=/usr/share/keyrings/com.rabbitmq.team.gpg] https://deb1.rabbitmq.com/rabbitmq-erlang/ubuntu/ noble main
deb [arch=amd64 signed-by=/usr/share/keyrings/com.rabbitmq.team.gpg] https://deb2.rabbitmq.com/rabbitmq-erlang/ubuntu/ noble main
deb [arch=amd64 signed-by=/usr/share/keyrings/com.rabbitmq.team.gpg] https://deb1.rabbitmq.com/rabbitmq-server/ubuntu/ noble main
deb [arch=amd64 signed-by=/usr/share/keyrings/com.rabbitmq.team.gpg] https://deb2.rabbitmq.com/rabbitmq-server/ubuntu/ noble main
EOF

sudo apt update
sudo apt install -y erlang-base erlang-asn1 erlang-crypto erlang-eldap erlang-ftp erlang-inets \
erlang-mnesia erlang-os-mon erlang-parsetools erlang-public-key erlang-runtime-tools erlang-snmp \
erlang-ssl erlang-syntax-tools erlang-tftp erlang-tools erlang-xmerl

sudo apt install -y rabbitmq-server --fix-missing
sudo ufw allow 5672,15672,4369,25672/tcp

# RabbitMQ users setup
rabbitmqctl add_user app "<PASSWORD>"
rabbitmqctl set_permissions -p / app ".*" ".*" ".*"

rabbitmqctl add_user system "<PASSWORD>"
rabbitmqctl set_user_tags system administrator
rabbitmqctl set_permissions -p / system ".*" ".*" ".*"

systemctl status rabbitmq-server.service
```

## PostgreSQL Installation
```bash
sudo apt install -y wget ca-certificates
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo apt-key add -
echo "deb http://apt.postgresql.org/pub/repos/apt/ $(lsb_release -cs)-pgdg main" | sudo tee /etc/apt/sources.list.d/pgdg.list
sudo apt update
sudo apt install -y postgresql postgresql-contrib

su - postgres;
psql;
create role app with login password '<password>';
alter role app createdb;
CREATE DATABASE "app" WITH OWNER "app" ENCODING 'UTF8' LC_COLLATE = 'en_US.UTF-8' LC_CTYPE = 'en_US.UTF-8' TEMPLATE template0;
```

## Nginx with Brotli
```bash
sudo apt install -y golang libpcre3-dev make gcc zlib1g-dev libbrotli-dev
NGINX_VERSION=1.26.3
BROTLI_DIR=/tmp/brotli

git clone --recursive https://github.com/google/ngx_brotli.git $BROTLI_DIR
wget http://nginx.org/download/nginx-$NGINX_VERSION.tar.gz
tar -zxvf nginx-$NGINX_VERSION.tar.gz
cd nginx-$NGINX_VERSION

./configure \
--sbin-path=/usr/sbin/nginx \
--with-http_ssl_module \
--with-http_v2_module \
--add-module=$BROTLI_DIR \
--with-compat \
--with-http_stub_status_module \
--with-http_realip_module \
--with-http_auth_request_module \
--with-threads \
--with-http_gunzip_module \
--with-http_gzip_static_module

make && sudo make install
sudo systemctl restart nginx.service
nginx -t
```

## Permissions for Project Directories
```bash
HTTPDUSER=$(ps axo user,comm | grep -E '[n]ginx|[w]ww-data' | head -1 | cut -d\  -f1)

setfacl -dR -m u:"$HTTPDUSER":rwX -m u:app:rwX /home/app/amananet/backend/var \
/home/app/amananet/backend/public/media /home/app/amananet/backend/public/uploads

setfacl -R -m u:"$HTTPDUSER":rwX -m u:app:rwX /home/app/amananet/backend/var \
/home/app/amananet/backend/public/media /home/app/amananet/backend/public/uploads
```

## Elasticsearch
```bash
curl -fsSL https://artifacts.elastic.co/GPG-KEY-elasticsearch | sudo gpg --dearmor -o /usr/share/keyrings/elastic.gpg
echo "deb [signed-by=/usr/share/keyrings/elastic.gpg] https://artifacts.elastic.co/packages/8.x/apt stable main" | sudo tee /etc/apt/sources.list.d/elastic-8.x.list
sudo apt update
sudo apt install -y elasticsearch
sudo systemctl enable elasticsearch
sudo systemctl start elasticsearch
sudo ufw allow 9200
```
## Image Processing Libraries

### ImageMagick
```bash
IMAGE_MAGICK_VERSION=7.1.2-1
cd /tmp
wget https://imagemagick.org/download/ImageMagick-$IMAGE_MAGICK_VERSION.tar.gz
tar xvzf ImageMagick-$IMAGE_MAGICK_VERSION.tar.gz
cd ImageMagick-$IMAGE_MAGICK_VERSION
sudo apt-get build-dep imagemagick -y
./configure --with-png --with-jpeg --with-zlib --with-webp
make
sudo make install
sudo ldconfig /usr/local/lib
```

### PHP Imagick Extension
```bash
PHP_IMAGICK_VERSION=3.8.0
cd /tmp
wget https://pecl.php.net/get/imagick-$PHP_IMAGICK_VERSION.tgz
tar zxvf imagick-$PHP_IMAGICK_VERSION.tgz
cd imagick-$PHP_IMAGICK_VERSION
phpize
./configure
make
sudo make install

sudo ln -s /etc/php/8.4/mods-available/imagick.ini /etc/php/8.4/fpm/conf.d/20-imagick.ini
sudo ln -s /etc/php/8.4/mods-available/imagick.ini /etc/php/8.4/cli/conf.d/20-imagick.ini
sudo systemctl restart php8.4-fpm.service
```

## System Libraries for PDF/HTML and Headless Chrome
```bash
sudo apt install -y \
gconf-service libasound2 libatk1.0-0 libatk-bridge2.0-0 libc6 libcairo2 libcups2 \
libdbus-1-3 libexpat1 libfontconfig1 libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 \
libglib2.0-0 libgtk-3-0 libnspr4 libpango-1.0-0 libx11-6 libx11-xcb1 libxcb1 \
libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 \
libxrender1 libxss1 libxtst6 libgbm1 ca-certificates fonts-liberation lsb-release xdg-utils wget
```

## Project Setup

### Create project directories
```bash
mkdir -p ~/amananet/backend ~/amananet/frontend
cd ~/amananet/backend
```
### Git setup
```bash
git init
git remote add origin git@github.com:aliensource-org/amananet-backend.git
git fetch origin
git checkout -b main --track origin/main
```
### Create necessary directories
`mkdir -p public/uploads public/media var`
### Install dependencies, run migrations, and clear cache
`make install`

## Symfony Commands

### Create an admin user
`bin/console user:create:admin -u admin@amananet.com -p "password123"`

### Generate sitemaps
`bin/console sitemap:generate`
