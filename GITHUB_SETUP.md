# GitHub Connection Setup Guide

## Current Status
- Git is configured with:
  - Username: `mchllbn`
  - Email: `michaella.bn3@gmail.com`
- Remote repository: `https://github.com/xGreeey/goldenz.git`
- Current branch: `backup`

## Option 1: Push to Existing Repository (if you have access)

### Step 1: Create a Personal Access Token (PAT)

1. Go to GitHub.com and sign in
2. Click your profile picture → **Settings**
3. Scroll down to **Developer settings** (left sidebar)
4. Click **Personal access tokens** → **Tokens (classic)**
5. Click **Generate new token** → **Generate new token (classic)**
6. Give it a name like "Goldenz Project"
7. Select scopes:
   - ✅ **repo** (full control of private repositories)
8. Click **Generate token**
9. **COPY THE TOKEN IMMEDIATELY** (you won't see it again!)

### Step 2: Push Your Changes

When you run `git push`, use:
- **Username**: `mchllbn` (or your GitHub username)
- **Password**: Paste your Personal Access Token (not your GitHub password)

```bash
git push origin backup
```

## Option 2: Push to Your Own Repository

### Step 1: Create a New Repository on GitHub

1. Go to GitHub.com
2. Click the **+** icon → **New repository**
3. Name it (e.g., `goldenz`)
4. Choose public or private
5. **DO NOT** initialize with README, .gitignore, or license
6. Click **Create repository**

### Step 2: Update Remote URL

Replace `YOUR_USERNAME` with your GitHub username:

```bash
git remote set-url origin https://github.com/YOUR_USERNAME/goldenz.git
```

### Step 3: Create Personal Access Token

Follow the same steps as Option 1, Step 1 above.

### Step 4: Push Your Code

```bash
git push -u origin backup
```

When prompted:
- **Username**: Your GitHub username
- **Password**: Your Personal Access Token

## Option 3: Use SSH (More Secure, Recommended for Long-term)

### Step 1: Generate SSH Key

```bash
ssh-keygen -t ed25519 -C "michaella.bn3@gmail.com"
```

Press Enter to accept default location. Optionally set a passphrase.

### Step 2: Add SSH Key to GitHub

1. Copy your public key:
   ```bash
   cat ~/.ssh/id_ed25519.pub
   ```
   (On Windows, it might be in `C:\Users\YourUsername\.ssh\id_ed25519.pub`)

2. Go to GitHub → Settings → SSH and GPG keys
3. Click **New SSH key**
4. Paste your public key
5. Click **Add SSH key**

### Step 3: Update Remote to Use SSH

```bash
git remote set-url origin git@github.com:YOUR_USERNAME/goldenz.git
```

### Step 4: Test Connection

```bash
ssh -T git@github.com
```

You should see: "Hi mchllbn! You've successfully authenticated..."

### Step 5: Push

```bash
git push origin backup
```

## Quick Commands Reference

```bash
# Check current remote
git remote -v

# Change remote URL (HTTPS)
git remote set-url origin https://github.com/YOUR_USERNAME/REPO_NAME.git

# Change remote URL (SSH)
git remote set-url origin git@github.com:YOUR_USERNAME/REPO_NAME.git

# Push to remote
git push origin backup

# Push and set upstream
git push -u origin backup
```

## Troubleshooting

- **Authentication failed**: Make sure you're using a Personal Access Token, not your password
- **Permission denied**: Check if you have access to the repository
- **Repository not found**: Verify the repository name and your username are correct
