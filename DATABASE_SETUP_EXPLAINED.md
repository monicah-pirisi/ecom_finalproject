# Database Connection Setup - Explained Simply

## Your Question Answered

### "Which files require to be connected to db_cred files?"

**SHORT ANSWER:** Only `includes/config.php` connects to `db_cred.php`. All other files automatically use the correct database through `config.php`.

**DETAILED ANSWER:**

```
includes/config.php (MAIN CONFIG)
    ↓
    Checks: Does db_cred.php exist?
    ↓
    YES → Use server database (ecommerce_2025A_monicah_lekupe)
    NO  → Use localhost database (campus_digs)
    ↓
    Creates $conn (database connection)
    ↓
    All other files include config.php and use $conn
```

### Files That Need Database Connection

**Answer:** ALL of these files need database access, but they ALL get it through `config.php`:

1. **Classes** (25 files)
   - `classes/user_class.php`
   - `classes/property_class.php`
   - `classes/booking_class.php`
   - etc...

2. **Controllers** (6 files)
   - `controllers/user_controller.php`
   - `controllers/property_controller.php`
   - `controllers/booking_controller.php`
   - etc...

3. **Actions** (30+ files)
   - `actions/admin_properties_action.php`
   - `actions/student_bookings_action.php`
   - `actions/paystack_initialize.php`
   - etc...

4. **Views** (50+ files)
   - `view/all_properties.php`
   - `dashboard_student.php`
   - etc...

**BUT** - They all have this at the top:
```php
require_once '../includes/config.php';
// Now they have access to $conn
```

### You DON'T Need To Modify Any Individual Files!

✅ The connection is automatic
✅ Environment detection is automatic
✅ Database selection is automatic

---

## How The System Works

### On LOCALHOST (Your Computer)

```php
// Step 1: Someone visits the site
// Step 2: config.php checks environment
$serverName = $_SERVER['SERVER_NAME']; // "localhost"

// Step 3: Checks if db_cred.php exists
if (file_exists('includes/db_cred.php')) {
    // File doesn't exist on localhost
} else {
    // USE DEFAULTS
    DB_HOST = 'localhost'
    DB_USER = 'root'
    DB_PASS = ''
    DB_NAME = 'campus_digs'  ← Your localhost database
}

// Step 4: Connect to database
$conn = new mysqli('localhost', 'root', '', 'campus_digs');

// Step 5: All files use this $conn
```

### On SERVER (School Server)

```php
// Step 1: Someone visits the site
// Step 2: config.php checks environment
$serverName = $_SERVER['SERVER_NAME']; // "webtech.com.ng"

// Step 3: Checks if db_cred.php exists
if (file_exists('includes/db_cred.php')) {
    // File EXISTS on server
    // Load it
    require_once 'includes/db_cred.php';

    // USE SERVER CREDENTIALS
    DB_HOST = SERVER     // 'localhost'
    DB_USER = USERNAME   // 'monicah.lekupe'
    DB_PASS = PASSWD     // 'Amelia@2026'
    DB_NAME = DATABASE   // 'ecommerce_2025A_monicah_lekupe'
}

// Step 4: Connect to database
$conn = new mysqli('localhost', 'monicah.lekupe', 'Amelia@2026', 'ecommerce_2025A_monicah_lekupe');

// Step 5: All files use this $conn
```

---

## Database Name Issue - Solution

### Your Concern:
> "In my localhost phpmyadmin, the name of the database is campus_digs...
> In the server phpmyadmin the name should be ecommerce_2025A_monicah_lekupe
> How should I go about this?"

### The Solution (Already Implemented):

#### Option 1: Import SQL Dump (RECOMMENDED)

1. **Export from localhost:**
   - phpMyAdmin → campus_digs → Export → SQL

2. **Modify the SQL file:**
   ```sql
   -- Open campus_digs.sql in Notepad
   -- Find and Replace:

   FIND: campus_digs
   REPLACE: ecommerce_2025A_monicah_lekupe

   -- Save file
   ```

3. **Import to server:**
   - Server phpMyAdmin → ecommerce_2025A_monicah_lekupe → Import → campus_digs.sql
   - Click Go

4. **Done!**
   - All tables copied
   - All data copied
   - Database name changed to ecommerce_2025A_monicah_lekupe
   - Your code automatically uses the correct database

#### Option 2: Manual Table Copy (NOT RECOMMENDED)

Too slow, error-prone. Use Option 1.

---

## File Structure for Deployment

```
YOUR LOCALHOST:
campus_digs/
├── includes/
│   ├── config.php          ← Main config
│   └── db_cred.php         ← Server credentials (OPTIONAL locally)
└── ... other files

YOUR SERVER:
campus_digs/  (or public_html/campus_digs/)
├── includes/
│   ├── config.php          ← Main config
│   └── db_cred.php         ← Server credentials (REQUIRED!)
└── ... other files
```

### The Magic:

**Localhost:**
- `db_cred.php` = Doesn't exist (or you can delete it)
- System uses: `campus_digs` database

**Server:**
- `db_cred.php` = EXISTS with server credentials
- System uses: `ecommerce_2025A_monicah_lekupe` database

---

## Testing Database Connection

After deployment, visit:
```
https://your-server-url.com/campus_digs/test_db_connection.php
```

This will show:
- ✅ Which database it's connecting to
- ✅ If connection is successful
- ✅ If db_cred.php is found
- ✅ Environment detection results

**IMPORTANT:** Delete `test_db_connection.php` after testing!

---

## Common Mistakes to Avoid

❌ **WRONG:** Editing all PHP files to change database name
✅ **RIGHT:** Just upload db_cred.php to server

❌ **WRONG:** Hardcoding database name in multiple files
✅ **RIGHT:** Let config.php handle it automatically

❌ **WRONG:** Using campus_digs database name on server
✅ **RIGHT:** Import modified SQL dump with correct name

❌ **WRONG:** Deleting db_cred.php from localhost
✅ **RIGHT:** It's okay to keep it or delete it locally (won't affect localhost)

---

## Quick Reference

### Localhost Setup
```
Database: campus_digs
File needed: includes/config.php only
db_cred.php: Optional (can delete)
```

### Server Setup
```
Database: ecommerce_2025A_monicah_lekupe
Files needed:
  - includes/config.php (auto-uploaded with all files)
  - includes/db_cred.php (MUST upload this!)
```

### Database Credentials

**Localhost:**
```
Host: localhost
User: root
Pass: (empty)
DB: campus_digs
```

**Server:**
```
Host: localhost
User: monicah.lekupe
Pass: Amelia@2026
DB: ecommerce_2025A_monicah_lekupe
```

---

## Summary

1. ✅ **NO files need manual editing** for database connection
2. ✅ **System auto-detects** environment (localhost vs server)
3. ✅ **Just upload db_cred.php** to server
4. ✅ **Import modified SQL dump** to server database
5. ✅ **Test connection** with test_db_connection.php
6. ✅ **Delete test file** after confirming it works

**That's it!** Your application will automatically use:
- `campus_digs` on localhost
- `ecommerce_2025A_monicah_lekupe` on server

No errors, no manual changes needed! 🎉
