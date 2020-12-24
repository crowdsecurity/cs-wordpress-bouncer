FROM wordpress:5.0-php7.4

RUN a2disconf remoteip && a2dismod remoteip && service apache2 restart

RUN apt-get update && apt-get install -y git libmemcached-dev zlib1g-dev \
&& pecl install -o -f redis memcached \
&&  rm -rf /tmp/pear \
&&  docker-php-ext-enable redis memcached \
&& curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer