# CampusDigs Kenya - Server Deployment Guide

## Overview
This guide explains how to deploy CampusDigs from localhost to your school server.

---

## Database Connection Strategy

### How It Works
The system automatically detects the environment and uses the correct database:

**LOCALHOST (Development)**
- Uses: `campus_digs` database
- Credentials: root / no password
- File: `includes/config.php` (default settings)

**SERVER (Production)**
- Uses: `ecommerce_2025A_monicah_lekupe` database
- Credentials: From `includes/db_cred.php`
- Detection: If `db_cred.php` exists, it's production

### Files That Connect to Database
All these files are already configured to use `includes/config.php`:
- ✅ `classes/*.php` - All class files
- ✅ `controllers/*.php` - All controller files
- ✅ `actions/*.php` - All action files
- ✅ `includes/core.php` - Core functions
- ✅ `view/*.php` - All view pages

**You don't need to modify any individual files!** They all include `config.php` which automatically uses the right database.

---

## Step-by-Step Deployment

### STEP 1: Prepare the Database

#### 1.1 Export from Localhost
```bash
# In phpMyAdmin localhost
1. Select 'campus_digs' database
2. Click 'Export' tab
3. Choose 'Quick' export method
4. Format: SQL
5. Click 'Go' to download campus_digs.sql
```

#### 1.2 Modify the SQL Dump
Open `campus_digs.sql` in a text editor and find/replace:
- Find: `campus_digs`
- Replace: `ecommerce_2025A_monicah_lekupe`

**Example:**
```sql
-- BEFORE:
CREATE DATABASE IF NOT EXISTS `campus_digs`;
USE `campus_digs`;

-- AFTER:
CREATE DATABASE IF NOT EXISTS `ecommerce_2025A_monicah_lekupe`;
USE `ecommerce_2025A_monicah_lekupe`;
```

Save the modified SQL file.

#### 1.3 Import to Server
```bash
# In server phpMyAdmin
1. Select 'ecommerce_2025A_monicah_lekupe' database (should already exist)
2. Click 'Import' tab
3. Choose the modified campus_digs.sql file
4. Click 'Go' to import
```

### STEP 2: Prepare Files for Upload

#### 2.1 Update Production URL
Edit `includes/config.php` line 123:
```php
// Change this to your actual server URL
define('BASE_URL', 'https://your-actual-server-url.com/campus_digs');
```

**Examples:**
- `https://webtech.com.ng/~monicah.lekupe/campus_digs`
- `https://cs.ashesi.edu.gh/~monicah.lekupe/campus_digs`
- `https://student-server.edu/monicah.lekupe/campus_digs`

#### 2.2 Verify db_cred.php
Make sure `includes/db_cred.php` contains:
```php
define('SERVER', 'localhost');
define('USERNAME', 'monicah.lekupe');
define('PASSWD', 'Amelia@2026');
define('DATABASE', 'ecommerce_2025A_monicah_lekupe');
```

### STEP 3: Upload Files to Server

#### What to Upload (ALL of these):
```
✅ actions/
✅ admin/
✅ assets/
✅ classes/
✅ controllers/
✅ css/
✅ images/
✅ includes/
   ✅ config.php
   ✅ core.php
   ✅ db_cred.php         ← IMPORTANT!
   ✅ header.php
   ✅ paystack_config.php
✅ js/
✅ uploads/
✅ view/
✅ dashboard_admin.php
✅ dashboard_landlord.php
✅ dashboard_student.php
✅ index.php
✅ login.php
✅ logout.php
✅ register.php
✅ .htaccess (if you have one)
```

#### What NOT to Upload:
```
❌ .git/
❌ .vscode/
❌ config/ (we deleted this folder)
❌ *.md files (DEPLOYMENT_GUIDE.md, README.md, etc.)
❌ .gitignore
❌ campus_digs.sql (database dump)
```

### STEP 4: Set Permissions on Server

Using FTP client or terminal:
```bash
# Make directories writable for uploads
chmod 755 uploads/
chmod 755 uploads/properties/
chmod 755 uploads/student_ids/
chmod 755 uploads/landlord_docs/

# Create logs directory if needed
mkdir logs
chmod 755 logs/
```

