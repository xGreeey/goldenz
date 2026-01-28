# Why Rclone Needs Re-Authentication

## Understanding the Problem

Rclone uses OAuth 2.0 to authenticate with Google Drive. There are **two types of tokens**:

1. **Access Token** - Expires after **1 hour**, used for API calls
2. **Refresh Token** - Should last indefinitely, used to get new access tokens

## Why Re-Authentication Happens

### Root Cause: OAuth App in "Testing" Mode

The most common reason is that your **Google OAuth app is in "Testing" mode** instead of "Production" mode.

**Testing Mode Limitations:**
- Refresh tokens expire after **7 days** (1 week)
- After 7 days, the refresh token stops working
- You must manually re-authenticate every week

**Production Mode:**
- Refresh tokens don't expire (unless revoked)
- Access tokens auto-refresh using the refresh token
- No manual re-authentication needed

### How Token Refresh Works

```
┌─────────────────────────────────────────────────┐
│ Normal Flow (Production Mode)                   │
├─────────────────────────────────────────────────┤
│ 1. Access token expires (after 1 hour)          │
│ 2. Rclone automatically uses refresh token     │
│ 3. Gets new access token                        │
│ 4. Saves updated token to config file          │
│ 5. Continues working indefinitely               │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ Problem Flow (Testing Mode)                     │
├─────────────────────────────────────────────────┤
│ 1. Access token expires (after 1 hour)          │
│ 2. Rclone tries to use refresh token             │
│ 3. Refresh token expired (after 7 days) ❌      │
│ 4. Error: "unauthorized_client"                 │
│ 5. Manual re-authentication required            │
└─────────────────────────────────────────────────┘
```

## Your Current Configuration

Looking at your rclone config, you have:
- ✅ Client ID and Secret configured
- ✅ Refresh token present
- ⚠️ **If already in Production mode, check other causes below**

## Other Causes (If Already in Production Mode)

If your OAuth app is **already in Production mode** but tokens still expire, check these:

### 1. Config File Permissions (Most Likely Issue)

**Problem:** Rclone can't write refreshed tokens to the config file.

**Check:**
```powershell
docker exec hr_web ls -la /var/www/.config/rclone/rclone.conf
```

**If you see:** `-rw------- root www-data` (only root can write)

**Fix:** Make the file writable by www-data (the user running PHP):
```powershell
docker exec hr_web chown www-data:www-data /var/www/.config/rclone/rclone.conf
docker exec hr_web chmod 640 /var/www/.config/rclone/rclone.conf
```

**Why this matters:** When the access token expires (after 1 hour), rclone automatically uses the refresh token to get a new access token. But if it can't **write** the new token back to the config file, the refresh fails silently, and you'll need to re-authenticate.

### 2. Invalid or Revoked Client ID/Secret

**Problem:** The client_id and client_secret in your config don't match your Google Cloud Console OAuth app, or they've been revoked.

**Check:**
1. Go to: https://console.cloud.google.com/apis/credentials
2. Find your OAuth 2.0 Client ID: `526823886846-lchg91rm3s1p3a4qsp59lt13ps4r0vro`
3. Verify:
   - Client ID matches exactly
   - Client Secret matches exactly
   - The OAuth app is enabled (not deleted/disabled)

**Fix:** If they don't match:
1. Get the correct client_id and client_secret from Google Cloud Console
2. Update rclone config:
   ```powershell
   docker exec -it hr_web rclone config
   # Select: e) Edit existing remote
   # Select: rclone
   # Update client_id and client_secret
   ```

### 3. OAuth App Needs Re-verification

**Problem:** Google requires periodic re-verification of OAuth apps, especially if:
- The app hasn't been verified
- The app's scopes changed
- Security policies changed

**Check:**
1. Go to: https://console.cloud.google.com/apis/credentials/consent
2. Look for warnings or "Verification required" messages

**Fix:** Complete the verification process or mark as "Internal" if using Google Workspace.

### 4. Refresh Token Was Revoked

**Problem:** The refresh token in your config was revoked (manually or by Google).

**Common causes:**
- User revoked access in Google Account settings
- Too many failed refresh attempts
- Security policy change

**Fix:** Re-authenticate to get a new refresh token:
```powershell
docker exec -it hr_web rclone config reconnect rclone:
```

### 5. Client ID/Secret Type Mismatch

**Problem:** Using a "Desktop app" client ID with a client secret, or vice versa.

**Check:** In Google Cloud Console, check the "Application type" of your OAuth client:
- **Web application** = Should have both client_id and client_secret ✅
- **Desktop app** = May not work with client_secret ❌

