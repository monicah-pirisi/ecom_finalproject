# Campus Digs - Server Setup Instructions

## After Pulling from GitHub

### 1. Create Configuration Files

The following files are **NOT** included in the repository for security reasons. You need to create them manually:

#### Database Configuration
Copy the template and update with your credentials:
```bash
cp includes/db_cred.TEMPLATE.php includes/db_cred.php
```

Then edit `includes/db_cred.php` and update:
- `SERVER` - Your database host (usually 'localhost')
- `USERNAME` - Your database username
- `PASSWD` - Your database password
- `DATABASE` - Your database name

#### Paystack Configuration
Copy the template and update with your API keys:
```bash
cp includes/paystack_config.TEMPLATE.php includes/paystack_config.php
```

Then edit `includes/paystack_config.php` and update:
- `PAYSTACK_SECRET_KEY` - Your Paystack secret key
- `PAYSTACK_PUBLIC_KEY` - Your Paystack public key

**For Production:** Use your LIVE keys from Paystack dashboard, not test keys.

### 2. Create Upload Directories

Create the necessary upload directories with proper permissions:
```bash
mkdir -p uploads/properties
chmod 755 uploads
chmod 755 uploads/properties
```

### 3. File Permissions

Ensure proper file permissions:
```bash
# Make files readable
chmod 644 includes/db_cred.php
chmod 644 includes/paystack_config.php

# Ensure upload directory is writable by web server
chown -R www-data:www-data uploads/
```

### 4. Database Setup

- Import your database schema
- Update database credentials in `includes/db_cred.php`

### 5. Test the Application

- Access the site in your browser
- Test database connection
- Test file uploads
- Test payment integration

## Security Checklist

- [ ] Database credentials are NOT in version control
- [ ] Paystack API keys are NOT in version control
- [ ] Upload directory has proper permissions
- [ ] Using LIVE Paystack keys for production
- [ ] SSL certificate is installed (HTTPS)
- [ ] Error reporting is disabled in production
