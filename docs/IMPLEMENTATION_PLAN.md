# ListDAV - Implementation Plan

## Overview

This document outlines the phased implementation plan for ListDAV. The project is divided into 6 phases, each with clear milestones and deliverables.

**Estimated Timeline:** 12-16 weeks for MVP (Phases 1-4)

## Phase 1: Project Foundation (Week 1-2)

### 1.1 Project Setup

#### Tasks
- [ ] Initialize Composer project
- [ ] Configure PHP 8.3+ requirements
- [ ] Set up directory structure
- [ ] Configure autoloading (PSR-4)
- [ ] Create `.env.example` with all configuration options
- [ ] Set up Git hooks (pre-commit for linting)

#### Directory Structure
```
/list-dav/
├── bin/                    # CLI scripts
│   └── server.php          # Built-in PHP server launcher
├── config/
│   ├── app.php             # Main configuration
│   ├── modules.php         # Module registration
│   └── services.php        # DI container config
├── docs/
│   ├── PRD.md
│   ├── IMPLEMENTATION_PLAN.md
│   ├── ARCHITECTURE.md
│   └── modules/
│       └── YOUTUBE.md
├── modules/                # Local/development modules
├── public/
│   ├── index.php           # WebDAV entry point
│   └── ui/                 # Web UI assets
├── src/
│   ├── Core/
│   │   ├── Server.php
│   │   ├── Container.php
│   │   └── Config.php
│   ├── Module/
│   │   ├── ModuleInterface.php
│   │   ├── ModuleLoader.php
│   │   ├── ModuleRegistry.php
│   │   └── AbstractModule.php
│   ├── Auth/
│   │   ├── AuthInterface.php
│   │   ├── BasicAuth.php
│   │   └── AuthPlugin.php
│   ├── VirtualFS/
│   │   ├── VirtualCollection.php
│   │   ├── VirtualFile.php
│   │   └── ViewCollection.php
│   ├── Trash/
│   │   ├── TrashManager.php
│   │   └── TrashCollection.php
│   └── WebUI/
│       ├── Controllers/
│       └── Views/
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── E2E/
├── var/
│   ├── cache/
│   ├── logs/
│   └── data/               # SQLite database
├── vendor/
├── composer.json
├── composer.lock
├── phpunit.xml
├── phpstan.neon
├── psalm.xml
├── .php-cs-fixer.php
├── infection.json
├── rector.php
├── .env.example
├── .gitignore
├── docker-compose.yml
├── Dockerfile
└── README.md
```

### 1.2 Code Quality Infrastructure

#### Static Analysis Setup

**PHPStan Configuration (phpstan.neon):**
```yaml
parameters:
    level: 9
    paths:
        - src
        - modules
    excludePaths:
        - vendor
    checkMissingIterableValueType: true
    checkGenericClassInNonGenericObjectType: true
```

**Psalm Configuration (psalm.xml):**
```xml
<?xml version="1.0"?>
<psalm errorLevel="1"
       resolveFromConfigFile="true"
       xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xmlns="https://getpsalm.org/schema/config">
    <projectFiles>
        <directory name="src"/>
        <directory name="modules"/>
        <ignoreFiles>
            <directory name="vendor"/>
        </ignoreFiles>
    </projectFiles>
</psalm>
```

**PHP CS Fixer (.php-cs-fixer.php):**
```php
<?php
return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PHP83Migration' => true,
        'strict_param' => true,
        'declare_strict_types' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__ . '/src')
            ->in(__DIR__ . '/modules')
            ->in(__DIR__ . '/tests')
    );
```

#### Testing Setup

**Test Types:**

| Type | Tool | Location | Purpose |
|------|------|----------|---------|
| Unit | PHPUnit/Pest | `tests/Unit/` | Individual class testing |
| Integration | PHPUnit/Pest | `tests/Integration/` | Module interaction testing |
| E2E | PHPUnit + HTTP client | `tests/E2E/` | Full WebDAV protocol testing |
| Mutation | Infection | - | Test quality validation |

**PHPUnit Configuration (phpunit.xml):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php"
         colors="true"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="E2E">
            <directory>tests/E2E</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">src</directory>
        </include>
        <report>
            <html outputDirectory="var/coverage"/>
            <clover outputFile="var/coverage/clover.xml"/>
        </report>
    </coverage>
