# ListDAV Testing Guide

This guide describes how to set up and manually test ListDAV on Windows with PHP installed locally.

## Prerequisites

### Windows Requirements

1. **PHP 8.3+** installed and in PATH
   - Download from https://windows.php.net/download/
   - Extract to `C:\php`
   - Add `C:\php` to system PATH

2. **Required PHP Extensions**
   Enable in `php.ini`:
   ```ini
   extension=pdo_sqlite
   extension=sqlite3
   extension=curl
   extension=openssl
   extension=mbstring
   ```

3. **Composer** installed
   - Download from https://getcomposer.org/download/
   - Run installer

4. **Git** (optional, for cloning)

### Verify Installation

Open PowerShell or Command Prompt:

```powershell
php -v
# Should show PHP 8.3.x or higher

composer -V
# Should show Composer version

php -m | findstr sqlite
# Should show pdo_sqlite and sqlite3
```

---

## Quick Start

### 1. Clone and Install

```powershell
# Clone repository
git clone https://github.com/yourname/list-dav.git
cd list-dav

# Install dependencies
composer install

# Create configuration
copy .env.example .env
```

### 2. Start Development Server

```powershell
php bin/server.php 8080
```

Server will start at `http://127.0.0.1:8080`

### 3. Default Credentials

- **Username:** admin
- **Password:** admin

---

## Manual Testing Scenarios

### Test 1: Basic WebDAV Connection

#### Using Windows Explorer (Map Network Drive)

1. Open File Explorer
2. Right-click "This PC" → "Map network drive..."
3. Enter: `http://127.0.0.1:8080`
4. Check "Connect using different credentials"
5. Enter: admin / admin

**Expected:** Drive appears with folders: `youtube`, `views`, `trash`

#### Using Command Line (curl)

```powershell
# List root directory
curl -u admin:admin -X PROPFIND http://127.0.0.1:8080/ -H "Depth: 1"

# Expected: XML response with youtube, views, trash folders
```

### Test 2: Browse YouTube Playlists

#### File Explorer

1. Navigate to mapped drive
2. Open `youtube` folder
3. Open `playlists` folder

**Expected:** See folders: Music, Tutorials, Watch Later

#### curl

```powershell
curl -u admin:admin -X PROPFIND http://127.0.0.1:8080/youtube/playlists/ -H "Depth: 1"
```

### Test 3: View Video Files

1. Open any playlist folder (e.g., `Music`)

**Expected:** See video files with status prefixes:
- `[ ] Never Gonna Give You Up.url`
- `[ ] Bohemian Rhapsody.url`
- `[ ] Gangnam Style.url`

### Test 4: Open Video in Browser

1. Double-click any `.url` file in playlist

**Expected:** YouTube video opens in browser

### Test 5: Change Watch Status (Rename)

1. Right-click video file
2. Rename from `[ ] Video Name.url` to `[✓] Video Name.url`

**Expected:** Status changes to "confirmed watched"

Alternative prefixes:
- `[?]` - YouTube marked as watched
- `[✓]` - Confirmed watched
- `[—]` - Skipped

### Test 6: Copy Video Between Playlists

1. Select video file in one playlist
2. Copy (Ctrl+C)
3. Navigate to another playlist
4. Paste (Ctrl+V)

**Expected:** Video appears in destination playlist

### Test 7: Move Video Between Playlists

1. Select video file
2. Cut (Ctrl+X)
3. Navigate to another playlist
4. Paste (Ctrl+V)

**Expected:** Video moves to destination, removed from source

### Test 8: Delete Video from Playlist

1. Select video file
2. Press Delete key
3. Confirm deletion

**Expected:** Video removed from playlist, appears in `/trash` folder

### Test 9: Create New Playlist

1. Navigate to `/youtube/playlists/`
2. Right-click → New → Folder
3. Name it "My New Playlist"

**Expected:** New folder created representing a playlist

### Test 10: Bulk Operations

1. Navigate to a playlist with multiple videos
2. Select multiple files (Ctrl+click or Shift+click)
3. Move to another playlist

**Expected:** All selected videos move together

### Test 11: View Folders (Status Filtering)

1. Navigate to `/views/` folder

**Expected:** See view folders:
- `youtube-new` - Unwatched videos
- `youtube-watched` - YouTube marked
- `youtube-confirmed` - User confirmed
- `youtube-to-review` - Needs review

### Test 12: Trash and Restore

1. Delete a video (Test 8)
2. Navigate to `/trash/`
3. Find deleted video
4. Move back to original playlist

**Expected:** Video restored to playlist

### Test 13: Web UI

1. Open browser to `http://127.0.0.1:8080/`

**Expected:** sabre/dav browser interface showing directory listing

Note: The dual-panel file manager UI is available at a separate endpoint (to be integrated).

---

## Testing with Different WebDAV Clients

### Windows Explorer

See Test 1 above.

### WinSCP

1. Open WinSCP
2. New Site → Protocol: WebDAV
3. Host: 127.0.0.1, Port: 8080
4. User: admin, Password: admin
5. Connect

### Cyberduck

1. Open Cyberduck
2. New Connection → WebDAV (HTTP)
3. Server: 127.0.0.1, Port: 8080
4. Username: admin, Password: admin
5. Connect

### Command Line with curl

