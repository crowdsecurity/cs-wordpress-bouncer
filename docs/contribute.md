# Contribute to this plugin

## Install the stack for development purpose

Run containers:

```bash
docker-compose up -d
```

Visit the wordpress instance here: http://localhost

Admin account: admin / ThisSecretIsKnown!

# Init deps

cd cs-wordpress-blocker
composer install

rm -rf vendor/crowdsec/bouncer
cd vendor/crowdsec
# "absolute" is required for usage in Docker containers
ln -s <absolute_path_to_lib_source>  bouncer
cd -

# Install plugin and configure it

To get a bouncer key:

docker-compose exec crowdsec cscli bouncers add wordpress-bouncer

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
