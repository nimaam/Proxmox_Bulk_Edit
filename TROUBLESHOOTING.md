# Troubleshooting Guide - Proxmox Bulk VM Setting

## Common Issues and Solutions

### Issue 1: "[ProxmoxVeVpsCloud]Package Scheduler requires Logs" Error on Activation

**Symptoms:**
- Error appears when trying to activate the addon
- Message: `[ProxmoxVeVpsCloud]Package Scheduler requires Logs`

**Cause:**
This error comes from the Proxmox module checking its own dependencies during our addon's activation.

**Solution:**
The code has been updated (v1.0.0+) to avoid loading Proxmox dependencies during activation. 

**Steps to Fix:**
1. Make sure you have the latest version of the addon
2. The activation function now creates tables directly without external dependencies
3. Try activating again - it should work now

**If Still Having Issues:**
1. Deactivate the addon if partially activated
2. Delete and re-upload the addon files
3. Make sure the Proxmox VE VPS Cloud module is properly installed and working
4. Try activating our addon again

---

### Issue 2: Module Not Appearing in Addon List

**Symptoms:**
- "Proxmox Bulk VM Setting" doesn't appear in Setup → Addon Modules

**Solutions:**

**Check 1: File Location**
```bash
# Files should be at:
/path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/proxmox_bulk_vm_setting.php
```

**Check 2: File Permissions**
```bash
# Set correct permissions
chmod 755 /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/
chmod 644 /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/*.php
chmod 755 /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/lib/
chmod 644 /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/lib/*.php
```

**Check 3: PHP Syntax**
```bash
# Check for syntax errors
php -l /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/proxmox_bulk_vm_setting.php
```

**Check 4: Clear WHMCS Cache**
```bash
# Clear compiled templates
rm -rf /path/to/whmcs/templates_c/*
```

**Check 5: Check PHP Version**
```bash
php -v
# Should be PHP 8.1 or higher
```

---

### Issue 3: Database Tables Not Created

**Symptoms:**
- Activation succeeds but tables don't exist
- Error when trying to use the addon

**Check Tables:**
```sql
SHOW TABLES LIKE 'mod_proxmox_bulk_%';
```

**Manual Table Creation:**
If tables weren't created automatically, run this SQL:

```sql
-- Create groups table
CREATE TABLE IF NOT EXISTS `mod_proxmox_bulk_groups` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `product_ids` text NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create change log table
CREATE TABLE IF NOT EXISTS `mod_proxmox_bulk_change_log` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `admin_id` int(10) unsigned NOT NULL,
    `group_id` int(10) unsigned DEFAULT NULL,
    `product_id` int(10) unsigned NOT NULL,
    `setting_name` varchar(255) NOT NULL,
    `old_value` text,
    `new_value` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `admin_id` (`admin_id`),
    KEY `group_id` (`group_id`),
    KEY `product_id` (`product_id`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

### Issue 4: "ProxmoxVeVpsCloud_ProductConfiguration Table Not Found"

**Symptoms:**
- Error when trying to load settings
- Message about missing table

**Cause:**
The Proxmox VE VPS Cloud module isn't properly installed or configured.

**Solutions:**

**Check 1: Verify Proxmox Module is Installed**
```sql
SHOW TABLES LIKE 'ProxmoxVeVpsCloud%';
-- Should show multiple tables including ProxmoxVeVpsCloud_ProductConfiguration
```

**Check 2: Verify Products are Configured**
```sql
SELECT COUNT(*) FROM ProxmoxVeVpsCloud_ProductConfiguration 
WHERE type = 'product';
-- Should return > 0
```

**Check 3: Configure Products with Proxmox Module**
1. Go to Setup → Products/Services
2. Edit a product
3. Module tab → Select "ProxmoxVeVpsCloud"
4. Configure the product settings
5. Save

**Check 4: Wrong Module Version**
- This addon is for **Proxmox VE VPS Cloud** module
- If you're using the non-Cloud version, the table name is different
- Contact for support to adapt for your module version

---

### Issue 5: Settings Not Loading

**Symptoms:**
- Select a group but no settings appear
- 0 settings shown

**Solutions:**

**Check 1: Product ID is Valid**
```sql
SELECT id, name FROM tblproducts WHERE id = YOUR_PRODUCT_ID;
```

**Check 2: Product Has Settings**
```sql
SELECT COUNT(*) FROM ProxmoxVeVpsCloud_ProductConfiguration 
WHERE product_id = YOUR_PRODUCT_ID AND type = 'product';
```

**Check 3: Group Configuration**
- Go to Manage Groups
- Verify product IDs are comma-separated numbers
- No spaces, no letters
- Format: `31,32,33` (correct)
- Not: `31, 32, 33` or `abc,31`

---

### Issue 6: Changes Not Applying to Database

**Symptoms:**
- Preview shows changes
- Apply succeeds
- But database values unchanged

**Solutions:**

**Check 1: Database Permissions**
```sql
SHOW GRANTS FOR 'whmcs_user'@'localhost';
-- Should include UPDATE permission
```

**Check 2: Verify in Change History**
- Go to Change History
- If change is logged, it was attempted
- Check the logged new value

**Check 3: Check for Errors**
Look in:
- WHMCS Activity Log
- PHP error log
- MySQL error log

**Check 4: Test Manual Update**
```sql
UPDATE ProxmoxVeVpsCloud_ProductConfiguration 
SET value = '"test"' 
WHERE product_id = 31 AND setting = 'userComment';
-- If this fails, it's a permissions issue
```

---

### Issue 7: Values Look Weird (JSON Encoding)

**Symptoms:**
- Values show as `"value"` with quotes
- Double-encoded JSON
- Can't read values

**This is NORMAL:**
- Values are stored as JSON in the database
- Database: `"on"` (with quotes)
- The addon handles decoding automatically
- When you edit, just type the value normally (without quotes)

**To See Readable Values:**
1. Use the CSV Export function
2. Values in CSV are decoded and readable

**Example:**
```
Database raw value: "on"
Decoded display value: on
You edit as: on
Saved back as: "on"
```

---

### Issue 8: Access Denied / Permission Issues

**Symptoms:**
- Cannot access addon
- 404 or permission error

**Solutions:**

**Check 1: Module is Activated**
- Setup → Addon Modules
- "Proxmox Bulk VM Setting" should show as active

**Check 2: Access Control Set**
- Setup → Addon Modules
- Configure button
- Check admin roles are selected
- Save

**Check 3: Your Admin Role**
- Your account must have one of the allowed roles
- Default: Full Administrator

---

### Issue 9: Slow Performance

**Symptoms:**
- Pages load slowly
- Timeouts on large groups

**Solutions:**

**For Large Groups (50+ products):**
- Consider splitting into smaller groups
- Increase PHP max_execution_time
- Increase PHP memory_limit

**PHP Configuration:**
```ini
max_execution_time = 300
memory_limit = 256M
```

**Database Optimization:**
```sql
-- Check indexes exist
SHOW INDEX FROM ProxmoxVeVpsCloud_ProductConfiguration;
SHOW INDEX FROM mod_proxmox_bulk_groups;
SHOW INDEX FROM mod_proxmox_bulk_change_log;
```

---

### Issue 10: Forbidden Settings Appearing in List

**Symptoms:**
- See cores, memory, diskSize, etc. in edit list
- Should be hidden

**This Shouldn't Happen:**
These settings are filtered out in code. If you see them:

**Check 1: Verify Code Version**
- Make sure you have the latest version
- Check `$forbiddenSettings` array in main PHP file

**Check 2: Clear Browser Cache**
- Hard refresh (Ctrl+F5 or Cmd+Shift+R)
- Clear cookies

**Workaround:**
- Don't edit these settings
- Even if they appear, they should be protected

---

### Issue 11: CSV Export Not Working

**Symptoms:**
- Click export button
- Nothing downloads
- Error or blank page

**Solutions:**

**Check 1: PHP Output Buffering**
```ini
output_buffering = Off
```

**Check 2: Check for Errors**
- Look in PHP error log
- Look in browser console

**Check 3: File Permissions**
- PHP needs write access to temp directory
- Check `/tmp` permissions

**Check 4: Memory Limit**
For large exports:
```ini
memory_limit = 256M
```

---

### Issue 12: Change History Not Logging

**Symptoms:**
- Changes apply successfully
- But don't appear in Change History

**Solutions:**

**Check 1: Table Exists**
```sql
SHOW TABLES LIKE 'mod_proxmox_bulk_change_log';
```

**Check 2: Can Write to Table**
```sql
INSERT INTO mod_proxmox_bulk_change_log 
(admin_id, product_id, setting_name, old_value, new_value) 
VALUES (1, 1, 'test', 'old', 'new');