</phpunit>
```

### 1.3 Deliverables

- [ ] Working Composer project with all dev dependencies
- [ ] All static analysis tools configured and passing
- [ ] PHPUnit/Pest configured with example test
- [ ] Pre-commit hooks running linters
- [ ] README with setup instructions

---

## Phase 2: Core WebDAV Server (Week 2-4)

### 2.1 Basic Server Implementation

#### Tasks
- [ ] Create Server class wrapping sabre/dav
- [ ] Implement basic directory/file serving
- [ ] Set up PSR-11 DI container
- [ ] Create configuration system

#### Key Classes

```php
// src/Core/Server.php
class Server
{
    public function __construct(
        private Container $container,
        private Config $config,
        private ModuleRegistry $modules,
    ) {}

    public function run(): void;
    public function addPlugin(ServerPlugin $plugin): void;
}
```

### 2.2 Authentication Module

#### Tasks
- [ ] Define AuthInterface
- [ ] Implement BasicAuth adapter
- [ ] Create AuthPlugin for sabre/dav
- [ ] Add user management (SQLite storage)

#### Interface Design

```php
// src/Auth/AuthInterface.php
interface AuthInterface
{
    public function authenticate(
        string $username,
        string $password
    ): ?User;

    public function getUsers(): array;
    public function createUser(string $username, string $password): User;
    public function deleteUser(string $username): void;
}
```

### 2.3 Virtual File System Base

#### Tasks
- [ ] Create VirtualCollection (folder abstraction)
- [ ] Create VirtualFile (file abstraction)
- [ ] Implement file content type handling
- [ ] Add custom property support

### 2.4 SQLite Database

#### Schema

```sql
-- Users
CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Trash
CREATE TABLE trash (
    id INTEGER PRIMARY KEY,
    original_path TEXT NOT NULL,
    module TEXT NOT NULL,
    metadata JSON,
    deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL
);

-- Cache
CREATE TABLE cache (
    key TEXT PRIMARY KEY,
    value BLOB,
    expires_at DATETIME
);

-- Module state
CREATE TABLE module_state (
    module TEXT NOT NULL,
    key TEXT NOT NULL,
    value JSON,
    PRIMARY KEY (module, key)
);
```

### 2.5 Testing

- [ ] Unit tests for all core classes
- [ ] Integration test: mount server, create/read/delete file
- [ ] E2E test: connect with cadaver/curl, perform operations

### 2.6 Deliverables

- [ ] Working WebDAV server with static file serving
- [ ] HTTP Basic authentication
- [ ] SQLite database with migrations
- [ ] 80%+ test coverage for core

---

## Phase 3: Module System (Week 4-6)

### 3.1 Module Interface

#### Tasks
- [ ] Define ModuleInterface
- [ ] Create AbstractModule base class
- [ ] Implement ModuleLoader (Composer + local)
- [ ] Create ModuleRegistry

#### Interface Design

```php
// src/Module/ModuleInterface.php
interface ModuleInterface
{
    // Identity
    public function getName(): string;
    public function getVersion(): string;
    public function getDescription(): string;

    // File system
    public function getRootCollection(): ICollection;
    public function getViewCollections(): array;

    // Capabilities
    public function getCapabilities(): ModuleCapabilities;
    public function getConfigSchema(): array;

    // Cross-module
    public function canAcceptFrom(string $sourceModule): bool;
    public function convertFromExternal(
        string $sourceModule,
        INode $node
    ): ?INode;

    // Lifecycle
    public function initialize(Container $container): void;
    public function shutdown(): void;
}
```

```php
// src/Module/ModuleCapabilities.php
class ModuleCapabilities
{
    public function __construct(
        public readonly bool $read = true,
        public readonly bool $write = false,
        public readonly bool $delete = false,
        public readonly bool $createFolders = false,
        public readonly array $customProperties = [],
        public readonly array $acceptsFrom = [],
        public readonly array $fileFormats = ['json'],
        public readonly string $defaultFormat = 'json',
    ) {}
}
```

### 3.2 Module Loader

#### Composer Package Detection

```php
// src/Module/ModuleLoader.php
class ModuleLoader
{
    public function loadComposerModules(): array
    {
        $installed = json_decode(
            file_get_contents('vendor/composer/installed.json'),
            true
        );

        $modules = [];
        foreach ($installed['packages'] as $package) {
            if (isset($package['extra']['listdav-module'])) {
                $class = $package['extra']['listdav-module']['class'];
                $modules[] = new $class();
            }
        }

        return $modules;
    }