```powershell
# GET file
curl -u admin:admin http://127.0.0.1:8080/youtube/playlists/Music/[%20]%20Never%20Gonna%20Give%20You%20Up.url

# PUT file (add video)
curl -u admin:admin -X PUT http://127.0.0.1:8080/youtube/playlists/Music/newvideo.url -d "dQw4w9WgXcQ"

# DELETE file
curl -u admin:admin -X DELETE http://127.0.0.1:8080/youtube/playlists/Music/[%20]%20Gangnam%20Style.url

# MOVE file
curl -u admin:admin -X MOVE http://127.0.0.1:8080/youtube/playlists/Music/[%20]%20Video.url ^
  -H "Destination: http://127.0.0.1:8080/youtube/playlists/Tutorials/[%20]%20Video.url"

# COPY file
curl -u admin:admin -X COPY http://127.0.0.1:8080/youtube/playlists/Music/[%20]%20Video.url ^
  -H "Destination: http://127.0.0.1:8080/youtube/playlists/Tutorials/[%20]%20Video.url"

# MKCOL (create folder)
curl -u admin:admin -X MKCOL http://127.0.0.1:8080/youtube/playlists/NewPlaylist/
```

---

## Expected Test Results

### Success Criteria

| Test | Expected Result | Status |
|------|-----------------|--------|
| WebDAV Connection | Drive mounts, folders visible | |
| Browse Playlists | 3 demo playlists shown | |
| View Videos | Videos with status prefixes | |
| Open Video | Opens YouTube in browser | |
| Rename Status | Status updates in database | |
| Copy Video | Video in both playlists | |
| Move Video | Video in destination only | |
| Delete Video | Video in trash folder | |
| Create Playlist | New folder created | |
| Bulk Operations | All items processed | |
| View Folders | Filtered video lists | |
| Trash Restore | Video back in playlist | |

### Common Issues

#### Issue: "Network Error" when mapping drive

**Solution:** Check if PHP server is running and accessible:
```powershell
curl http://127.0.0.1:8080/
```

#### Issue: "Access Denied"

**Solution:** Verify credentials (admin/admin) and check server logs.

#### Issue: Files don't appear

**Solution:** Check SQLite database exists:
```powershell
dir var\data\listdav.sqlite
```

#### Issue: Special characters in filenames

**Solution:** Some WebDAV clients have issues with Unicode. The checkmark (✓) might display incorrectly. Use ASCII alternatives: `[x]` instead of `[✓]`.

---

## Automated Testing

### Run Unit Tests

```powershell
composer test:unit
```

### Run Integration Tests

```powershell
composer test:integration
```

### Run All Tests

```powershell
composer test
```

### Run Static Analysis

```powershell
composer analyse
```

### Check Code Style

```powershell
composer cs:check
```

---

## Database Inspection

SQLite database is at `var/data/listdav.sqlite`.

### View with SQLite CLI

```powershell
# If sqlite3 is installed
sqlite3 var/data/listdav.sqlite

# List tables
.tables

# View users
SELECT * FROM users;

# View watch status
SELECT * FROM youtube_watch_status;

# View trash
SELECT * FROM trash;
```

### View with DB Browser for SQLite

1. Download from https://sqlitebrowser.org/
2. Open `var/data/listdav.sqlite`
3. Browse tables

---

## Test Data Reset

To reset all test data:

```powershell
# Delete database
del var\data\listdav.sqlite

# Delete cache
del var\cache\*

# Restart server
php bin/server.php 8080
```

Fresh database with default admin user will be created.

---

## Performance Testing

### Test with Large Playlists

Modify `modules/youtube/src/Services/YouTubeClient.php` to return more mock videos in `getMockVideos()` method.

### Measure Response Times

```powershell
# Time a PROPFIND request
Measure-Command { curl -u admin:admin -X PROPFIND http://127.0.0.1:8080/youtube/playlists/Music/ -H "Depth: 1" }
```

**Target:** < 2 seconds for 100 items

---

## Security Testing

### Test Authentication

```powershell
# Should fail (401 Unauthorized)
curl http://127.0.0.1:8080/youtube/playlists/

# Should fail (401 Unauthorized)
curl -u wrong:password http://127.0.0.1:8080/youtube/playlists/

# Should succeed
curl -u admin:admin http://127.0.0.1:8080/youtube/playlists/
```

### Test Path Traversal

```powershell
# Should be blocked
curl -u admin:admin http://127.0.0.1:8080/../../../etc/passwd
```

---

## Checklist

Before considering testing complete:

- [ ] All 13 manual tests pass
- [ ] At least 2 different WebDAV clients tested
- [ ] Database operations verified
- [ ] Error handling tested (invalid paths, auth failures)
- [ ] Performance acceptable (< 2s response)
- [ ] Security tests pass

---

## Troubleshooting

### Enable Debug Mode

In `.env`:
```ini
APP_DEBUG=true
```

### View PHP Errors

```powershell
php -S 127.0.0.1:8080 -t public 2>&1
```

### Check PHP Extensions

```powershell
php -m
```

Required: pdo_sqlite, sqlite3, curl, json, mbstring

### Check Composer Autoload

```powershell
composer dump-autoload
```

---

## Next Steps

After manual testing:

1. Document any bugs found
2. Note performance issues
3. Test with real YouTube API (requires OAuth setup)
4. Test in Docker environment
5. Test on other operating systems
