# Stefa Backend Installation Guide

This guide provides step-by-step instructions for setting up the Stefa PHP backend environment.

---

## Table of Contents
1. [System Requirements](#system-requirements)
2. [PHP Installation](#php-installation)
3. [Composer Installation](#composer-installation)
4. [RabbitMQ and Erlang](#rabbitmq-and-erlang)
5. [Image Processing Libraries](#image-processing-libraries)
6. [Nginx with Brotli](#nginx-with-brotli)
7. [PostgreSQL Installation](#postgresql-installation)
8. [Permissions for Project Directories](#permissions-for-project-directories)
9. [Machine Learning Environment Setup](#machine-learning-environment-setup)
10. [Metabase Setup](#metabase-setup)
11. [Headless Chromium Setup](#headless-chromium-setup)
12. [Project Setup](#project-setup)
---

## System Requirements
- PHP 8.2
- PostgreSQL
- RabbitMQ
- Nginx
- Python3
- Headless chrome
- Metabase
---

## PHP Installation
```bash
sudo add-apt-repository ppa:ondrej/php -y
apt update && apt -y upgrade;
sudo apt install -y \
acl \
unzip \
php8.2-zip \
php8.2-pdo \
php8.2-mysql \
php8.2-igbinary \
php8.2-redis \
php8.2-apcu \
php8.2-fpm \
php8.2-dom \
php8.2-xsl \
php8.2-xml \
php8.2-intl \
php8.2-opcache \
php8.2-imagick \
php8.2-dev \
php8.2-curl \
php8.2-ds \
php8.2-mbstring \
php8.2-bcmath \
php8.2-gd \
php8.2-pgsql \
php8.2-amqp \
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

## Image Processing Libraries
### Installing libraries
```bash
apt install -y \
libjpeg-dev \
libpng-dev \
libtiff-dev \
libgif-dev;
```

### Adding repositories
```bash
sudo cat <<'END' >> /etc/apt/sources.list && apt update;
deb http://mirror.yandex.ru/ubuntu/ noble main
deb-src http://mirror.yandex.ru/ubuntu/ noble main
END
```

### WebP
```bash
WEBP_VERSION=1.4.0;
cd /tmp && wget https://storage.googleapis.com/downloads.webmproject.org/releases/webp/libwebp-$WEBP_VERSION.tar.gz ;
tar xvzf libwebp-$WEBP_VERSION.tar.gz;
cd libwebp-$WEBP_VERSION;
./configure;
make;
make install;
echo "export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/usr/local/lib" >> ~/.zshrc;
```

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

sudo ln -s /etc/php/8.2/mods-available/imagick.ini /etc/php/8.2/fpm/conf.d/20-imagick.ini
sudo ln -s /etc/php/8.2/mods-available/imagick.ini /etc/php/8.2/cli/conf.d/20-imagick.ini
```

### Mozjpeg
```bash
apt -y install cmake autoconf automake libtool nasm make pkg-config libpng-dev pngquant;
cd /tmp && git clone https://github.com/mozilla/mozjpeg.git ;
cd /tmp/mozjpeg;
mkdir build && cd build;
cmake -G"Unix Makefiles" ../;
make && make install;
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

## Permissions for Project Directories
```bash
HTTPDUSER=$(ps axo user,comm | grep -E '[n]ginx|[w]ww-data' | head -1 | cut -d\  -f1)

setfacl -dR -m u:"$HTTPDUSER":rwX -m u:app:rwX /home/app/amananet/backend/var \
/home/app/amananet/backend/public/media /home/app/amananet/backend/public/uploads

setfacl -R -m u:"$HTTPDUSER":rwX -m u:app:rwX /home/app/amananet/backend/var \
/home/app/amananet/backend/public/media /home/app/amananet/backend/public/uploads
```

## Machine Learning Environment Setup
### Install Python and pip
```bash
sudo apt update
sudo apt install -y python3 python3-pip python3-venv
```
### Create and activate a virtual environment
```bash
cd /home/app/stefa/backend
python3 -m venv venv
source venv/bin/activate
```
### Install Python dependencies
```bash
pip install --upgrade pip
pip install face_recognition scikit-learn numpy pillow imagehash
```
### Install additional libraries
```bash
sudo apt install -y build-essential cmake libopenblas-dev liblapack-dev libx11-dev libgtk-3-dev libboost-all-dev
```
### Scripts overview
`hashes.py` Calculates image perceptual hashes (aHash, pHash, dHash, wHash) using Pillow and ImageHash

`recognize.py` Extracts facial encodings from an image

`predict.py` Predicts the most likely match for a face encoding using a trained KNN model

`train.py` Trains a KNN classifier on preprocessed face encodings and saves the model to disk

## Metabase setup
Install Java
```bash
sudo apt update
sudo apt install -y openjdk-17-jre
```
Create a directory for Metabase
```bash
sudo mkdir -p /opt/metabase
sudo useradd -r -s /bin/false metabase
sudo chown metabase:metabase /opt/metabase
```

### Download and configure Metabase
```bash
sudo wget https://downloads.metabase.com/v0.50.5/metabase.jar -O /opt/metabase/metabase.jar
sudo chown metabase:metabase /opt/metabase/metabase.jar
```

Create a systemd service
```bash
sudo nano /etc/systemd/system/metabase.service
```
Paste the following content:
```bash
[Unit]
Description=Metabase Analytics
After=network.target

[Service]
User=metabase
ExecStart=/usr/bin/java -jar /opt/metabase/metabase.jar
Environment="MB_DB_FILE=/opt/metabase/metabase.db"
Restart=always

[Install]
WantedBy=multi-user.target
```
Then start and enable the service:
```bash
Then start and enable the service:
sudo systemctl daemon-reload
sudo systemctl enable metabase
sudo systemctl start metabase
```
Access Metabase
Metabase runs on port 3000 by default.
```bash
http://your-server-ip:3000
```

## Headless Chromium Setup
The backend uses Headless Chrome for web scraping and parsing tasks (triggered by Symfony commands like persons:parse:daily).
### Install Chromium and dependencies
```bash
sudo apt update
sudo apt install -y chromium-browser chromium-driver fonts-liberation
sudo apt install -y libx11-dev libnss3 libxss1 libappindicator3-1 libatk-bridge2.0-0 libgtk-3-0
```
Verify installation
```bash
chromium-browser --version
chromedriver --version
```
### Configure environment variables 
```bash
CHROME_BIN=/usr/bin/chromium-browser
CHROMEDRIVER_PATH=/usr/bin/chromedriver
PARSING_SITE_URL=https://example.com
```
## Project Setup

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