-- Check it inserted
SELECT * FROM mod_proxmox_bulk_change_log ORDER BY id DESC LIMIT 1;

-- Clean up test
DELETE FROM mod_proxmox_bulk_change_log WHERE setting_name = 'test';
```

**Check 3: Admin ID Detection**
- Make sure you're logged in as admin
- Check session is valid

---

## Getting More Help

### Diagnostic Information to Collect

When asking for help, provide:

1. **WHMCS Version:**
   ```
   System Info → About WHMCS
   ```

2. **PHP Version:**
   ```bash
   php -v
   ```

3. **Error Messages:**
   - Exact error text
   - WHMCS Activity Log entries
   - PHP error log entries

4. **What You've Tried:**
   - Steps taken so far
   - Which solutions from this guide

5. **Screenshots:**
   - Error messages
   - Configuration screens
   - Any relevant UI

### Log Files to Check

**WHMCS Logs:**
```
/path/to/whmcs/logs/
```

**PHP Error Log:**
```bash
# Common locations:
/var/log/apache2/error.log
/var/log/nginx/error.log
/var/log/php-fpm/error.log
```

**MySQL Error Log:**
```bash
/var/log/mysql/error.log
```

### Database Diagnostic Queries

```sql
-- Check all relevant tables exist
SHOW TABLES LIKE '%Proxmox%';
SHOW TABLES LIKE 'mod_proxmox_bulk%';

-- Check products exist
SELECT id, name FROM tblproducts LIMIT 5;

-- Check configuration data exists
SELECT product_id, COUNT(*) as setting_count 
FROM ProxmoxVeVpsCloud_ProductConfiguration 
WHERE type = 'product' 
GROUP BY product_id;

-- Check groups
SELECT * FROM mod_proxmox_bulk_groups;

-- Check change log
SELECT COUNT(*) FROM mod_proxmox_bulk_change_log;
```

---

## Emergency Procedures

### Complete Uninstall

If you need to start fresh:

```bash
# 1. Deactivate in WHMCS
# Setup → Addon Modules → Deactivate

# 2. Backup change history (optional)
mysqldump -u user -p whmcs mod_proxmox_bulk_change_log > change_log_backup.sql

# 3. Drop tables
```

```sql
DROP TABLE IF EXISTS mod_proxmox_bulk_change_log;
DROP TABLE IF EXISTS mod_proxmox_bulk_groups;
```

```bash
# 4. Delete files
rm -rf /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/

# 5. Start fresh installation
```

### Restore from Backup

If something went wrong:

```bash
# 1. Restore database from backup
mysql -u user -p whmcs < backup_before_changes.sql

# 2. Or restore specific settings from CSV export
# Use the addon to load CSV and reapply correct values
```

---

## Prevention

### Best Practices to Avoid Issues

1. **Always backup before bulk edits**
   ```bash
   mysqldump -u user -p whmcs > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Always export to CSV before changes**
   - Use Export button in addon
   - Keep CSV files organized by date

3. **Test in staging first**
   - Never test in production
   - Use staging WHMCS for testing

4. **Start small**
   - Test with 1 product first
   - Then expand to more products

5. **Review preview carefully**
   - Always use preview
   - Check all products and settings
   - Verify counts match expectations

6. **Check change history after**
   - Verify changes were logged
   - Confirm all products updated

7. **Monitor for issues**
   - Check WHMCS logs weekly
   - Review change history monthly
   - Test VM provisioning after bulk changes

---

## Still Need Help?

If none of these solutions work:

1. Re-read the relevant documentation:
   - README.md
   - INSTALLATION.md
   - QUICKSTART.md

2. Check you have latest version

3. Collect diagnostic information (see above)

4. Contact support with:
   - Detailed problem description
   - Steps to reproduce
   - Error messages
   - Diagnostic info
   - What you've already tried

---

**Last Updated:** December 2024  
**Version:** 1.0.0

