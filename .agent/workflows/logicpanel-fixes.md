---
description: LogicPanel Comprehensive Fix Plan - All identified issues and solutions
---

# LogicPanel - Comprehensive Fix Plan

**IMPORTANT: Read this entire file before making ANY changes!**

## Project Overview

LogicPanel is a Node.js hosting control panel similar to cPanel but for Node.js apps. It integrates with WHMCS for billing/provisioning.

## Project Location
- **Repo**: `c:\xampp\htdocs\logicpanel-repo`
- **Panel Code**: `logicpanel/` folder (PHP backend + templates)
- **WHMCS Module**: `whmcs-module/logicpanel/logicpanel.php`
- **Live URL**: https://logicpanel.logicdock.cloud
- **GitHub**: https://github.com/LogicDock/LogicPanel

## Architecture

```
logicpanel/
├── config/routes.php          # All routes defined here
├── public/index.php            # Entry point
├── src/
│   ├── Controllers/
│   │   ├── ApiController.php   # WHMCS API endpoints (/api/v1/...)
│   │   ├── AuthController.php  # Login, SSO
│   │   ├── DashboardController.php
│   │   └── BaseController.php
│   ├── Models/                 # Eloquent models
│   ├── Services/
│   │   └── DockerService.php   # Docker API communication
│   └── Middleware/
├── templates/                  # PHP templates (NOT Twig!)
│   ├── layouts/main.php        # Main layout with sidebar
│   ├── dashboard/index.php     # User dashboard/tools
│   ├── services/show.php       # Service details (BROKEN - uses Tailwind)
│   ├── domains/index.php       # Domain list
│   ├── databases/index.php     # Database management
│   └── admin/                  # Admin panel templates
└── database/schema.sql         # DB structure
```

## VPS Update Commands
```bash
cd /opt/logicpanel
docker compose pull
docker compose up -d --force-recreate
```

---

## ✅ ALREADY FIXED (DO NOT TOUCH)

1. **SSO URL** - Fixed from `/public/sso/{token}` to `/sso/{token}`
   - File: `src/Controllers/ApiController.php` line 364-365

2. **Package Endpoint** - Fixed from `/api/v1/packages` to `/api/packages`
   - File: WHMCS module `logicpanel.php` line 470

3. **CreateAccount API** - Added package lookup, error retry, graceful Docker failure
   - File: `src/Controllers/ApiController.php` createAccount method

4. **Docker Ping Disabled** - Health check doesn't call docker->ping()
   - File: `src/Controllers/ApiController.php` health method

**DO NOT MODIFY THESE FIXES!**

---

## 🔴 Issues to Fix (Priority Order)

### Critical - Must Fix

1. **Service Details Page - Broken Design**
   - File: `templates/services/show.php`
   - Problem: Uses Tailwind CSS classes (`grid-cols-2`, `sm:flex-row`, etc.) but panel doesn't have Tailwind
   - Solution: REWRITE entire file with custom CSS matching panel's design system
   - Reference: Look at `templates/dashboard/index.php` for correct CSS approach

2. **Docker Socket Permission** (VPS side)
   - Problem: Container creation fails - "Failed to create container"
   - VPS Command: `chmod 666 /var/run/docker.sock`
   - This is a VPS config issue, not code issue

3. **Username/Password Login Not Working**
   - File: `src/Controllers/AuthController.php`
   - Problem: Users created via WHMCS have random password, can't login directly
   - Check how password is hashed/verified in login() method

4. **Missing Template: domains/show.php**
   - File: `templates/domains/show.php` (NEED TO CREATE)
   - Error: "Template not found: domains/show"
   - Used when clicking gear icon on domain list

### Medium - UI/UX Issues

5. **Bengali Text in Database Page**
   - File: `templates/databases/index.php` Line 82
   - Text: `প্রতি service এ ১টা database তৈরি করতে পারবেন`
   - Replace with: `Each service can have 1 database`

6. **Service Name in Tool Names**
   - File: `templates/dashboard/index.php` Lines 127, 133
   - Current: `<?= htmlspecialchars($service->name) ?> Terminal`
   - Should be just: `Terminal` and `Git Deploy`

7. **Domains Page - No Add Domain Button**
   - File: `templates/domains/index.php`
   - Need: Add "Add Domain" button with modal form

### Low - Enhancements

8. **Admin Panel - User Management**
9. **Admin Panel - Service Management**
10. **API Keys Page - Modern Modals**

---

## Design System (IMPORTANT!)

**The panel uses CUSTOM CSS, NOT Tailwind!**

CSS file: Defined in `templates/layouts/main.php`

Common classes:
```css
.card                 /* Card container */
.card-header          /* Card header */
.card-body            /* Card body */
.btn, .btn-primary    /* Buttons */
.form-control         /* Input fields */
.table                /* Tables */
.badge, .badge-success /* Badges */
```

CSS Variables:
```css
--primary             /* Green color */
--bg-card             /* Card background */
--border-color        /* Border color */
--text-primary        /* Primary text */
--text-secondary      /* Secondary text */
--text-muted          /* Muted text */
```

**NEVER use Tailwind classes like `grid-cols-2`, `sm:flex-row`, `p-4`, etc.**

---

## API Credentials (for testing)

- API Key: `lp_ef60b8b107792105470af827086fadae`
- API Secret: `5ecb028559eb653b1de61d6560843134c467b0647c198a38eae9aa2b3c6e487d`

---

## Testing Checklist

After fixes:
1. [ ] SSO Login from WHMCS client area
2. [ ] Service Details page loads properly
3. [ ] Domain settings page loads (gear icon)
4. [ ] Add new domain works
5. [ ] Database page shows English text

---

## WARNINGS

1. **DO NOT remove or modify** the Docker ping disable in ApiController health()
2. **DO NOT change** package endpoint from `/api/packages` 
3. **DO NOT change** SSO URL generation
4. **ALWAYS use** custom CSS classes, never Tailwind
5. **ALWAYS test** locally before pushing to GitHub

---

// turbo-all
