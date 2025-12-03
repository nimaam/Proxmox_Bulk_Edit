# Installation Guide - Proxmox Bulk VM Setting

Complete step-by-step installation guide for the Proxmox Bulk VM Setting WHMCS addon.

## Prerequisites Checklist

Before starting installation, verify you have:

- [ ] WHMCS 8.0 or higher installed and running
- [ ] PHP 8.1 or higher
- [ ] ModuleGarden Proxmox VE VPS Cloud module installed and configured
- [ ] Admin access to WHMCS
- [ ] FTP/SSH access to WHMCS server
- [ ] Database backup capability
- [ ] At least one Proxmox Cloud product configured in WHMCS

## Step 1: Backup Your System

**⚠️ CRITICAL: Always backup before installing any addon**

### Database Backup

```bash
# SSH into your server
ssh user@your-whmcs-server.com

# Navigate to a backup directory
cd /path/to/backups

# Create database backup
mysqldump -u whmcs_user -p whmcs_database > whmcs_backup_$(date +%Y%m%d_%H%M%S).sql

# Verify backup was created
ls -lh whmcs_backup_*.sql
```

### File Backup (Optional but Recommended)

```bash
# Backup entire WHMCS directory
cd /path/to
tar -czf whmcs_backup_$(date +%Y%m%d_%H%M%S).tar.gz whmcs/
```

## Step 2: Upload Module Files

### Option A: Upload via FTP/SFTP

1. Download the `proxmox_bulk_vm_setting` folder
2. Connect to your WHMCS server via FTP/SFTP
3. Navigate to: `/path/to/whmcs/modules/addons/`
4. Upload the entire `proxmox_bulk_vm_setting` folder
5. Verify folder structure:

```
whmcs/
└── modules/
    └── addons/
        └── proxmox_bulk_vm_setting/
            ├── proxmox_bulk_vm_setting.php
            ├── lib/
            │   ├── Database.php
            │   ├── GroupManager.php
            │   ├── ProductConfigManager.php
            │   ├── ChangeLogger.php
            │   └── CsvExporter.php
            ├── README.md
            └── INSTALLATION.md
```

### Option B: Upload via SSH

```bash
# SSH into your server
ssh user@your-whmcs-server.com

# Navigate to addons directory
cd /path/to/whmcs/modules/addons/

# Upload using scp from your local machine (run this on your local machine)
scp -r proxmox_bulk_vm_setting user@your-whmcs-server.com:/path/to/whmcs/modules/addons/

# Or if you have the zip file on server
unzip proxmox_bulk_vm_setting.zip -d /path/to/whmcs/modules/addons/
```

## Step 3: Set File Permissions

```bash
# Navigate to module directory
cd /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting

# Set proper permissions
chmod 755 .
chmod 644 *.php *.md
chmod 755 lib
chmod 644 lib/*.php

# Set owner (replace www-data with your web server user)
chown -R www-data:www-data .
```

## Step 4: Activate the Module

1. **Login to WHMCS Admin Area**
   - URL: `https://your-whmcs-domain.com/admin/`
   - Use your admin credentials

2. **Navigate to Addon Modules**
   - Go to: **Setup → Addon Modules**
   - Or URL: `/admin/configaddonmods.php`

3. **Find the Module**
   - Scroll down to find "Proxmox Bulk VM Setting"
   - If you don't see it, click "Refresh" or clear cache

4. **Activate**
   - Click the **Activate** button
   - Wait for confirmation message

5. **Set Access Control**
   - After activation, you'll see "Configure" button
   - Click **Configure**
   - In "Access Control" section, select which admin roles can use this module
   - Recommended: Only "Full Administrator" role for initial testing
   - Click **Save Changes**

## Step 5: Verify Installation

### Check Database Tables

1. **Access PHPMyAdmin** or use MySQL command line:

```sql
-- Connect to your WHMCS database
USE whmcs_database;

-- Verify tables were created
SHOW TABLES LIKE 'mod_proxmox_bulk_%';

-- Should show:
-- mod_proxmox_bulk_change_log
-- mod_proxmox_bulk_groups

-- Check table structure
DESCRIBE mod_proxmox_bulk_groups;
DESCRIBE mod_proxmox_bulk_change_log;
```

