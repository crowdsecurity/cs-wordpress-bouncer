### New feature

```bash
git checkout -b <branch-name>
git commit # as much as necessary.

# If the bouncer version has been bumped
# Update <project>/composer.json with the last version then:
export CONTAINER_NAME=`echo "wordpress$WORDPRESS_VERSION" | tr . -`
docker-compose exec $CONTAINER_NAME composer update --working-dir /var/www/html/wp-content/plugins/cs-wordpress-bouncer --prefer-source

# Rename branch if necessary
git branch -m <new-name>
git push origin :<old-name> && git push -u origin <new-name>

# Create PR
gh pr create --fill
```

> Note: after the merge, don't forget to delete to branch.

### New release

```bash
git checkout main && git pull
git describe --tags `git rev-list --tags --max-count=1` # to verify what is the current tag
export NEW_GIT_VERSION_WITHOUT_V_PREFIX= #...X.X.X
./scripts/publish-release.sh
```
