# LogicPanel

**Node.js Application Hosting Control Panel**

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/logicdock/logicpanel)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Docker](https://img.shields.io/badge/docker-required-blue.svg)](https://docker.com)

---

## Quick Install

```bash
curl -sL https://raw.githubusercontent.com/LogicDock/LogicPanel/main/bootstrap.sh | sudo sh
```

That's it! The installer will guide you through the setup process.

---

## Features

### Panel Features
- **File Manager** - Upload, edit, manage application files
- **Web Terminal** - SSH access directly in browser
- **Git Deploy** - One-click deployment from GitHub/GitLab
- **Database Manager** - MySQL/MariaDB management
- **Resource Monitoring** - CPU, RAM, Disk usage in real-time
- **SSL Certificates** - Auto-provisioned Let's Encrypt
- **Package Management** - Resource-based hosting packages

### Installer Features
- **One-Click Deployment** - Single command installs everything
- **Multi-Distro Support** - Ubuntu, Debian, CentOS, RHEL, Fedora, Arch, Alpine, openSUSE
- **Auto Docker Setup** - Installs Docker if not present, skips if already installed
- **N8N Compatible** - Works alongside DockerN8N instances

---

## Requirements

| Requirement | Minimum |
|-------------|---------|
| **OS** | Any modern Linux distribution |
| **RAM** | 2 GB |
| **Disk** | 10 GB |
| **Ports** | 80, 443 (must be free) |
| **Domain** | Must point to server IP before installation |

---

## Installation

### Step 1: Point Your Domain

Create an A record pointing your domain to your server IP:
```
panel.yourdomain.com -> YOUR_SERVER_IP
```

### Step 2: Run Installation

```bash
curl -sL https://raw.githubusercontent.com/logicdock/logicpanel/main/install.sh | sudo bash
```

### Step 3: Follow the Prompts

The installer will ask for:
- **Panel Domain** - e.g., `panel.yourdomain.com`
- **Admin Email** - For SSL certificates and login
- **Admin Name** - Display name
- **Admin Password** - Leave empty for auto-generate

### Installation Steps

```
Step 1/6: Docker Installation
Step 2/6: Nginx Proxy & SSL Setup
Step 3/6: Panel Configuration
Step 4/6: Deploying LogicPanel
Step 5/6: Creating CLI Commands
Step 6/6: Installation Complete!
```

---

## CLI Commands

After installation, you have access to CLI commands:

### Panel Management

```bash
logicpanel start       # Start LogicPanel
logicpanel stop        # Stop LogicPanel
logicpanel restart     # Restart LogicPanel
logicpanel logs        # View logs
logicpanel status      # Show container status
logicpanel update      # Pull latest and restart
logicpanel credentials # Show all credentials
```

### WHMCS Credentials

```bash
whmcs show             # Display current API credentials
whmcs generate new     # Generate new API key & secret
```

---

## WHMCS Integration

LogicPanel includes a WHMCS provisioning module for automated Node.js hosting.

### Install WHMCS Module

Copy the `whmcs-module/logicpanel` folder to your WHMCS:
```
whmcs/modules/servers/logicpanel/
```

### Get License (Required for WHMCS)

A license is required to use the WHMCS module:
- **Purchase**: https://logicdock.cloud/logicpanel

### Server Configuration

1. Go to **Setup -> Products/Services -> Servers**
2. Click **Add New Server**
3. Configure:

| Field | Value |
|-------|-------|
| Name | LogicPanel Server |
| Module | LogicPanel - Node.js Hosting |
| Hostname | `panel.yourdomain.com` |
| Secure | Yes |
| Username | *(leave empty)* |
| Password | `[API Secret]` |
| Access Hash | `[API Key]` |

### Get API Credentials

```bash
whmcs show
```

---

## Directory Structure

```
/opt/logicpanel/
â”œâ”€â”€ docker-compose.yml    # Container definitions
â””â”€â”€ .env                  # Configuration & credentials
```

---

## Updates

```bash
logicpanel update
```

---

## N8N Compatibility

This panel is **fully compatible** with DockerN8N module:

- Shares the same `nginx-proxy_web` network
- Uses the same Nginx Proxy & Let's Encrypt containers
- No port conflicts
- N8N and Node.js apps run side by side

---

## Support

- **Documentation**: https://docs.logicdock.cloud
- **Support**: support@logicdock.cloud

---

## License

LogicPanel is open source under the MIT License.

WHMCS module requires a commercial license - https://logicdock.cloud/logicpanel

---

Made with love by LogicDock - https://logicdock.cloud