    public function loadLocalModules(): array
    {
        $modules = [];
        foreach (glob('modules/*/module.php') as $file) {
            $modules[] = require $file;
        }
        return $modules;
    }
}
```

#### Composer Package Structure (for external modules)

```json
{
    "name": "vendor/listdav-module-example",
    "type": "listdav-module",
    "extra": {
        "listdav-module": {
            "class": "Vendor\\ListDAV\\ExampleModule",
            "name": "example"
        }
    },
    "autoload": {
        "psr-4": {
            "Vendor\\ListDAV\\": "src/"
        }
    }
}
```

### 3.3 Cross-Module Operations

#### Tasks
- [ ] Implement permission checking plugin
- [ ] Create conversion pipeline
- [ ] Add detailed error messages for rejected operations

```php
// src/Module/CrossModulePlugin.php
class CrossModulePlugin extends ServerPlugin
{
    public function beforeMove(string $source, string $dest): bool
    {
        $sourceModule = $this->registry->getModuleForPath($source);
        $destModule = $this->registry->getModuleForPath($dest);

        if ($sourceModule === $destModule) {
            return true; // Same module, allow
        }

        if (!$destModule->canAcceptFrom($sourceModule->getName())) {
            throw new Forbidden(sprintf(
                'Cannot move from %s to %s: incompatible modules',
                $sourceModule->getName(),
                $destModule->getName()
            ));
        }

        return true;
    }
}
```

### 3.4 Testing

- [ ] Unit tests for ModuleLoader
- [ ] Integration tests for module registration
- [ ] E2E tests for cross-module operations
- [ ] Test local module loading
- [ ] Test Composer module detection

### 3.5 Deliverables

- [ ] Complete module system
- [ ] Example/template module
- [ ] Module development documentation
- [ ] Cross-module operation handling

---

## Phase 4: YouTube Module (Week 6-10)

### 4.1 YouTube API Integration

#### Tasks
- [ ] Set up Google API client
- [ ] Implement OAuth2 flow
- [ ] Create YouTubeClient wrapper
- [ ] Add token refresh logic
- [ ] Implement rate limiting

```php
// modules/youtube/src/YouTubeClient.php
class YouTubeClient
{
    public function getPlaylists(): array;
    public function getPlaylistItems(string $playlistId): array;
    public function addToPlaylist(string $playlistId, string $videoId): void;
    public function removeFromPlaylist(string $playlistItemId): void;
    public function createPlaylist(string $title): string;
    public function deletePlaylist(string $playlistId): void;
    public function getVideoDetails(string $videoId): array;
}
```

### 4.2 Virtual File System Structure

#### Tasks
- [ ] Implement PlaylistCollection
- [ ] Implement VideoFile
- [ ] Add watch status tracking (local DB)
- [ ] Implement file format handling (.url, .json, etc.)

```php
// modules/youtube/src/Collections/PlaylistCollection.php
class PlaylistCollection extends VirtualCollection
{
    public function getChildren(): array
    {
        $videos = $this->client->getPlaylistItems($this->playlistId);

        return array_map(
            fn($video) => new VideoFile($video, $this->statusTracker),
            $videos
        );
    }

