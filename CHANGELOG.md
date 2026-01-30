# LogicPanel - Change Log

## [1.3.0] - 2026-01-22

### 🗑️ Trash Bin Feature (New!)

Files are no longer permanently deleted by default. They go to a Trash Bin first.

#### Features:
- **Soft Delete**: Deleted items move to `.trash` folder in each service
- **Restore**: Right-click on trash items to restore to original location
- **Empty Trash**: Button to permanently delete all trash items
- **Permanent Delete Option**: Checkbox in delete modal for immediate permanent deletion
- **Metadata Storage**: Original path and deletion time saved for each trashed item

#### New API Endpoints:
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/services/{id}/files/trash` | List items in trash |
| POST | `/services/{id}/files/trash/restore` | Restore item from trash |
| DELETE | `/services/{id}/files/trash` | Empty trash |

---

### 🎨 Custom Confirmation Modal

- Replaced browser's native `confirm()` with custom modal
- Yellow warning checkbox for "Delete Permanently" option
- Styled delete button with danger color

---

### 📱 Mobile Responsive Design

File Manager now works beautifully on mobile devices:

- **Toolbar**: Horizontal scroll, icon-only buttons on small screens
- **Sidebar**: Horizontal scrollable app list on mobile
- **Table**: Hidden columns (Last Modified, Type, Perms) on mobile for compactness
- **Modals**: Full-width on mobile
- **Toast Notifications**: Full-width on mobile
- **Breakpoints**: 768px (tablet), 480px (mobile)

---

### 🔧 Other Improvements

1. **Breadcrumb Icon Spacing**: Added margin between icon and text
2. **No Emojis**: Replaced all emojis with Font Awesome icons
3. **Trash Button**: Added to toolbar for quick access to trash view
4. **Trash Context Menu**: Restore and Delete Permanently options

---

### 📁 Files Changed

| File | Type | Description |
|------|------|-------------|
| `templates/apps/files.php` | Modified | Trash UI, delete modal, mobile CSS |
| `src/Application/Controllers/FileController.php` | Modified | Trash methods: listTrash, restoreFromTrash, emptyTrash |
| `src/routes.php` | Modified | Added 3 new trash routes |

---

## [1.2.0] - 2026-01-22

### 🚀 Major Features & Improvements

#### 1. Toast Notification System
- Replaced all `alert()` and `confirm()` dialogs with elegant toast notifications
- 4 notification types: success (green), error (red), warning (yellow), info (blue)
- Auto-dismiss after 4 seconds with smooth slide-out animation
- Manual dismiss with close button
- Non-blocking UI experience

#### 2. Rename Functionality (Now Working!)
- **New Backend API**: Added `/services/{serviceId}/files/rename` endpoint
- **Parameters**: `oldPath` (full path) and `newName` (new filename only)
- **Modal Dialog**: Clean modal interface for entering new name
- **Validation**: Prevents invalid names, duplicate names, and path traversal attacks
- Works for both files and folders

#### 3. Enhanced Upload Functionality
- **Drag & Drop Support**: Drag files directly onto the upload area
- **Multiple File Upload**: Upload multiple files at once
- **Progress Notifications**: Toast notifications for upload status
- **Dynamic Service ID**: Uses URL-based service ID for reliability

#### 4. API Call Improvements
- `apiCall()` now dynamically reads service ID from URL
- No more reliance on PHP-injected `serviceId` variable
- Consistent behavior across all file operations

---

### 🎨 UI/UX Improvements

1. **Context Menu Behavior**
   - All context menus now auto-close after action
   - Clicking outside closes both file and empty space menus
   - `hideContextMenus()` utility function added

2. **Modal Improvements**
   - Input fields cleared when opening modals
   - Rename modal with auto-focus and text selection

3. **Toast Notifications**
   - CSS animations for smooth entry/exit
   - Icons for each notification type
   - Fixed position in top-right corner

4. **Keyboard Shortcuts** (New!)
   - `Ctrl+C` - Copy selected items
   - `Ctrl+X` - Cut selected items
   - `Ctrl+V` - Paste items
   - `Ctrl+A` - Select all items
   - `Delete` - Delete selected items
   - `F2` - Rename selected item
   - `F5` - Refresh current directory
   - `Escape` - Clear selection and close menus
   - `Backspace` - Go up one level

5. **Enhanced Status Bar**
   - Shows total folders and files count
   - Shows clipboard status (items in clipboard)
   - Shows selection count

6. **Breadcrumb Navigation Fixed**
   - Home button now properly shows app selection screen
   - App Root icon changed from server to folder
   - All breadcrumb paths now clickable and working

---

### 📁 Files Changed

| File | Type | Description |
|------|------|-------------|
| `templates/apps/files.php` | Modified | Toast system, rename modal, upload improvements, keyboard shortcuts |
| `src/Application/Controllers/FileController.php` | Modified | Added `rename()` method |
| `src/routes.php` | Modified | Added `/files/rename` route |

---

### 🐛 Bug Fixes

1. **Duplicate Functions Removed**: Removed duplicate `downloadItem()` and `showNotification()` functions
2. **Service ID Consistency**: `apiCall()` now uses URL parameter instead of potentially stale PHP variable
3. **Context Menu Z-Index**: Fixed context menu appearing behind modals
4. **Empty Context Menu Not Closing**: Added listener to close all context menus
5. **Breadcrumb Navigation**: Fixed Home and folder path clicks not working
6. **Copy/Paste Message**: Improved success message to not show "Failed: 0"

---

### 📋 New API Endpoint

#### POST `/api/services/{serviceId}/files/rename`

**Request Body:**
```json
{
    "oldPath": "/path/to/file.txt",
    "newName": "newfile.txt"
}
```

**Response:**
```json
{
    "message": "Renamed successfully",
    "oldPath": "/path/to/file.txt",
    "newPath": "/path/to/newfile.txt"
}
```

---

## [1.1.0] - 2026-01-22

### 🎨 File Manager Complete Redesign (cPanel-style)

#### UI/UX Changes

1. **Header Bar**
   - Background color changed to `#1E2127` (dark theme)
   - Brand text updated to "LogicPanel File Manager"
   - Added "Dashboard" link button to return to main dashboard

