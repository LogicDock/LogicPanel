# LogicPanel - Secure Shared Hosting Control Panel

A modern, secure control panel for hosting Node.js and Python applications in Docker containers with comprehensive database management, file editing, and terminal access.

## 🎯 Features

- **Multi-Language Support**: Host Node.js and Python applications
- **Docker Isolation**: Each app runs in a dedicated, secured container
- **Database Management**: Automated provisioning for MySQL, PostgreSQL, and MongoDB
- **File Manager**: Browser-based file editing with Monaco editor
- **Terminal Access**: Secure ttyd integration restricted to container scope
- **WHMCS Integration**: Full billing system integration
- **Security First**: Multi-layer isolation, encrypted credentials, audit logging
- **Resource Management**: CPU, memory, and disk limits per service
- **API-First**: RESTful API with JWT authentication

## 📋 Requirements

- PHP 8.2+
- Docker & Docker Compose
- MySQL/MariaDB
- Redis
- Composer
- Node.js 18+ (for DB Provisioner)

## 🚀 Quick Start

### 1. Clone and Setup

```bash
cd c:\xampp\htdocs\logicpanel
composer install
cp .env.example .env
```

### 2. Configure Environment

Edit `.env` and set:
- Database credentials
- JWT secret (generate with: `php -r "echo bin2hex(random_bytes(32));"`)
- Encryption key (generate with: `php -r "echo sodium_bin2hex(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));"`)
- Docker paths
- Database root passwords

### 3. Create Database

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE logicpanel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema
mysql -u root -p logicpanel < database/schema.sql
```

### 4. Start Docker Infrastructure

```bash
docker-compose up -d
```

This starts:
- MariaDB (internal network)
- PostgreSQL (internal network)
- MongoDB (internal network)
- Redis (caching & queues)
- DB Provisioner Service

### 5. Verify Installation

```bash
# Check containers
docker-compose ps

# Test DB Provisioner
curl http://localhost:3001/health
```

### 6. Access Panel

- **Web UI**: http://localhost/logicpanel
- **Default Login**: admin / admin123 (CHANGE THIS!)
- **Adminer** (dev only): http://localhost:8080

## 📁 Project Structure

```
logicpanel/
├── config/                 # Configuration files
│   └── settings.php       # Main settings
├── database/              # Database migrations
│   └── schema.sql         # Database schema
├── docker/                # Docker configurations
│   ├── db-provisioner/    # DB management microservice
│   ├── app-templates/     # User app templates
│   └── docker-compose.yml # Infrastructure
├── public/                # Public web root
│   ├── assets/           # Static assets
│   └── index.php         # Entry point
├── src/                   # Application code
│   ├── Application/      # Controllers, Middleware, Services
│   ├── Domain/           # Business logic & models
│   └── Infrastructure/   # Docker, DB, FileSystem
├── storage/              # Storage directory
│   ├── logs/            # Application logs
│   ├── uploads/         # File uploads
│   └── user-apps/       # User application files
├── templates/            # UI templates
│   ├── dashboard/       # Dashboard views
│   ├── layouts/         # Layout templates
│   └── partials/        # Reusable components
├── tests/               # Unit & integration tests
├── .env.example         # Environment template
├── composer.json        # PHP dependencies
└── README.md           # This file
```

## 🔒 Security Architecture

### Multi-Layer Isolation

1. **Container Boundaries**: Each user app in isolated Docker container
2. **DB User Restrictions**: Each user can only access their own databases
3. **Network Isolation**: Database containers on internal network only
4. **File System**: Read-only root filesystem, writable /app only

### Secrets Management

- ✅ All DB credentials encrypted with libsodium
- ✅ JWT tokens for API authentication
- ✅ API keys hashed before storage
- ✅ Environment variables for sensitive config
- ✅ Never expose root credentials to users

### Container Security

```yaml
security_opt:
  - no-new-privileges:true
  - seccomp=default.json
read_only: true
user: "1001:1001"
deploy:
  resources:
    limits:
      cpus: '0.5'
      memory: 512M
