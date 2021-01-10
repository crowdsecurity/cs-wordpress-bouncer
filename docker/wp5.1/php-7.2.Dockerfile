FROM wordpress:5.1-php7.2

RUN usermod -u 1000 www-data

RUN apt-get update && apt-get install -y git libmemcached-dev zlib1g-dev \
&& pecl install -o -f redis memcached \
&&  rm -rf /tmp/pear \
&&  docker-php-ext-enable redis memcached \
&& curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN apt-get install -y \
    libfreetype6-dev \
    libmcrypt-dev \
    libjpeg-dev \
    libpng-dev \
    && docker-php-ext-configure gd \
    --with-freetype-dir=/usr/include/freetype2 \
    --with-png-dir=/usr/include \
    --with-jpeg-dir=/usr/include \
    && docker-php-ext-install gd
