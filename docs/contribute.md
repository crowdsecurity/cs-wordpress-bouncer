# Contribute to this plugin

## Install the stack for development purpose

To allow you use a local repository of the library and develop in the two repository at the same time, set the local path to the clone of the php library:
example :

```bash
export CS_PHPLIB_ABS_PATH=/path/to/the/local/clone/of/the/php/cs/lib
```
## Select the PHP version you want to use (7.2, 7.3, 7.4, 8.0)

```bash
export CS_WORDPRESS_BOUNCER_PHP_VERSION=7.2
```

Run containers:

```bash
docker-compose up -d
```

Visit the wordpress instance here: http://localhost

Admin account: admin / ThisSecretIsKnown!

# Init deps

cd cs-wordpress-blocker
docker-compose exec sh composer install

rm -rf vendor/crowdsec/bouncer
cd vendor/crowdsec
# "absolute" is required for usage in Docker containers
ln -s ${CS_PHPLIB_ABS_PATH}  bouncer
cd -

# Install plugin and configure it

To get a bouncer key:

```bash
docker-compose exec crowdsec cscli bouncers add wordpress-bouncer
```

The LAPI URL is:

http://crowdsec:8080

docker-compose run crowdsec

# Play with crowdsec container

```bash
# Get the Docker host IP from inside the crowdsec container
export CS_WP_HOST=`docker-compose exec crowdsec /sbin/ip route|awk '/default/ { printf $3 }'`

# Add captcha your own IP for 15m:
docker-compose exec crowdsec cscli decisions add --ip ${CS_WP_HOST} --duration 15m --type captcha

# Ban your own IP for 15 sec:
docker-compose exec crowdsec cscli decisions add --ip ${CS_WP_HOST} --duration 15s --type ban


# Remove all decisions:
docker-compose exec crowdsec cscli decisions delete --all

# View CrowdSec logs:
docker-compose logs crowdsec

```

### Docker-compose cheets sheet

```bash
docker-compose run web sh # run sh on wordpress container
docker-compose ps # list running containers
docker-compose stop # stop
docker-compose rm # destroy
```

### Use another PHP version

```bash
docker-compose down
docker images | grep wordpress-bouncer_web # to get the container id
docker rmi 145c1ed0e4df
CS_WORDPRESS_BOUNCER_PHP_VERSION=7.2 docker-compose up -d --build --force-recreate
```