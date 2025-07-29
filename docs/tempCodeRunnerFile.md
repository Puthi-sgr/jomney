### Removing something from git that were previously tracked

git rm --cached {fileName}

# Commit the removal

git add {fileName}
git commit -m "Remove something update something"

# Push the changes

git push origin main

**List down tacking files**
git ls-files | grep {fileName}

### Create and switch to new branch (SAFE VERSION)
```bash
# Ensure main is up to date first
git checkout main
git pull origin main

# Create and switch to feature branch
git checkout -b feature/database-connection-pooling
git push -u origin feature/database-connection-pooling
```

### Development workflow with regular backups
```bash
# Regular commits during development
git add -A
git commit -m "Add DatabasePool class with connection pooling"
git push origin feature/database-connection-pooling

# Continue development...
git add app/Models/Customer.php
git commit -m "Migrate Customer model to use pooled connections"
git push origin feature/database-connection-pooling
```

### SAFE merge process
```bash
# Step 1: Sync with latest main
git checkout main
git pull origin main
git checkout feature/database-connection-pooling
git rebase main  # Resolve any conflicts here

# Step 2: Test everything works
# Run your application and test key features

# Step 3: Merge with safety
git checkout main
git merge --no-ff feature/database-connection-pooling

# Step 4: Test merged code before pushing
# Test again to ensure merge didn't break anything

# Step 5: Push to remote
git push origin main
```

### Emergency rollback (if needed)
```bash
# Find the merge commit
git log --oneline -10

# Revert the merge (use -m 1 for merge commits)
git revert -m 1 <merge-commit-hash>
git push origin main
```

### Delete branch (only after confirming success)
```bash
git branch -d feature/database-connection-pooling
git push origin --delete feature/database-connection-pooling
```