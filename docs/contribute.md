# Contribute to this plugin

## Install the stack for development purpose
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

# Init deps for dev environment

In `composer.json`, replace `"crowdsec/bouncer": "^..."` with `"crowdsec/bouncer": "dev-<a-dev-branch>"`.

> Important: Don't forget to replace this value by the new lib release tag when finishing the feature).

```bash
docker-compose exec web composer install --working-dir /var/www/html/wp-content/plugins/cs-wordpress-bouncer --prefer-source
```

> In this dev environment, we use `--prefer-source` to be able to develop the bouncer library at the same time. Composer will may ask you for your own Github token to download sources instead of using dist packages.

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

# WP Scan pass

```bash
docker-compose run --rm wpscan --url http://web/
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

#### New feature

```bash
git checkout -b <branch-name>
git commit # as much as necessary.

# Rename branch if necessary
git branch -m <new-name>
git push origin :<old-name> && git push origin <new-name>

# Create PR
gh pr create --fill
```

> Note: after the merge, don't forget to delete to branch.

#### New release

```bash
git checkout main && git pull && git co -
git describe --tags `git rev-list --tags --max-count=1` # to verify what is the current tag
export NEW_GIT_VERSION_WITHOUT_V_PREFIX= #...X.X.X
./scripts/publish-release.sh
```