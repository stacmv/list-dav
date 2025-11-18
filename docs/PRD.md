# ListDAV - Product Requirements Document

## Executive Summary

ListDAV is a modular WebDAV server that exposes various data sources (YouTube playlists, todo lists, calendars, shopping lists) as virtual file systems. Users can manage their data using any WebDAV-compatible file manager or through a built-in dual-panel web interface.

## Problem Statement

Users have data scattered across multiple services (YouTube, task managers, calendars) with different interfaces. They want to:
- Manage data uniformly using familiar file operations (copy, move, delete)
- Perform bulk operations easily
- Automate workflows using standard file system tools
- Access everything from one place

## Solution

A WebDAV server that:
- Presents remote service data as files and folders
- Supports standard file operations that translate to API calls
- Provides a modular architecture for adding new data sources
- Includes a dual-panel file manager web UI

## Target Audience

- **Primary:** Tech-savvy individuals who self-host applications
- **Secondary:** Small teams needing shared access to organized data

## Core Features

### 1. WebDAV Server Core

- Full WebDAV compliance (RFC 4918)
- HTTP Basic Authentication (replaceable auth module)
- SQLite for local state and caching
- Support for custom properties (PROPFIND/PROPPATCH)

### 2. Modular Architecture

- Hybrid module system:
  - **Composer packages** for official/stable modules
  - **Local modules/** directory for development/custom modules
- Standardized module interface
- Module capability declarations for cross-module operations
- Per-module configuration

### 3. YouTube Playlist Module (First Module)

#### Virtual File Structure
```
/youtube/
  /playlists/
    /Playlist Name/
      [ ] unwatched-video.url
      [?] youtube-marked.url
      [✓] confirmed-watched.url
      [—] skipped-video.url

/views/
  /by-status/
    /new/
    /youtube-watched/
    /confirmed/
    /skipped/
  /actionable/
    /to-review/
    /to-delete/

/trash/
  /youtube/
    deleted-video.url    (30-day retention)
```

#### Video Status System

| Prefix | Meaning | Description |
|--------|---------|-------------|
| `[ ]` | New | Never watched |
| `[?]` | YouTube-marked | YouTube reports watched, not confirmed |
| `[✓]` | Confirmed | User confirmed watched |
| `[—]` | Skipped | User will not watch |

#### Operations

| File Operation | YouTube Action |
|----------------|----------------|
| Copy video to another playlist folder | Add to playlist |
| Move video to another playlist folder | Add to playlist + Remove from source |
| Delete video | Move to trash (30-day retention) |
| Rename (change prefix) | Update watch status |
| Move to view folder | Update watch status |
| Create folder in /playlists/ | Create new playlist |
| Delete folder | Delete playlist |

#### Status Management Methods

1. **View folders** - Move/copy files between status folders
2. **Rename** - Change status prefix in filename
3. **Action files** - Write to special files for bulk operations

Implementation will be phased with independent testing for each method.

### 4. Web Interface (Dual-Panel File Manager)

#### Technology Stack
- **Backend:** HTMX + Alpine.js (server-driven, minimal JS)
- **UI Components:** Framework-agnostic, replaceable components
- **Styling:** Tailwind CSS or similar utility-first framework

#### Features
- Dual-panel layout (like mc, Far Commander, Free Commander)
- File/folder navigation
- Copy, move, delete operations
- Rename files
- Preview panel (video thumbnails, task content)
- Multi-select with bulk operations
- Search and filtering
- Module settings and OAuth configuration
- Keyboard shortcuts for navigation and common operations
- Responsive design (desktop-focused, mobile-friendly)

#### Keyboard Shortcuts (Reference: mc/Far)
- `Tab` - Switch panels
- `F5` - Copy
- `F6` - Move
- `F7` - Create folder
- `F8` - Delete
- `Enter` - Open/Enter
- `Space` - Select item
- `Ctrl+A` - Select all
- `/` or `Ctrl+F` - Search

### 5. Authentication Module

- HTTP Basic Auth (default)
- Modular design for future replacements:
  - OAuth2/OIDC
  - LDAP
  - API keys

### 6. Trash System

- Deleted items move to `/trash/` instead of permanent deletion
- 30-day retention period
- Restore by moving back to original location
- Permanent delete from trash
- Auto-cleanup of expired items

## Technical Requirements

### Environment

| Requirement | Specification |
|-------------|---------------|
| PHP Version | 8.3+ (8.4 preferred) |
| Database | SQLite 3 |
| Web Server | Apache/Nginx with PHP-FPM, or built-in PHP server |

### Dependencies

- **sabre/dav** - WebDAV server implementation
- **guzzlehttp/guzzle** - HTTP client for API calls
- **google/apiclient** - YouTube Data API
- **vlucas/phpdotenv** - Environment configuration

### Code Quality Tools

| Tool | Purpose |
|------|---------|
| PHPStan (level 9) | Static analysis |
| Psalm | Static analysis |
| PHP CS Fixer | Code style |
| PHP CodeSniffer | Code style |
| PHPUnit | Unit tests |
| Pest | Feature tests |
| Infection | Mutation testing |
| Rector | Automated refactoring |

### Deployment Options

1. **Docker Compose** (recommended for VPS)
   - PHP-FPM container
   - Nginx container
   - SQLite volume

2. **Traditional Hosting** (shared hosting without Docker)
   - PHP 8.3+ with required extensions
   - Apache with mod_rewrite or Nginx
   - Composer for dependencies

## Module System Architecture

### Module Interface

```php
interface ModuleInterface
{
    public function getName(): string;
    public function getVersion(): string;
    public function getRootCollection(): DAV\ICollection;
    public function getCapabilities(): array;
    public function canAcceptFrom(string $sourceModule): bool;
    public function convertFromExternal(
        string $sourceModule,
        DAV\INode $node
    ): DAV\INode;
}
```

### Module Capabilities

Modules declare what they can do:
```php
[
    'read' => true,
    'write' => true,
    'delete' => true,
    'create_folders' => true,
    'custom_properties' => ['watched', 'duration'],
    'accepts_from' => ['todo', 'calendar'],
    'file_formats' => ['url', 'json'],
]
```

### Cross-Module Operations

When moving/copying between modules:
1. Source module's `canAcceptFrom()` is checked
2. If compatible, `convertFromExternal()` transforms the data
3. If incompatible, operation is rejected with clear error

Example rules:
- YouTube → Todo: Create task with video link
- Todo → Calendar: Create event from task
- Calendar → Todo: Create task from event
- YouTube → Shopping: Rejected (incompatible)

## File Format Configuration

Each module defines its preferred file format:

| Module | Default Format | Alternatives |
|--------|----------------|--------------|
| YouTube | `.url` (opens in browser) | `.json`, `.webloc` |
| Todo | `.txt` or `.md` | `.json` |
| Calendar | `.ics` | `.json` |
| Shopping | `.txt` | `.json` |

Format is configurable per module in settings.

## Security Considerations

### Authentication
- HTTP Basic Auth over HTTPS only
- Rate limiting on auth failures
- Session management for web UI

### Authorization
- Per-module access control
- Operation-level permissions
- API key scoping (e.g., read-only YouTube)

### Data Protection
- OAuth tokens encrypted at rest
- Sensitive config via environment variables
- No credential logging

### Input Validation
- Sanitize filenames
- Validate paths to prevent traversal
- Rate limit API calls to external services

## Non-Functional Requirements

### Performance
- Lazy loading of folder contents
- Caching of API responses (configurable TTL)
- Pagination for large playlists

### Reliability
- Graceful degradation when external APIs fail
- Retry logic with exponential backoff
- Clear error messages

### Maintainability
- PSR-12 code style
- Comprehensive documentation
- High test coverage (>80%)

## Success Metrics

1. **Functionality:** All CRUD operations work correctly
2. **Performance:** Directory listing < 2s for 100 items
3. **Reliability:** 99% uptime for self-hosted instance
4. **Usability:** User can complete common tasks without documentation

## Out of Scope (v1.0)

- Real-time synchronization (polling only)
- Collaborative editing
- Mobile native apps
- Video streaming/download
- Advanced search (full-text, tags)

## Future Modules (Planned)

- **Todo Lists** (Todoist, Things, local)
- **Calendar** (CalDAV, Google Calendar)
- **Shopping Lists** (local, shared)
- **Bookmarks** (browser bookmarks as files)
- **Notes** (Obsidian vault, Notion)

## Glossary

- **WebDAV:** Web Distributed Authoring and Versioning - HTTP extension for file management
- **View folder:** Virtual folder that shows filtered subset of files
- **Action file:** Special file that triggers operations when written to
- **Module:** Plugin that provides a virtual file system for a specific data source

## Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2024-01-XX | - | Initial PRD |
