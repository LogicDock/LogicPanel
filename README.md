# LogicPanel - Professional Hosting Control Panel

LogicPanel is a modern, high-performance control panel designed for hosting **Node.js** and **Python** applications with ease. Built on top of Docker isolation, it provides a secure environment for developers to manage their services, databases, and files through an intuitive web interface.

## 🚀 One-Line Installation

Get your panel up and running in seconds on any clean Ubuntu/Debian/CentOS/AlmaLinux server.

```bash
bash <(curl -sSL https://raw.githubusercontent.com/LogicDock/LogicPanel/main/install.sh)
```

## ✨ Core Features

- **Docker-Powered Isolation**: Every application runs in its own secure container.
- **Dynamic Routing**: Built-in Nginx Reverse Proxy with automated **Let's Encrypt SSL**.
- **Multi-DB Support**: Instant provisioning for MySQL, PostgreSQL, and MongoDB.
- **Embedded Editor**: Professional file management with Monaco-based editor.
- **Root Terminal**: Docker-level terminal access for administrators.
- **WHMCS Ready**: Full integration for automated billing and provisioning.
- **Security First**: No hardcoded credentials, random DB secrets, and JWT-based API.

## 📡 Default Ports

LogicPanel operates on specific ports for administrative and user access behind the proxy:

- **Admin Panel**: Port `999`
- **User Panel**: Port `777`

## 🔒 Security Configuration

During installation, the script will prompt you for:
- **Panel Hostname** (e.g., `panel.yourdomain.cloud`)
- **Admin Username/Email**
- **Admin Password** (min 8 characters)

All database names, users, and internal secrets are **randomly generated** per installation to ensure maximum security.

## 🛠️ Uninstallation

To completely remove LogicPanel and all associated Docker data:

```bash
bash <(curl -sSL https://raw.githubusercontent.com/LogicDock/LogicPanel/main/uninstall.sh)
```

## 🤝 Support & Contribution

LogicPanel is a proprietary product by **LogicDock**. 

For professional support or feature inquiries, please visit our website:
🌐 [LogicDock.cloud](https://logicdock.cloud)  
📧 Email: `support@logicdock.cloud`

---

**Version**: 1.5.0  
**Status**: Beta (Stable)  
**Maintained by**: LogicDock Development Team