2. **Sidebar Application List**
   - Applications now displayed as tree items in left sidebar (like cPanel's folder tree)
   - Each application appears as a clickable "server" icon folder
   - Active application highlighted with visual indicator
   - Dynamic loading with API call including cache-busting timestamp
   - Clicking an app switches context and loads its files

3. **Main File View**
   - Professional table-based layout with columns: Name, Size, Last Modified, Type, Perms
   - Sortable display (folders first, then files alphabetically)
   - Checkbox selection for multi-file operations
   - Row highlighting on selection
   - Double-click to open files/folders

4. **Context Menus (Right-Click)**
   
   **File/Folder Context Menu:**
   - Open - Opens folder or file in editor
   - Edit - Opens file in code editor (new tab) [NEW]
   - Rename - Rename file/folder
   - Copy - Copy to clipboard
   - Move - Cut to clipboard
   - Paste - Paste from clipboard (shown when clipboard has items)
   - Download - Download file with proper auth
   - Extract - Extract archives (shown for .zip, .tar, .gz)
   - Delete - Delete selected items

   **Empty Space Context Menu:** [NEW]
   - New File - Create new file
   - New Folder - Create new folder
   - Paste - Paste from clipboard
   - Refresh - Reload current directory

5. **Breadcrumb Navigation**
   - "Home" link returns to global app list
   - "App Root" shows current application root
   - Clickable path segments for easy navigation

---

### 📝 Code Editor (New Feature)
Created a full-featured code editor page (`/apps/editor`).

**File:** `templates/apps/editor.php`

#### Features:
- **CodeMirror Integration** with Dracula dark theme
- **Syntax Highlighting** for:
  - JavaScript / JSON
  - PHP
  - HTML / CSS / XML
  - Python
  - Markdown
  - Plain Text / .env files
- **Keyboard Shortcuts**: Ctrl+S to save
- **Undo/Redo** buttons
- **Font Size** selector (12px - 18px)
- **Unsaved Changes Warning** before closing
- **Status Bar** showing cursor position and file type
- **Encoding Selector** (UTF-8, ISO-8859-1)

#### How It Works:
1. From File Manager, right-click a file → Edit
2. File opens in new browser tab
3. CodeMirror loads with syntax highlighting
4. Make changes, press Ctrl+S or click "Save Changes"
5. Changes saved via API

---

### 🔐 Authentication Improvements

**File:** `src/Application/Middleware/AuthMiddleware.php`

#### Changes:
- Added **fallback token authentication via query parameter**
- Now checks:
  1. First: `Authorization: Bearer <token>` header
  2. Fallback: `?token=<token>` query parameter
- This enables:
  - File downloads via direct URL navigation
  - Editor page loading with proper auth
  - Any direct link that needs authentication

---

### 🛠️ Dashboard Updates

**File:** `templates/dashboard/index.php`

#### Changes:
- "File Manager" tool card now opens `/apps/files` directly in new tab
- Removed popup modal for application selection
- Uses `target="_blank"` for new tab behavior

**File:** `public/assets/dashboard.js`

#### Changes:
- Updated `setupEventListeners()` to allow default link behavior for `target="_blank"` elements
- File Manager no longer intercepted by JavaScript

---

### 🗂️ Routing Updates

**File:** `index.php`

#### New Route:
```php
} elseif (strpos($path, 'apps/editor') === 0) {
    $title = 'Code Editor';
    $current_page = 'apps_editor';
    include 'templates/apps/editor.php';
}
```

---

### 📁 Files Changed

| File | Type | Description |
|------|------|-------------|
| `templates/apps/files.php` | Modified | Complete redesign with cPanel-style UI |
| `templates/apps/editor.php` | Created | New code editor with CodeMirror |
| `templates/dashboard/index.php` | Modified | File Manager link opens directly |
| `public/assets/dashboard.js` | Modified | Allow target="_blank" links |
| `src/Application/Middleware/AuthMiddleware.php` | Modified | Token fallback via query param |
| `index.php` | Modified | Added /apps/editor route |

---

### 🐛 Bug Fixes

1. **File Manager showing deleted apps**: Added cache-busting `?t=` parameter to API calls
2. **Download authorization error**: AuthMiddleware now accepts token from URL query param
3. **"Open" showing alert instead of editor**: Fixed `handleFileAction()` to use new editor
4. **Context menu not closing**: Added proper click handlers to hide menus

---

### 📋 Technical Notes

#### API Endpoints Used:
- `GET /api/services` - List all services/applications
- `GET /api/services/{id}/files?path=` - List files in directory
- `GET /api/services/{id}/files/read?path=` - Read file content
- `PUT /api/services/{id}/files` - Create/Update file
- `POST /api/services/{id}/files/mkdir` - Create folder
- `DELETE /api/services/{id}/files` - Delete files
- `GET /api/services/{id}/files/download?path=&token=` - Download file

#### JavaScript Functions Added:
- `initSidebar()` - Load apps in sidebar
- `switchApp(id)` - Switch between applications
- `loadGlobalRoot()` - [Removed] Was showing apps in table
- `showEmptyContextMenu(e)` - Handle right-click on empty space
- `editItem()` - Open file in editor
- `downloadItem()` - Download selected file
- `showNotification(msg)` - Simple notification helper

---

### 🎯 Summary

This update transforms the File Manager from a basic file browser into a professional, cPanel-like interface with:
- Multi-application support in sidebar
- Full-featured code editor with syntax highlighting
- Context menus for quick actions
- Proper authentication for all operations
- Clean, dark-themed professional design
