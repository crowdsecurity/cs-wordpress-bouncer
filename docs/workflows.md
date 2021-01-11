### New feature

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

### New release

```bash
git checkout main && git pull && git co -
git describe --tags `git rev-list --tags --max-count=1` # to verify what is the current tag
export NEW_GIT_VERSION_WITHOUT_V_PREFIX= #...X.X.X
./scripts/publish-release.sh
```