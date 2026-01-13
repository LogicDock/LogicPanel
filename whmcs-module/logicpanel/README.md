# LogicPanel WHMCS Module

Node.js Application Hosting provisioning module for WHMCS.

## Installation

1. Copy the `logicpanel` folder to `modules/servers/` in your WHMCS installation
2. Go to **Setup > Products/Services > Servers**
3. Add a new server with:
   - **Module**: LogicPanel
   - **Hostname**: Your LogicPanel server (e.g., `panel.yourdomain.com`)
   - **Access Hash**: Your API Key from LogicPanel
   - **Password**: Your API Secret from LogicPanel

## Server Configuration

| Field | Description |
|-------|-------------|
| Hostname | LogicPanel server URL (without protocol) |
| Secure | Check if using HTTPS |
| Access Hash | API Key from LogicPanel > Admin > Settings > API |
| Password | API Secret from LogicPanel |

## Product Configuration

When creating a product:

1. Go to **Setup > Products/Services > Products/Services**
2. Create new product or edit existing
3. Go to **Module Settings** tab
4. Select **LogicPanel** as module
5. Select server
6. Configure:
   - **Hosting Package**: Select from packages defined in LogicPanel
   - **Node.js Version**: Default Node.js version
   - **Application Port**: Default port (3000)

## Features

- ✅ Auto-provisioning on order activation
- ✅ Suspend/Unsuspend with container stop/start
- ✅ Terminate with full cleanup
- ✅ SSO (Single Sign-On) login to panel
- ✅ Client area with service status
- ✅ Package-based resource allocation

## API Endpoints Used

| Action | Endpoint |
|--------|----------|
| Create | `POST /api/v1/account/create` |
| Suspend | `POST /api/v1/account/suspend` |
| Unsuspend | `POST /api/v1/account/unsuspend` |
| Terminate | `POST /api/v1/account/terminate` |
| SSO | `POST /api/v1/sso/generate` |
| Health | `GET /api/v1/health` |
| Packages | `GET /api/packages` |

## Support

For support, contact: support@logicdock.com
