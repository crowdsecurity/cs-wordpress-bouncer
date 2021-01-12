# Manually build zip package

This is how to manually build the CrowdSec plugin and zip it.

### Backup bouncer sources

```bash
mv ./vendor/crowdsec ../tmp_crowdsec_source
```

### Re-build prod deps

```bash
docker-compose exec $CONTAINER_NAME apt install unzip

rm -rf ./vendor crowdsec-wp.zip crowdsec-wp

docker-compose exec $CONTAINER_NAME composer install --no-dev --working-dir /var/www/html/wp-content/plugins/cs-wordpress-bouncer --prefer-dist --optimize-autoloader
```

# Build zip file

```bash
zip \
-r 'crowdsec-wp.zip' . \
-x '.cache*' \
-x '*.git*' \
-x '.github*' \
-x '.vagrant*' \
-x 'docker*' \
-x 'docs*' \
-x 'node_modules*' \
-x 'scripts*' \
-x 'tests*' \
-x '.editorconfig' \
-x '.env' \
-x '.env.example' \
-x '.gitignore' \
-x '.composer.json' \
-x '.composer.lock' \
-x 'docker-compose.yml' \
-x '*.sh' \
-x '*.log' \
-x '*.log' \
-x 'vendor/crowdsec/bouncer/vendor*' \
-x 'vendor/crowdsec/bouncer/tools*' \
-x 'vendor/crowdsec/bouncer/var*' \
-x 'vendor/crowdsec/bouncer/tests*' \
-x 'vendor/crowdsec/bouncer/scripts*' \
-x 'vendor/crowdsec/bouncer/examples*' \
-x 'vendor/crowdsec/bouncer/docs*' \
-x 'vendor/crowdsec/bouncer/docker*' \
-x 'vendor/crowdsec/bouncer/README.md' \
-x 'vendor/crowdsec/bouncer/composer.json' \
-x 'vendor/crowdsec/bouncer/composer.lock' \
-x 'vendor/crowdsec/bouncer/phpunit.xml' \
-x 'vendor/crowdsec/bouncer/.phpdoc-md' \
-x 'vendor/crowdsec/bouncer/phpstan.neon' \
-x 'vendor/crowdsec/bouncer/.github*' \
-x 'vendor/crowdsec/.bouncer-key' \
-x 'README.md' \
-x '**/.DS_Store' \
-x 'composer.json' \
-x 'composer.lock' \
-x 'logs/**' \
-x '**/*Test.php' \
-x 'Vagrantfile'
```

# Check zip package

```bash
unzip crowdsec-wp.zip -d crowdsec-wp

ncdu crowdsec-wp
```

# Restore bouncer sources 

```bash
rm -rf ./vendor

docker-compose exec $CONTAINER_NAME composer install --working-dir /var/www/html/wp-content/plugins/cs-wordpress-bouncer --prefer-source

rm -rf ./vendor/crowdsec

mv ../tmp_crowdsec_source ./vendor/crowdsec
```