**Fix:** Create a new OAuth client with type "Web application" if needed.

## Solutions

### Solution 1: Fix Config File Permissions (If This Is The Issue)

This is the **most common issue** when already in Production mode:

```powershell
# Fix ownership and permissions
docker exec hr_web chown www-data:www-data /var/www/.config/rclone/rclone.conf
docker exec hr_web chmod 640 /var/www/.config/rclone/rclone.conf

# Also fix the directory
docker exec hr_web chown -R www-data:www-data /var/www/.config/rclone/
docker exec hr_web chmod 755 /var/www/.config/rclone/

# Test if rclone can now refresh tokens
docker exec hr_web rclone --config /var/www/.config/rclone/rclone.conf lsd rclone:
```

### Solution 2: Move OAuth App to Production (If Not Already)

This is the **permanent fix** that prevents token expiration:

1. **Go to Google Cloud Console:**
   - Visit: https://console.cloud.google.com/
   - Select your project (or create one)

2. **Navigate to OAuth Consent Screen:**
   - APIs & Services → OAuth consent screen

3. **Change Publishing Status:**
   - If status is "Testing", click **"PUBLISH APP"**
   - This moves it to "Production"

4. **If Verification Required:**
   - For personal use: Mark as "Internal" (if using Google Workspace)
   - For public use: Complete verification process
   - For testing: Add your email as a test user

5. **Re-authenticate rclone:**
   ```powershell
   docker exec -it hr_web rclone config reconnect rclone:
   ```

**After this, tokens should work indefinitely!**

### Solution 2: Add Test Users (Quick Fix)

If you can't publish to production yet:

1. **Go to OAuth Consent Screen**
2. **Add Test Users:**
   - Click "ADD USERS"
   - Add your Google account email
   - Save

3. **Re-authenticate:**
   ```powershell
   docker exec -it hr_web rclone config reconnect rclone:
   ```

This extends token validity but may still expire periodically.

### Solution 3: Use Service Account (Advanced)

Service accounts don't expire and don't require OAuth:

1. Create a service account in Google Cloud Console
2. Download the JSON key file
3. Configure rclone to use service account:
   ```bash
   docker exec -it hr_web rclone config
   # Edit remote, set service_account_file to the JSON path
   ```

## Why Container Restart Doesn't Matter

**Good news:** Container restarts don't cause token expiration because:
- ✅ Tokens are stored in Docker volume (`rclone_config`)
- ✅ Volume persists across container restarts
- ✅ Rclone reads tokens from the config file

**The issue is token expiration, not container restart.**

## Checking Your OAuth App Status

1. Go to: https://console.cloud.google.com/apis/credentials
2. Find your OAuth 2.0 Client ID
3. Check the status:
   - **Testing** = Tokens expire after 7 days ❌
   - **Production** = Tokens don't expire ✅

## Verification Steps

After moving to Production mode:

1. **Test connection:**
   ```powershell
   docker exec hr_web rclone --config /var/www/.config/rclone/rclone.conf lsd rclone:
   ```

2. **Wait 1 hour** (access token expires)

3. **Test again:**
   ```powershell
   docker exec hr_web rclone --config /var/www/.config/rclone/rclone.conf lsd rclone:
   ```

4. **If it still works**, auto-refresh is working! ✅

5. **Check token expiry in config:**
   ```powershell
   docker exec hr_web cat /var/www/.config/rclone/rclone.conf | grep expiry
   ```
   
   The expiry date should update automatically when refreshed.

## Preventing Future Issues

1. **Move to Production mode** (most important)
2. **Monitor backup logs** for authentication errors
3. **Set up alerts** if backups fail
4. **Consider service account** for production systems

## Summary

| Issue | Cause | Solution |
|-------|-------|----------|
| Token expires after 1 week | OAuth app in Testing mode | Move to Production mode |
| Token expires after 1 hour | Normal (access token) | Auto-refreshes (if refresh token valid) |
| Re-auth needed after restart | Config not persisted | Check Docker volume mount |
| Re-auth needed daily | Refresh token expired OR config file not writable | Fix permissions OR move to Production mode |
| "unauthorized_client" error | Config file not writable OR invalid client_id/secret | Fix permissions OR update credentials |
| Works for a while then fails | Config file permissions prevent token refresh | Fix file ownership/permissions |

**The fix:** Move your OAuth app to Production mode in Google Cloud Console, then re-authenticate once. After that, it should work indefinitely!