### STEP 5: Test the Deployment

#### 5.1 Test Database Connection
Visit: `https://your-server-url.com/campus_digs/`

**Expected:** Login page loads (means database connected)
**If error:** Check error logs or enable display_errors temporarily

#### 5.2 Test Login
- Try logging in as admin, student, or landlord
- Credentials from your localhost database should work

#### 5.3 Test Key Features
- ✅ Student registration
- ✅ Property browsing
- ✅ Booking creation
- ✅ Payment initialization
- ✅ AI Recommendations
- ✅ Reviews

---

## Troubleshooting

### Error: "Database connection failed"
**Solution:**
1. Check `includes/db_cred.php` exists on server
2. Verify credentials match server phpMyAdmin
3. Test database connection in phpMyAdmin

### Error: "Table doesn't exist"
**Solution:**
1. Make sure you imported the modified SQL dump
2. Check database name is `ecommerce_2025A_monicah_lekupe`
3. Re-import if needed

### Error: "BASE_URL incorrect" / Broken links/images
**Solution:**
1. Update `includes/config.php` line 123 with correct server URL
2. Clear browser cache
3. Check folder name matches URL path

### Payment not working
**Solution:**
For production, you need to:
1. Get live Paystack keys from https://paystack.com
2. Update `includes/config.php` lines 169-170:
```php
define('PAYSTACK_SECRET_KEY', 'sk_live_YOUR_LIVE_KEY');
define('PAYSTACK_PUBLIC_KEY', 'pk_live_YOUR_LIVE_KEY');
```

---

## Environment Detection

The system automatically detects the environment:

**Localhost Detection:**
```php
if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
    // Development mode
    - Shows all errors
    - Uses campus_digs database
    - Uses test Paystack keys
}
```

**Server Detection:**
```php
else {
    // Production mode
    - Hides errors (logs to file)
    - Uses db_cred.php credentials
    - Uses ecommerce_2025A_monicah_lekupe database
    - Should use live Paystack keys
}
```

---

## Database Credentials Summary

### Localhost
```
Server: localhost
Username: root
Password: (empty)
Database: campus_digs
```

### Production Server
```
Server: localhost
Username: monicah.lekupe
Password: Amelia@2026
Database: ecommerce_2025A_monicah_lekupe
File: includes/db_cred.php
```

---

## Files That Handle Database Connection

### Primary Configuration
- **includes/config.php** - Main config, auto-detects environment
- **includes/db_cred.php** - Server credentials (only on production)

### Classes (use global $conn from config.php)
- classes/user_class.php
- classes/property_class.php
- classes/booking_class.php
- classes/payment_class.php
- classes/wishlist_class.php

### Controllers (include config.php)
- controllers/user_controller.php
- controllers/property_controller.php
- controllers/booking_controller.php
- controllers/payment_controller.php
- controllers/review_controller.php
- controllers/recommendation_controller.php

### Actions (include config.php)
- All files in actions/ folder (30+ files)
- All include ../includes/config.php

**IMPORTANT:** You only need `includes/db_cred.php` on the server. All other files automatically use the correct database through `config.php`.

---

## Final Checklist Before Going Live

- [ ] Database imported to `ecommerce_2025A_monicah_lekupe`
- [ ] `includes/db_cred.php` uploaded with correct credentials
- [ ] `includes/config.php` updated with correct BASE_URL
- [ ] All project files uploaded to server
- [ ] Folder permissions set (755 for uploads/)
- [ ] Tested login functionality
- [ ] Tested property browsing
- [ ] Tested booking creation
- [ ] For live payments: Updated Paystack keys to live keys

---

## Support

If you encounter issues:
1. Check server error logs: `logs/error.log`
2. Temporarily enable error display in `config.php` line 22
3. Verify database connection in phpMyAdmin
4. Check file permissions on server

---

**Deployment Date:** _______________
**Server URL:** _______________
**Database Name:** ecommerce_2025A_monicah_lekupe
**Deployed By:** Monicah Lekupe
