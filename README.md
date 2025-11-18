# ListDAV

A modular WebDAV server that exposes various data sources as virtual file systems. Manage YouTube playlists, todo lists, calendars, and more using any file manager.

## Features

- **WebDAV Protocol** - Access from any OS (Windows, macOS, Linux)
- **Modular Architecture** - Add new data sources via plugins
- **Dual-Panel Web UI** - Built-in file manager with keyboard shortcuts
- **Virtual Views** - Filter and organize files by status
- **Trash System** - 30-day retention for deleted items

## Modules

### Available
- **YouTube** - Manage playlists as folders, videos as files

### Planned
- Todo Lists (Todoist, Things, local)
- Calendar (CalDAV, Google Calendar)
- Shopping Lists
- Bookmarks

## Quick Start

### Docker (Recommended)

```bash
git clone https://github.com/yourname/list-dav.git
cd list-dav
cp .env.example .env
# Edit .env with your settings
docker-compose up -d
```

Access at `http://localhost:8080`

### Manual Installation

Requirements:
- PHP 8.3+
- Composer
- SQLite 3

```bash
git clone https://github.com/yourname/list-dav.git
cd list-dav
composer install
cp .env.example .env
# Edit .env with your settings
php bin/server.php
```

## Documentation

- [Product Requirements](docs/PRD.md)
- [Implementation Plan](docs/IMPLEMENTATION_PLAN.md)
- [Architecture](docs/ARCHITECTURE.md) (coming soon)
- [Module Development](docs/modules/) (coming soon)

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Static analysis
composer analyse

# Code style
composer cs:fix
```

## License

MIT License - see [LICENSE](LICENSE) file.

## Status

🚧 **Work in Progress** - Not ready for production use.
