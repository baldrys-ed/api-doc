# Umalat Installation Guide

This guide provides step-by-step instructions for setting up the Umalat.

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
9. [Telegram Bot Setup](#telegram-bot-setup)
10. [Project Setup](#project-setup)
---

## System Requirements
- PHP 8.3
- PostgreSQL
- RabbitMQ
- Nginx
---

## PHP Installation
```bash
sudo add-apt-repository ppa:ondrej/php -y
apt update && apt -y upgrade;
sudo apt install -y \
acl \
unzip \
php8.3-zip \
php8.3-pdo \
php8.3-mysql \
php8.3-igbinary \
php8.3-redis \
php8.3-apcu \
php8.3-fpm \
php8.3-dom \
php8.3-xsl \
php8.3-xml \
php8.3-intl \
php8.3-opcache \
php8.3-imagick \
php8.3-dev \
php8.3-curl \
php8.3-ds \
php8.3-mbstring \
php8.3-bcmath \
php8.3-gd \
php8.3-pgsql \
php8.3-amqp \
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

sudo ln -s /etc/php/8.3/mods-available/imagick.ini /etc/php/8.3/fpm/conf.d/20-imagick.ini
sudo ln -s /etc/php/8.3/mods-available/imagick.ini /etc/php/8.3/cli/conf.d/20-imagick.ini
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

setfacl -dR -m u:"$HTTPDUSER":rwX -m u:app:rwX /home/app/umalat/var \
/home/app/umalat/public/media /home/app/umalat/public/uploads

setfacl -R -m u:"$HTTPDUSER":rwX -m u:app:rwX /home/app/umalat/var \
/home/app/umalat/public/media /home/app/umalat/public/uploads
```

## Telegram bot Setup
1. Create a bot using BotFather
2. Get the API token for your bot.
3. Set up a webhook with the following URL: `https://<your-domain>/api/telegram/webhook`
For local development, you can use ngrok `ngrok http 8000`
4. Add the following environment variables to your .env file:
```bash
TELEGRAM_BOT_TOKEN=<bot_token>
TELEGRAM_BOT_URL=<bot_url>
ALLOWED_TELEGRAMS=[<array_of_allowed_telegrams>]
```

### Install Chromium and dependencies
```bash
sudo apt update
sudo apt install -y chromium-browser chromium-driver fonts-liberation
sudo apt install -y libx11-dev libnss3 libxss1 libappindicator3-1 libatk-bridge2.0-0 libgtk-3-0
```

## Project Setup

### Git setup
```bash
git init
git remote add origin git@github.com:aliensource-org/umalat-bot.git
git fetch origin
git checkout -b main --track origin/main
```

### Create necessary directories
```bash
mkdir -p public/uploads \
         public/media/persons \
         var/model/face/train
```

### Install dependencies, run migrations, and clear cache
`make install`

### Importing Locations
To load locations from a CSV file (var/data/import.csv), run the following command: `bin/console journey:import -f ./var/data/import.csv`
