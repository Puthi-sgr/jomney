# Git Workflow Guide

## Removing Tracked Files from Git

```bash
git rm --cached {fileName}
git add {fileName}
git commit -m "Remove something update something"
```

## Creating and Working with Feature Branches

### Create and Switch to New Branch
```bash
git checkout -b your-feature-branch
```

This creates a new branch called 'your-feature-branch' and switches to it. Now you can make your changes and commit them as usual.

### Push Branch to Remote
```bash
git push -u origin your-feature-branch
```

The `-u` flag sets the upstream so future `git push` and `git pull` commands will default to this branch.

### Safe Branch Creation (Recommended)
```bash
# Ensure main is up to date first
git checkout main
git pull origin main

# Create and switch to feature branch
git checkout -b feature/database-connection-pooling
git push -u origin feature/database-connection-pooling
```

## Development Workflow

### Regular Commits During Development
```bash
git add -A
git commit -m "Add DatabasePool class with connection pooling"
git push origin feature/database-connection-pooling

# Continue development...
git add app/Models/Customer.php
git commit -m "Migrate Customer model to use pooled connections"
git push origin feature/database-connection-pooling
```

## Safe Merge Process

### Step 1: Sync with Latest Main
```bash
git checkout main
git pull origin main
git checkout feature/database-connection-pooling
git rebase main  # Resolve any conflicts here
```

### Step 2: Test Everything Works
Run your application and test key features

### Step 3: Merge with Safety
```bash
git checkout main
git merge --no-ff feature/database-connection-pooling
```

### Step 4: Test Merged Code Before Pushing
Test again to ensure merge didn't break anything

### Step 5: Push to Remote
```bash
git push origin main
```

## Emergency Rollback (if needed)

### Find the Merge Commit
```bash
git log --oneline -10
```

### Revert the Merge
```bash
git revert -m 1 <merge-commit-hash>
git push origin main
```

## Clean Up

### Delete Branch (only after confirming success)
```bash
git branch -d feature/database-connection-pooling
git push origin --delete feature/database-connection-pooling
```

## Additional Commands

### List Tracking Files
```bash
git ls-files | grep {fileName}
```

### Push Changes to Main
```bash
git push origin main
```

---

**Tip:** Always make sure your main branch is up to date before branching off:
```bash
git checkout main
git pull origin main
git checkout -b your-feature-branch
```


