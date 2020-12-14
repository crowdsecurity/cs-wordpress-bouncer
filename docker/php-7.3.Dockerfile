FROM wordpress:5.6-php7.3

RUN apt-get update && apt-get install -y libmemcached-dev zlib1g-dev \
&& pecl install -o -f redis memcached \
&&  rm -rf /tmp/pear \
&&  docker-php-ext-enable redis memcached \
&& curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer