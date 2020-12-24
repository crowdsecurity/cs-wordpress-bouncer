# Contribute to this plugin

## Install the stack for development purpose

Select the PHP version you want to use (7.2, 7.3, 7.4, 8.0) :

```bash
export CS_WORDPRESS_BOUNCER_PHP_VERSION=7.2
```

Run containers:

```bash
docker-compose up -d wordpress crowdsec mysql redis memcached
```

Visit the wordpress instance here: http://localhost and install the wordpress instance.

# Init deps for dev environment

```bash
docker-compose exec wordpress composer install --working-dir /var/www/html/wp-content/plugins/cs-wordpress-bouncer --prefer-source
```

> In this dev environment, we use `--prefer-source` to be able to develop the bouncer library at the same time. Composer will may ask you for your own Github token to download sources instead of using dist packages.

# Install plugin and configure it

To get a bouncer key:

```bash
docker-compose exec crowdsec cscli bouncers add wordpress-bouncer
```

The LAPI URL is:

http://crowdsec:8080

# Play with crowdsec state

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

## Run functionnal tests

Headless mode (speed up):

```bash
./tests-local.sh
```

Debug mode (add tests):

```bash
DEBUG ./tests-local.sh
```

> Note: you can add `await jestPlaywright.debug()` at the moment you want to pause the process.

All the versions:

```bash
./tests-local-wpall.sh
```

> Note: If you have some problems while running tests, `docker system prune --volumes` can help.

# WP Scan pass

```bash
docker-compose run --rm wpscan --url http://wordpress/
```

### Quick `docker-compose` cheet sheet

```bash
docker-compose run wordpress sh # run sh on wordpress container
docker-compose ps # list running containers
docker-compose stop # stop
docker-compose rm # destroy
```

### Try the plugin with another PHP version

```bash
docker-compose down
docker images | grep wordpress-bouncer_wordpress # to get the container id
docker rmi 145c1ed0e4df
CS_WORDPRESS_BOUNCER_PHP_VERSION=7.2 docker-compose up -d --build --force-recreate
```

### Use another Worpress version

In end 2020, [more than 90% of the wordpress websites](https://wordpress.org/about/stats/) was using WordPress versions:

The plugin is tested under each of these versions: `5.6`, `5.5`, `5.4`, `5.3`, `5.2`, `5.1`, `5.0`, `4.9`.

#### Add support for a new WordPress version

This is a sheet cheet to help testing briefly the support:

```bash

# To install a specific version
docker-compose up -d wordpress<X.X> crowdsec mysql redis memcached && docker-compose exec crowdsec cscli bouncers add wordpress-bouncer

# To display the captcha wall

export CS_WP_HOST=`docker-compose exec crowdsec /sbin/ip route|awk '/default/ { printf $3 }'` && docker-compose exec crowdsec cscli decisions add --ip ${CS_WP_HOST} --duration 15m --type captcha

# To delete the image in order to rebuild it

docker-compose down && docker rmi wordpress-bouncer_wordpress<X.X>

# To debug inside the container

docker-compose run wordpress<X.X> bash
```

### Display the plugin logs

```bash
tail -f logs/*
```

#### New feature workflow

```bash
git checkout -b <branch-name>
git commit # as much as necessary.

# Rename branch if necessary
git branch -m <new-name>
git push origin :<old-name> && git push -u origin <new-name>

# Create PR
gh pr create --fill
```

> Note: after the merge, don't forget to delete to branch.

#### New release workflow

```bash
git checkout main && git pull && git co -
git describe --tags `git rev-list --tags --max-count=1` # to verify what is the current tag
export NEW_GIT_VERSION_WITHOUT_V_PREFIX= #...X.X.X
./scripts/publish-release.sh
```