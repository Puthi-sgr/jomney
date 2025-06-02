### Removing something from git that were previously tracked

git rm --cached {fileName}

# Commit the removal

git add {fileName}
git commit -m "Remove something update something"

# Push the changes

git push

**List down tacking files**
git ls-files | grep {fileName}
