# CampusDigs Kenya - Production Deployment Checklist

**Version:** 1.0
**Date:** 2025-11-28
**Status:** Ready for Production Deployment

---

## Pre-Deployment Checklist

### 1. Database Configuration

- [ ] **Import database schema**
  - Import `database/campus_digs.sql` to your production database
  - Verify all tables were created successfully:
    - users, properties, bookings, payments, payment_installments, reviews, wishlists

- [ ] **Configure database credentials**

  **Option A: Environment Variables (Recommended)**
  - Set these environment variables in your hosting control panel or `.htaccess`:
    ```
    SetEnv DB_HOST_PROD "your_database_host"
    SetEnv DB_USER_PROD "your_database_user"
    SetEnv DB_PASS_PROD "your_secure_password"
    SetEnv DB_NAME_PROD "campus_digs"
    ```

  **Option B: Direct Configuration**
  - Edit `includes/config.php` lines 44-47
  - Replace placeholder values with actual production credentials
  - **WARNING:** Not recommended for security reasons

- [ ] **Test database connection**
  - Access your site
  - Verify "Service temporarily unavailable" is NOT displayed
  - Check error logs for connection issues

---

### 2. Paystack Payment Gateway Configuration

- [ ] **Obtain Live API Keys**
  - Login to your Paystack dashboard (https://dashboard.paystack.com)
  - Navigate to Settings → API Keys & Webhooks
  - Copy your **Live Secret Key** (starts with `sk_live_`)
  - Copy your **Live Public Key** (starts with `pk_live_`)

- [ ] **Configure Paystack Keys**

  **Option A: Environment Variables (Recommended)**
  - Set these environment variables:
    ```
    SetEnv PAYSTACK_SECRET_KEY_LIVE "sk_live_xxxxxxxxxxxxx"
    SetEnv PAYSTACK_PUBLIC_KEY_LIVE "pk_live_xxxxxxxxxxxxx"
    ```

  **Option B: Direct Configuration**
  - Edit `includes/paystack_config.php` lines 17 and 24
  - Replace test keys with live keys
  - **WARNING:** Ensure this file is NOT publicly accessible

- [ ] **Set Paystack Webhook URL**
  - In Paystack dashboard, go to Settings → Webhooks
  - Add webhook URL: `https://yourdomain.com/actions/paystack_webhook.php`
  - Select events: `charge.success`, `charge.failed`

- [ ] **Test Payment Flow**
  - Create a test booking
  - Complete payment with small amount (KES 10)
  - Verify payment records in database
  - Check booking status updates correctly

---

### 3. File & Directory Permissions

- [ ] **Set correct permissions**
  ```bash
  # Directories should be 755
  find /path/to/campus_digs -type d -exec chmod 755 {} \;

  # PHP files should be 644
  find /path/to/campus_digs -type f -name "*.php" -exec chmod 644 {} \;

  # Config files should be 600 (more restrictive)
  chmod 600 /path/to/campus_digs/includes/config.php
  chmod 600 /path/to/campus_digs/includes/paystack_config.php
  ```

- [ ] **Create logs directory** (if not exists)
  ```bash
  mkdir -p /path/to/campus_digs/logs
  chmod 755 /path/to/campus_digs/logs
  ```

- [ ] **Create uploads directory permissions**
  ```bash
  chmod 755 /path/to/campus_digs/uploads
  chmod 755 /path/to/campus_digs/uploads/properties
  ```

---

### 4. Security Configuration

- [ ] **Verify error reporting is OFF**
  - Check `includes/config.php` line 22: `ini_set('display_errors', 0);`
  - Confirm errors are logged, not displayed

- [ ] **Enable HTTPS**
  - Install SSL certificate (Let's Encrypt recommended)
  - Force HTTPS redirect in `.htaccess`:
    ```apache
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    ```

- [ ] **Review .htaccess security headers**
  - Add security headers to `.htaccess`:
    ```apache
    # Security Headers
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-Content-Type-Options "nosniff"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    ```

- [ ] **Protect sensitive files**
  - Add to `.htaccess`:
    ```apache
    # Deny access to configuration files
    <Files "config.php">
        Require all denied
    </Files>
    <Files "paystack_config.php">
        Require all denied
    </Files>
    ```

- [ ] **Verify CSRF protection**
  - Test forms require CSRF token
  - Test API endpoints validate tokens

---

### 5. Email Notifications (Optional - TODO)

**Note:** Email functionality is NOT YET IMPLEMENTED. These are placeholders for future implementation.

- [ ] **Configure SMTP settings**
  - Set up email service (Gmail, SendGrid, Mailgun, etc.)
  - Add SMTP configuration to `includes/config.php`

- [ ] **Implement email functions**
  - Booking confirmation emails
  - Payment receipt emails
  - Property approval notifications
  - User registration emails

---

### 6. Performance Optimization

- [ ] **Enable PHP OPcache**
  - Check `php.ini` for:
    ```ini
    opcache.enable=1
    opcache.memory_consumption=128
    opcache.interned_strings_buffer=8
    opcache.max_accelerated_files=10000
    ```

- [ ] **Enable GZIP compression**
  - Add to `.htaccess`:
    ```apache
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
    </IfModule>
    ```

- [ ] **Set browser caching**
  - Add to `.htaccess`:
    ```apache
    <IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType image/jpg "access plus 1 year"
        ExpiresByType image/jpeg "access plus 1 year"
        ExpiresByType image/png "access plus 1 year"
        ExpiresByType text/css "access plus 1 month"
        ExpiresByType application/javascript "access plus 1 month"
    </IfModule>
    ```

---

### 7. Testing & Verification

- [ ] **Test user registration**
  - Student registration
  - Landlord registration
  - Admin login

- [ ] **Test property listing**
  - Landlord can create property
  - Properties display correctly
  - Image uploads work
  - Search functionality works

- [ ] **Test booking flow**
  - Student can create booking
  - Landlord receives notification
  - Landlord can approve/reject booking
  - Booking status updates correctly

- [ ] **Test payment flow** (CRITICAL)
  - Student initiates payment
  - Redirects to Paystack gateway
  - Payment processes successfully
  - Returns to callback page
  - Payment recorded in database
  - Booking status updates to "paid"

- [ ] **Test admin panel**
  - Admin can view users
  - Admin can manage properties
  - Admin can view bookings
  - Admin can view payments
  - Reports generate correctly

- [ ] **Cross-browser testing**
  - Chrome
  - Firefox
  - Safari
  - Edge

- [ ] **Mobile responsiveness**
  - Test on iOS
  - Test on Android
  - Verify payment flow on mobile

---

### 8. Backup & Recovery

- [ ] **Set up automated database backups**
  - Daily database backup via cron job
  - Retain backups for 30 days minimum
  - Store backups off-site

- [ ] **Set up file backups**
  - Backup uploaded files regularly
  - Test backup restoration process

- [ ] **Document recovery procedures**
  - Database restoration steps
  - File restoration steps
  - Emergency contact information

---

### 9. Monitoring & Logging

- [ ] **Set up error monitoring**
  - Monitor error logs daily
  - Set up email alerts for critical errors
  - Use tools like Sentry or Rollbar (optional)

- [ ] **Monitor payment transactions**
  - Review Paystack dashboard daily
  - Verify all payments recorded in database
  - Check for failed transactions

- [ ] **Monitor server resources**
  - CPU usage
  - Memory usage
  - Disk space
  - Database performance

- [ ] **Set up uptime monitoring**
  - Use UptimeRobot or similar service
  - Get notified if site goes down

---

### 10. Final Pre-Launch Checks

- [ ] **Verify BASE_URL is correct**
  - Check `includes/config.php` line ~102
  - Should match your production domain

- [ ] **Verify commission rate**
  - Check `includes/config.php` line ~173
  - Currently set to 5% (0.05)

- [ ] **Remove test accounts**
  - Delete test users from database
  - Delete test properties
  - Delete test bookings

- [ ] **Verify all debug statements removed**
  - No `error_log()` in payment files ✓
  - No `var_dump()` or `print_r()` ✓

- [ ] **Test all user flows**
  - Student complete journey
  - Landlord complete journey
  - Admin complete journey

---

## Post-Deployment Checklist

### Immediate (Within 24 Hours)

- [ ] **Monitor error logs**
  - Check `/logs/error.log` for any issues
  - Fix critical errors immediately

- [ ] **Monitor first transactions**
  - Verify first payment processes correctly
  - Check Paystack dashboard matches database records

- [ ] **Test all critical features**
  - User registration
  - Property creation
  - Booking creation
  - Payment processing

### Within First Week

- [ ] **Review analytics**
  - User registration rate
  - Property listing rate
  - Booking conversion rate
  - Payment success rate

- [ ] **Gather user feedback**
  - Any issues with booking process?
  - Any payment failures?
  - Any UI/UX improvements needed?

- [ ] **Performance monitoring**
  - Page load times
  - Database query performance
  - Server response times

### Within First Month

- [ ] **Security audit**
  - Review access logs for suspicious activity
  - Check for SQL injection attempts
  - Review failed login attempts

- [ ] **Financial reconciliation**
  - Match Paystack transactions with database
  - Verify commission calculations
  - Generate financial reports

- [ ] **Feature requests**
  - Document user feature requests
  - Prioritize new features
  - Plan development roadmap

---

## Known Limitations & Future Improvements

### Email Notifications (Not Implemented)
- **Impact:** Users do not receive email notifications
- **Affected Features:**
  - Booking confirmations
  - Payment receipts
  - Property approval notifications
  - Account status changes
- **Workaround:** Users must check their dashboard for updates
- **Priority:** HIGH - Implement before full launch

### Recommended Future Enhancements
1. **Email Notification System**
   - Use PHPMailer or similar library
   - Set up SMTP configuration
   - Implement all notification functions

2. **Payment Installments**
   - Currently payment_installments table exists but not implemented
   - Allow students to pay in installments
   - Track installment schedules

3. **SMS Notifications**
   - Integrate Africa's Talking or Twilio
   - Send SMS for critical events (payment confirmation, booking approval)

4. **Advanced Search**
   - Filter by price range
   - Filter by amenities
   - Sort by distance from campus

5. **Reviews & Ratings**
   - Student reviews for properties
   - Landlord ratings
   - Moderate reviews for quality

---

## Troubleshooting Guide

### Problem: "Database connection failed"
**Solution:**
- Check database credentials in `includes/config.php`
- Verify database server is running
- Check database user permissions
- Verify firewall allows database connections

### Problem: "Payment verification failed"
**Solution:**
- Check Paystack API keys are correct
- Verify Paystack callback URL is accessible
- Check error logs for specific error message
- Verify internet connection from server to Paystack

### Problem: "Failed to record payment"
**Solution:**
- Check `payments` table exists and has correct structure
- Verify foreign key constraints
- Check database user has INSERT permissions
- Review error logs for SQL errors

### Problem: "File upload failed"
**Solution:**
- Check `uploads/properties` directory exists
- Verify directory permissions (755)
- Check PHP `upload_max_filesize` and `post_max_size` settings
- Verify file type is allowed (jpg, jpeg, png, webp)

---

## Support & Maintenance

### Regular Maintenance Tasks

**Daily:**
- Check error logs
- Monitor payment transactions
- Review Paystack dashboard

**Weekly:**
- Review new user registrations
- Check for spam properties
- Monitor server resources
- Review booking statistics

**Monthly:**
- Database backup verification
- Security audit
- Performance optimization
- Financial reconciliation

### Emergency Contacts

- **Developer:** [Your contact information]
- **Hosting Support:** [Hosting provider contact]
- **Paystack Support:** support@paystack.com
- **Database Admin:** [DBA contact]

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2025-11-28 | Initial production-ready version |
|  |  | - Fixed payment verification issues |
|  |  | - Removed debug statements |
|  |  | - Added environment variable support |
|  |  | - Commission rate set to 5% |

---

## Deployment Completion Sign-Off

**Deployed By:** ___________________________
**Date:** ___________________________
**Production URL:** ___________________________
**Database Name:** ___________________________
**Paystack Account:** ___________________________

**All checks completed:** [ ] Yes [ ] No
**Ready for production:** [ ] Yes [ ] No

**Notes:**
___________________________________________________________________
___________________________________________________________________
___________________________________________________________________

---

**End of Deployment Checklist**