Expected output for `mod_proxmox_bulk_groups`:
```
+-------------+------------------+------+-----+-------------------+
| Field       | Type             | Null | Key | Default           |
+-------------+------------------+------+-----+-------------------+
| id          | int(10) unsigned | NO   | PRI | NULL              |
| name        | varchar(255)     | NO   | MUL | NULL              |
| product_ids | text             | NO   |     | NULL              |
| created_at  | timestamp        | NO   |     | CURRENT_TIMESTAMP |
| updated_at  | timestamp        | NO   |     | CURRENT_TIMESTAMP |
+-------------+------------------+------+-----+-------------------+
```

### Access the Module

1. **Navigate to the Addon**
   - Go to: **Addons → Proxmox Bulk VM Setting**
   - Or URL: `/admin/addonmodules.php?module=proxmox_bulk_vm_setting`

2. **Verify Interface Loads**
   - You should see: "No groups configured yet" message
   - Three buttons: "Bulk Editor", "Manage Groups", "Change History"
   - No errors displayed

## Step 6: Initial Configuration

### Create Your First Group

1. **Gather Product IDs**
   - Go to: **System Settings → Products/Services**
   - Note down the IDs of products you want to manage together
   - Example: Products 31, 32, 33, 34, 35 are all "Windows VDS" plans

2. **Create Group**
   - In Proxmox Bulk VM Setting addon, click **Manage Groups**
   - Fill in the form:
     - **Group Name**: "Test Group" (for testing)
     - **Product IDs**: Use just one product ID first for testing (e.g., "31")
   - Click **Create Group**
   - Verify success message appears

3. **Verify Product Configuration Exists**
   - Check database:
   ```sql
   SELECT COUNT(*) FROM ProxmoxVeVpsCloud_ProductConfiguration 
   WHERE product_id = 31 AND type = 'product';
   ```
   - Should return a number greater than 0
   - If 0, the product is not configured with Proxmox module yet

## Step 7: Test the Module

### Test 1: View Settings

1. Click on your test group from the main page
2. Verify settings load correctly
3. Check that forbidden settings are NOT in the list:
   - `additionalDiskSize`
   - `cores`
   - `cpulimit`
   - `cpuunits`
   - `diskSize`
   - `memory`
   - `vcpus`

### Test 2: Export Settings

1. Click **Export Current Settings to CSV**
2. Verify CSV downloads correctly
3. Open CSV and verify data looks correct
4. Keep this CSV as a backup

### Test 3: Preview Changes (Without Applying)

1. Find a safe setting to test (e.g., `autostart`)
2. Change its value (e.g., from `"off"` to `"on"`)
3. Ensure "Apply" checkbox is checked
4. Click **Preview Changes**
5. Verify preview shows the change correctly
6. Click **Cancel** (do not apply yet)

### Test 4: Apply a Safe Change

1. Choose a non-critical setting for testing
   - Recommended: `userComment` or `description`
2. Change the value to something identifiable (e.g., "TEST CHANGE")
3. Click **Preview Changes**
4. Review the preview carefully
5. Click **Confirm & Apply Changes**
6. Verify success message appears
7. Check **Change History** to see the logged change
8. Verify in database:
   ```sql
   SELECT value FROM ProxmoxVeVpsCloud_ProductConfiguration 
   WHERE product_id = 31 AND setting = 'userComment';
   ```

### Test 5: Verify Change History

1. Go to **Change History**
2. Verify your test change is logged
3. Check that it shows:
   - Your admin username
   - Correct timestamp
   - Product ID
   - Setting name
   - Old and new values

## Step 8: Production Deployment

Once testing is successful:

1. **Create Production Groups**
   - Delete or rename the test group
   - Create real groups with multiple product IDs
   - Example: "Windows VDS" with IDs "31,32,33,34,35,36,80"