    public function createFile($name, $data = null): void
    {
        // Adding video to playlist by creating a file
        $videoId = $this->extractVideoId($name, $data);
        $this->client->addToPlaylist($this->playlistId, $videoId);
    }
}
```

### 4.3 Watch Status System (Phased Implementation)

**Important:** Each status management method is implemented as a separate sub-phase with independent testing.

#### Phase 4.3.1: Basic Status Tracking

- [ ] SQLite schema for watch status
- [ ] Status display in filename prefix
- [ ] Read-only status display

```sql
CREATE TABLE youtube_watch_status (
    video_id TEXT PRIMARY KEY,
    user_status TEXT CHECK(user_status IN ('unmarked', 'watched', 'skipped')),
    youtube_watched BOOLEAN DEFAULT FALSE,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Testing:**
- Unit tests for status storage
- Integration tests for status display in filenames

#### Phase 4.3.2: Rename-Based Status Change

- [ ] Parse status from filename on rename
- [ ] Update database on rename
- [ ] Validate status transitions

```php
// When user renames "[ ] Video.url" to "[✓] Video.url"
public function setName($name): void
{
    $newStatus = $this->parseStatusFromName($name);
    $this->statusTracker->setStatus($this->videoId, $newStatus);
}
```

**Testing:**
- Unit tests for status parsing
- Integration tests for rename operations
- E2E tests with WebDAV client

#### Phase 4.3.3: View Folders

- [ ] Implement ViewCollection for status filtering
- [ ] Add by-status views (/new, /confirmed, etc.)
- [ ] Add actionable views (/to-review, /to-delete)
- [ ] Handle moves between view folders

```php
// src/VirtualFS/ViewCollection.php
class ViewCollection extends VirtualCollection
{
    public function __construct(
        private string $name,
        private Closure $filter,
        private ModuleInterface $module,
    ) {}

    public function getChildren(): array
    {
        $allFiles = $this->module->getAllFiles();
        return array_filter($allFiles, $this->filter);
    }
}
```

**Testing:**
- Unit tests for ViewCollection filtering
- Integration tests for status changes via move
- E2E tests for bulk operations

#### Phase 4.3.4: Action Files

- [ ] Implement action file detection
- [ ] Add `.mark-all-watched` action
- [ ] Add `.delete-watched` action
- [ ] Secure action execution

```php
// modules/youtube/src/ActionFile.php
class MarkAllWatchedAction extends ActionFile
{
    public function put($data): void
    {
        $content = stream_get_contents($data);
        if (trim($content) !== 'confirm') {
            throw new Forbidden('Write "confirm" to execute');
        }

        $this->playlist->markAllWatched();
    }
}
```

**Testing:**
- Unit tests for action parsing
- Integration tests for action execution
- Security tests (prevent injection)

### 4.4 Trash Integration

- [ ] Override delete to move to trash
- [ ] Store original path and metadata
- [ ] Implement restore operation
- [ ] Add 30-day cleanup job

### 4.5 Caching

- [ ] Cache playlist listings (5 min TTL)
- [ ] Cache video metadata (1 hour TTL)
- [ ] Invalidate on write operations
- [ ] Add cache warming on startup

### 4.6 Testing

Each sub-phase has its own test suite:

| Sub-phase | Unit Tests | Integration Tests | E2E Tests |
|-----------|------------|-------------------|-----------|
| 4.3.1 | Status storage | Status in filenames | - |
| 4.3.2 | Status parsing | Rename operations | cadaver rename |
| 4.3.3 | ViewCollection | Status via move | Bulk move |
| 4.3.4 | Action parsing | Action execution | Action security |

### 4.7 Deliverables

- [ ] Complete YouTube module
- [ ] OAuth setup documentation
- [ ] All status management methods working
- [ ] Comprehensive test coverage
- [ ] Performance benchmarks

---

## Phase 5: Web UI (Week 10-14)

### 5.1 Backend for UI

#### Tasks
- [ ] Create UI controller endpoints
- [ ] Implement session management
- [ ] Add CSRF protection
- [ ] Create API endpoints for UI operations

#### Endpoints

```
GET  /ui/                     # Main UI page
GET  /ui/api/browse?path=     # List directory
POST /ui/api/copy             # Copy files
POST /ui/api/move             # Move files
POST /ui/api/delete           # Delete files
POST /ui/api/rename           # Rename file
GET  /ui/api/preview?path=    # Get file preview
GET  /ui/api/search?q=        # Search files
GET  /ui/settings             # Settings page
POST /ui/settings/module      # Update module settings
GET  /ui/oauth/youtube        # Start YouTube OAuth
GET  /ui/oauth/youtube/callback # OAuth callback
```

### 5.2 Frontend Architecture

#### Technology Stack
- **HTMX** - AJAX without JavaScript
- **Alpine.js** - Minimal reactivity for UI state
- **Tailwind CSS** - Utility-first styling

#### Component Structure

```
public/ui/
├── index.html              # Main layout
├── css/
│   └── app.css             # Tailwind + custom styles
├── js/
│   ├── app.js              # Alpine.js components
│   └── shortcuts.js        # Keyboard handler
├── components/
│   ├── panel.html          # File panel partial
│   ├── file-row.html       # File row partial
│   ├── preview.html        # Preview panel partial
│   ├── context-menu.html   # Right-click menu
│   └── modal.html          # Modal dialogs
└── icons/
    └── *.svg
```

#### Main UI Layout

```html
<div x-data="fileManager()" class="h-screen flex flex-col">
    <!-- Toolbar -->
    <header class="toolbar">
        <button @click="copy()" title="F5">Copy</button>
        <button @click="move()" title="F6">Move</button>
        <button @click="newFolder()" title="F7">New Folder</button>
        <button @click="delete()" title="F8">Delete</button>
    </header>

    <!-- Dual panels -->
    <main class="flex-1 flex">
        <!-- Left panel -->
        <div class="panel" :class="{ 'active': activePanel === 'left' }">
            <div class="path-bar" x-text="leftPath"></div>
            <div hx-get="/ui/api/browse"
                 hx-vals='{"path": leftPath}'
                 hx-trigger="pathChanged">
                <!-- File list loaded here -->
            </div>
        </div>

        <!-- Right panel -->
        <div class="panel" :class="{ 'active': activePanel === 'right' }">
            <!-- Similar structure -->
        </div>
    </main>

    <!-- Status bar -->
    <footer class="status-bar">
        <span x-text="selectedCount + ' selected'"></span>
        <span x-text="totalSize"></span>
    </footer>
</div>
```

### 5.3 Keyboard Shortcuts

```javascript
// public/ui/js/shortcuts.js
document.addEventListener('keydown', (e) => {
    const shortcuts = {
        'Tab': () => Alpine.store('fm').switchPanel(),
        'F5': () => Alpine.store('fm').copy(),
        'F6': () => Alpine.store('fm').move(),
        'F7': () => Alpine.store('fm').newFolder(),
        'F8': () => Alpine.store('fm').delete(),
        'Enter': () => Alpine.store('fm').open(),
        ' ': () => Alpine.store('fm').toggleSelect(),
        'Delete': () => Alpine.store('fm').delete(),
        'Ctrl+a': () => Alpine.store('fm').selectAll(),
        'Ctrl+f': () => Alpine.store('fm').showSearch(),
        '/': () => Alpine.store('fm').showSearch(),
    };

    const key = (e.ctrlKey ? 'Ctrl+' : '') + e.key;
    if (shortcuts[key]) {
        e.preventDefault();
        shortcuts[key]();
    }
});
```

### 5.4 Component Design (Replaceable)

Each UI component follows a contract:

```javascript
// Component interface
{
    // Required methods
    render(data): string,

    // Events emitted
    events: ['select', 'open', 'contextmenu'],

    // Required CSS classes
    classes: ['file-row', 'file-row--selected', 'file-row--directory']
}
```

This allows replacing components without changing logic:
- Default: Simple HTML/Tailwind
- Alternative: Custom themed components
- Future: Web Components

### 5.5 Responsive Design

```css
/* Desktop: dual panels side by side */
@media (min-width: 768px) {
    .panels { flex-direction: row; }
    .panel { width: 50%; }
}

/* Mobile: single panel with toggle */
@media (max-width: 767px) {
    .panels { flex-direction: column; }
    .panel { display: none; }
    .panel.active { display: block; width: 100%; }
}
```

### 5.6 Testing

- [ ] Unit tests for UI controllers
- [ ] Integration tests for API endpoints
- [ ] E2E tests with Playwright/Cypress
  - [ ] Navigation
  - [ ] Copy/move/delete
  - [ ] Keyboard shortcuts
  - [ ] Search
  - [ ] OAuth flow

### 5.7 Deliverables

- [ ] Complete dual-panel file manager
- [ ] All operations working via UI
- [ ] Keyboard shortcuts functional
- [ ] Responsive mobile view
- [ ] Settings and OAuth pages

---

## Phase 6: Deployment & Documentation (Week 14-16)

### 6.1 Docker Setup

#### Dockerfile

```dockerfile
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    nginx \
    sqlite \
    && docker-php-ext-install pdo_sqlite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/entrypoint.sh /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
```

#### docker-compose.yml

```yaml
version: '3.8'

services:
  listdav:
    build: .
    ports:
      - "8080:80"
    volumes:
      - ./var/data:/app/var/data
      - ./.env:/app/.env
    environment:
      - APP_ENV=production
    restart: unless-stopped
```

### 6.2 Traditional Hosting

Documentation for shared hosting setup:
- Required PHP extensions
- Apache .htaccess configuration
- Nginx configuration
- Manual SQLite setup

### 6.3 Documentation

- [ ] README with quick start
- [ ] Installation guide (Docker + traditional)
- [ ] Configuration reference
- [ ] Module development guide
- [ ] API documentation
- [ ] Troubleshooting guide

### 6.4 Deliverables

- [ ] Docker image on Docker Hub / GHCR
- [ ] Complete documentation
- [ ] Example configurations
- [ ] Release v1.0.0

---

## Testing Strategy

### Test Coverage Goals

| Component | Coverage Target |
|-----------|----------------|
| Core | 90% |
| Module System | 85% |
| YouTube Module | 80% |
| Web UI Controllers | 75% |

### Continuous Testing

```bash
# Run all tests
composer test

# Individual suites
composer test:unit
composer test:integration
composer test:e2e

# Static analysis
composer analyse   # PHPStan + Psalm

# Code style
composer cs:check
composer cs:fix

# Mutation testing
composer test:mutation
```

### E2E WebDAV Testing

Using `curl` for WebDAV operations:

```bash
# PROPFIND (list directory)
curl -X PROPFIND http://localhost:8080/youtube/playlists/ \
     -u user:pass \
     -H "Depth: 1"

# GET (download file)
curl http://localhost:8080/youtube/playlists/Music/song.url \
     -u user:pass

# MOVE (move file)
curl -X MOVE http://localhost:8080/youtube/playlists/Music/song.url \
     -u user:pass \
     -H "Destination: http://localhost:8080/youtube/playlists/Jazz/song.url"

# DELETE
curl -X DELETE http://localhost:8080/youtube/playlists/Music/song.url \
     -u user:pass
```

---

## Risk Mitigation

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| YouTube API rate limits | High | High | Aggressive caching, rate limiting |
| YouTube API changes | Medium | High | Abstraction layer, version pinning |
| Complex WebDAV edge cases | Medium | Medium | Extensive E2E testing |
| Performance with large playlists | Medium | Medium | Pagination, lazy loading |
| OAuth token expiration | High | Low | Auto-refresh, clear error messages |

---

## Dependencies

### Production

```json
{
    "require": {
        "php": ">=8.3",
        "sabre/dav": "^4.4",
        "guzzlehttp/guzzle": "^7.8",
        "google/apiclient": "^2.15",
        "vlucas/phpdotenv": "^5.6",
        "psr/container": "^2.0",
        "psr/log": "^3.0",
        "monolog/monolog": "^3.5"
    }
}
```

### Development

```json
{
    "require-dev": {
        "phpunit/phpunit": "^10.5",
        "pestphp/pest": "^2.30",
        "phpstan/phpstan": "^1.10",
        "vimeo/psalm": "^5.20",
        "friendsofphp/php-cs-fixer": "^3.46",
        "squizlabs/php_codesniffer": "^3.8",
        "infection/infection": "^0.27",
        "rector/rector": "^0.19",
        "mockery/mockery": "^1.6",
        "fakerphp/faker": "^1.23"
    }
}
```

---

## Milestones Summary

| Milestone | Week | Deliverables |
|-----------|------|--------------|
| M1: Foundation | 2 | Project setup, tooling |
| M2: Core Server | 4 | WebDAV server, auth |
| M3: Module System | 6 | Module loading, cross-module ops |
| M4: YouTube Basic | 8 | Playlists, basic operations |
| M5: YouTube Status | 10 | All status management methods |
| M6: Web UI | 14 | Dual-panel file manager |
| M7: Release | 16 | Docker, docs, v1.0.0 |

---

## Next Steps

1. Review and approve this plan
2. Create GitHub issues for Phase 1 tasks
3. Set up development environment
4. Begin implementation

---

## Appendix A: Commit Message Convention

```
type(scope): subject

body

footer
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

Example:
```
feat(youtube): add watch status tracking

Implement SQLite storage for user watch status.
YouTube API status is now tracked separately from user confirmations.

Closes #42
```

## Appendix B: Branch Strategy

```
main              # Stable releases
develop           # Integration branch
feature/*         # New features
bugfix/*          # Bug fixes
release/*         # Release preparation
```
