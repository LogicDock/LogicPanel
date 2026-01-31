# LogicPanel - Professional Hosting Control Panel

<p align="center">
  <img src="https://img.shields.io/badge/Version-2.0.0-blue" alt="Version">
  <img src="https://img.shields.io/badge/Status-Stable-green" alt="Status">
  <img src="https://img.shields.io/badge/License-Proprietary-red" alt="License">
  <img src="https://img.shields.io/badge/Docker-Required-blue" alt="Docker">
</p>

LogicPanel is a modern, high-performance control panel designed for hosting **Node.js** and **Python** applications with ease. Built on top of Docker isolation, it provides a secure environment for developers to manage their services, databases, and files through an intuitive web interface.

## 🚀 One-Line Installation

Get your panel up and running in seconds on any clean Linux server.

```bash
curl -sSL https://raw.githubusercontent.com/LogicDock/LogicPanel/main/install.sh | bash
```

### Supported Operating Systems
- Ubuntu 20.04+ / Debian 11+
- CentOS 8+ / Rocky Linux 8+ / AlmaLinux 8+
- Fedora 36+
- Arch Linux

## 💻 System Requirements

| Resource | Minimum | Recommended |
|----------|---------|-------------|
| **CPU** | 2 Cores | 4 Cores |
| **RAM** | 2 GB | 4 GB |
| **Storage** | 20 GB SSD | 40 GB+ SSD |
| **Network** | Public IPv4 | Public IPv4 + IPv6 |

## ✨ Core Features

- 🐳 **Docker-Powered Isolation**: Every application runs in its own secure container
- 🔐 **Automated SSL**: Built-in Let's Encrypt with auto-renewal
- 🗄️ **Multi-DB Support**: MySQL, PostgreSQL, and MongoDB provisioning
- 📝 **Monaco Editor**: Professional in-browser code editor
- 🖥️ **Root Terminal**: Docker-level terminal access for administrators
- 💳 **WHMCS Ready**: Full integration for automated billing
- 🔒 **Security First**: Random secrets, JWT auth, no hardcoded credentials

## 📡 Access Ports

| Panel | Port | Description |
|-------|------|-------------|
| **Master Panel** | `999` | Admin/Reseller Dashboard |
| **User Panel** | `777` | Customer Dashboard |

After installation, access your panels at:
- `https://your-domain.com:999` (Admin)
- `https://your-domain.com:777` (User)

## 🔧 What Gets Installed

- LogicPanel Application Container
- Terminal Gateway (WebSocket)
- MariaDB 11.2 (Mother Database)
- PostgreSQL 16
- MongoDB 7.0
- Redis 7
- Database Provisioner Service
- Nginx Reverse Proxy with SSL

## 🔄 Updates

Update your existing installation without losing data:

```bash
curl -sSL https://raw.githubusercontent.com/LogicDock/LogicPanel/main/update.sh | bash
```

## 🛠️ Uninstallation

To completely remove LogicPanel and all associated data:

```bash
curl -sSL https://raw.githubusercontent.com/LogicDock/LogicPanel/main/uninstall.sh | bash
```

> ⚠️ **Warning**: This will permanently delete all user data and databases!

## 📖 Documentation

For detailed documentation, visit: [docs.logicdock.cloud](https://docs.logicdock.cloud)

## 🐛 Bug Reports

Found a bug? Please open an issue on GitHub:
[github.com/LogicDock/LogicPanel/issues](https://github.com/LogicDock/LogicPanel/issues)

## 📄 License

LogicPanel is **proprietary software** by LogicDock.

- ✅ Free to use for personal and commercial projects
- ✅ Free updates and bug fixes
- ❌ Source code modification prohibited without permission
- ❌ Redistribution prohibited

See [LICENSE](LICENSE) for full terms.

## 🤝 Support

For professional support or feature inquiries:

- 🌐 Website: [LogicDock.cloud](https://logicdock.cloud)
- 📧 Email: support@logicdock.cloud
- 💬 Discord: [Join our community](https://discord.gg/logicdock)

---

<p align="center">
  <b>Made with ❤️ by LogicDock</b><br>
  <i>Simplifying hosting, one panel at a time.</i>
</p>