2. **Document Your Groups**
   - Keep a record of which products are in which groups
   - Note the purpose of each group

3. **Train Administrators**
   - Show other admins how to use the module
   - Emphasize the importance of:
     - Creating backups before changes
     - Using preview before applying
     - Checking change history after updates

4. **Set Up Regular Backups**
   - Schedule daily database backups
   - Keep backups for at least 30 days
   - Test backup restoration process

## Troubleshooting Installation

### Module Not Appearing in Addon Modules

**Problem**: Module doesn't show up in Setup → Addon Modules

**Solutions**:
```bash
# Check file permissions
ls -la /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/

# Verify ownership
# Should be owned by web server user (www-data, apache, nginx, etc.)

# Check PHP syntax errors
php -l /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/proxmox_bulk_vm_setting.php

# Clear WHMCS cache
rm -rf /path/to/whmcs/templates_c/*
```

### Database Tables Not Created

**Problem**: Tables `mod_proxmox_bulk_groups` and `mod_proxmox_bulk_change_log` don't exist

**Solution**: Manually create tables:

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

### "ProxmoxVeVpsCloud_ProductConfiguration Table Not Found"

**Problem**: Error when trying to load settings

**Solutions**:

1. Verify Proxmox VE VPS Cloud module is installed:
   ```sql
   SHOW TABLES LIKE 'ProxmoxVeVpsCloud%';
   ```

2. Check if products are configured with Proxmox module:
   ```sql
   SELECT product_id, COUNT(*) as setting_count 
   FROM ProxmoxVeVpsCloud_ProductConfiguration 
   GROUP BY product_id;
   ```

3. If table exists but is empty, you need to configure products in the Proxmox module first

### Permission Denied Errors

**Problem**: Cannot write to files or access database

**Solutions**:

```bash
# Fix file permissions
chown -R www-data:www-data /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/
chmod -R 755 /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/

# Check SELinux (if enabled)
setenforce 0  # Temporarily disable for testing
# If this fixes it, properly configure SELinux contexts
```

### Settings Not Updating

**Problem**: Changes preview correctly but don't apply to database

**Solutions**:

1. Check database user permissions:
   ```sql
   SHOW GRANTS FOR 'whmcs_user'@'localhost';
   -- Should include UPDATE permission on WHMCS database
   ```

2. Check if products exist:
   ```sql
   SELECT id, name FROM tblproducts WHERE id IN (31,32,33);
   ```

3. Enable error reporting temporarily:
   ```php
   // Add to top of proxmox_bulk_vm_setting.php for debugging
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

## Post-Installation Security

1. **Remove debug code** (if added during troubleshooting)
2. **Review access control** - ensure only trusted admins have access
3. **Monitor change history** regularly for unexpected changes
4. **Test backup restoration** to ensure you can recover if needed

## Uninstallation (If Needed)

If you need to uninstall the module:

1. **Deactivate** in WHMCS Admin → Setup → Addon Modules
2. **Backup change history**:
   ```sql
   SELECT * FROM mod_proxmox_bulk_change_log INTO OUTFILE '/tmp/change_log_backup.csv';
   ```
3. **Delete database tables** (optional - only if you want to remove all data):
   ```sql
   DROP TABLE IF EXISTS mod_proxmox_bulk_change_log;
   DROP TABLE IF EXISTS mod_proxmox_bulk_groups;
   ```
4. **Delete files**:
   ```bash
   rm -rf /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/
   ```

## Getting Help

If you encounter issues not covered in this guide:

1. Check WHMCS error logs: `/path/to/whmcs/logs/`
2. Check PHP error logs: Usually `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
3. Review the README.md for troubleshooting tips
4. Contact your system administrator or developer

## Next Steps

After successful installation:

1. Read the [README.md](README.md) for detailed usage instructions
2. Create your production groups
3. Familiarize yourself with the preview and apply workflow
4. Set up regular database backup schedule
5. Train other administrators who will use this module

---

**Installation Complete!** 🎉

You can now use the Proxmox Bulk VM Setting addon to efficiently manage your Proxmox product configurations.

