# LogicPanel - Project Context

## Overview
LogicPanel is a **web-based hosting control panel** similar to cPanel/Plesk but for **Node.js and Python applications**. It provides a modern, containerized approach to application hosting using Docker.

## Core Architecture
- **Backend**: PHP 8.2 with Slim Framework
- **Frontend**: PHP templates with Vanilla JS (Lucide icons)
- **Database**: MySQL (Eloquent ORM)
- **Container Orchestration**: Docker API (creating user containers dynamically)
- **Terminal Gateway**: Node.js + node-pty (WebSocket-based real-time terminal)

## Key Components

### User Panel (`/templates/`)
- Dashboard, File Manager, Terminal, Database Management
- Application creation (Node.js, Python)
- GitHub deployment support

### API (`/src/Application/Controllers/`)
- `AuthController.php` - Login, Register, JWT tokens
- `ServiceController.php` - App/Container management
- `FileController.php` - File operations
- `DatabaseController.php` - MySQL/PostgreSQL/MongoDB provisioning

### Infrastructure (`/src/Infrastructure/`)
- `DockerService.php` - Docker container creation/management
- `DatabaseProvisionerService.php` - Multi-DB provisioning

### Terminal Gateway (`/services/gateway/`)
- Node.js WebSocket server
- Handles real-time terminal sessions to user containers

## Docker Services (docker-compose.yml)
- `app` - Main PHP application (port 8000)
- `terminal-gateway` - WebSocket terminal server (port 3002)
- `mysql` / `postgres` / `mongo` - Database engines
- `db-provisioner` - Database provisioning microservice
- `redis` - Caching

## Current Development Environment
- **Windows XAMPP** for local development
- Uses **Host Bind Mount** for file sync between containers
- Path: `C:\xampp\htdocs\logicpanel`

## Future Roadmap
1. **Traefik Integration** - Replace port-based routing with domain-based (unlimited apps)
2. **Master Admin Panel** - User management, packages, global settings
3. **Reseller System** - WHM-like reseller accounts
4. **2FA Security** - TOTP authentication
5. **Backup System** - App and database backups

## Important Notes
- **Linux Production**: Ensure all paths use ENV variables, not hardcoded Windows paths
- **Terminal**: Uses Node.js node-pty (not PHP) for stability
- **File Sync**: Host bind mount required for File Manager <-> Terminal sync

## Key ENV Variables
```
DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
JWT_SECRET
DOCKER_NETWORK
USER_APPS_PATH (container path)
USER_APPS_HOST_PATH (host path - for Linux compatibility)
```