```

## 🗄️ Database Provisioning

The DB Provisioner service creates isolated databases with restricted users:

### MySQL Example
```sql
CREATE DATABASE user_1_db_1;
CREATE USER 'user_1_db_1'@'%' IDENTIFIED BY 'strong_random_password';
GRANT ALL PRIVILEGES ON user_1_db_1.* TO 'user_1_db_1'@'%';
```

### Verification
```bash
# User should only see their own database
mysql -u user_1_db_1 -p -e "SHOW DATABASES;"
# Output: information_schema, user_1_db_1
```

## 📡 API Documentation

### Authentication

```bash
# Login
POST /api/auth/login
{
  "email": "user@example.com",
  "password": "password"
}

# Response
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "...",
  "expires_in": 3600
}
```

### Service Management

```bash
# Create Service
POST /api/services
Authorization: Bearer {token}
{
  "name": "My App",
  "type": "nodejs",
  "domain": "myapp.example.com"
}

# Start Service
POST /api/services/{id}/start
Authorization: Bearer {token}

# Get Logs
GET /api/services/{id}/logs
Authorization: Bearer {token}
```

### Database Management

```bash
# Create Database
POST /api/services/{id}/databases
Authorization: Bearer {token}
{
  "type": "mysql"
}

# Response
{
  "id": 1,
  "name": "user_1_db_1",
  "user": "user_1_db_1",
  "password": "generated_password",
  "host": "mysql",
  "port": 3306
}
```

## 🔧 Development

### Running Tests

```bash
composer test
```

### Code Analysis

```bash
composer stan
```

### Docker Development

```bash
# View logs
docker-compose logs -f

# Restart service
docker-compose restart db-provisioner

# Shell into container
docker-compose exec mysql mysql -u root -p
```

## 🎨 UI Customization

The panel uses a cPanel-inspired design with:
- Node.js green color scheme (#3C873A)
- Collapsible tool sections
- Dark/Light theme toggle
- Responsive mobile design

Customize in `templates/layouts/main.php`

## 🔌 WHMCS Integration

### Module Installation

1. Copy `whmcs/modules/servers/logicpanel/` to WHMCS
2. Configure API credentials in WHMCS
3. Create server in WHMCS pointing to LogicPanel API

### API Endpoints

```bash
POST /api/whmcs/create-account
POST /api/whmcs/suspend-account
POST /api/whmcs/terminate-account
GET /api/whmcs/account-status
```

## 📊 Monitoring & Logging

### Application Logs
```bash
tail -f storage/logs/app.log
```

### Container Logs
```bash
docker-compose logs -f
```

### Audit Logs
All critical operations logged to `audit_logs` table

## 🚨 Troubleshooting

### Database Connection Failed
```bash
# Check database container
docker-compose ps mysql

# Test connection
docker-compose exec mysql mysql -u root -p
```

### Container Won't Start
```bash
# Check Docker daemon
docker info

# View container logs
docker logs logicpanel_mysql
```

### DB Provisioner Not Responding
```bash
# Check service
curl http://localhost:3001/health

# View logs
docker-compose logs db-provisioner
```

## 📝 TODO / Roadmap

- [x] Complete Slim API implementation
- [x] File Manager UI with cPanel-style design
- [x] Code Editor with CodeMirror integration
- [x] Trash Bin with restore functionality
- [x] Toast notification system
- [x] Mobile responsive design
- [x] Keyboard shortcuts
- [ ] Terminal integration with ttyd
- [ ] WHMCS module development
- [ ] Automated backups
- [ ] Resource usage graphs
- [ ] Email notifications
- [ ] Two-factor authentication
- [ ] API rate limiting dashboard
- [ ] Reseller management UI

## 📜 Change Log

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

## 🤝 Contributing

This is a private project. For issues or suggestions, contact the development team.

## 📄 License

Proprietary - All rights reserved

## 👥 Support

For support, email: support@logicpanel.local

---

**Version**: 1.3.0  
**Last Updated**: 2026-01-22  
**Status**: Beta
