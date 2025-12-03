# Upgrade Guide to Version 1.1.0

## What's New in 1.1.0

🎉 **Major Feature: Support for Both Proxmox Modules!**

Version 1.1.0 adds support for both Proxmox module types:
- **Proxmox VE VPS Cloud** (ProxmoxVeVpsCloud_ProductConfiguration) - Already supported
- **Proxmox VE VPS / Addon** (ProxmoxAddon_ProductConfiguration) - NEW!

You can now select which module you're using in the addon settings, and the addon will automatically use the correct database table.

---

## Who Should Upgrade?

### ✅ Upgrade if:
- You're using Proxmox VE VPS (non-Cloud) module
- You want to switch between different Proxmox modules
- You want the latest features and improvements

### ⏸️ Can skip if:
- You're happy with version 1.0.1
- You only use Proxmox VE VPS Cloud and don't need the new feature
- You're waiting for more testing feedback

---

## Pre-Upgrade Checklist

- [ ] Current version is 1.0.0 or 1.0.1
- [ ] Database backup created
- [ ] Know which Proxmox module you're using
- [ ] Have FTP/SSH access to server
- [ ] Tested on staging environment (if available)

---

## Upgrade Steps

### Step 1: Backup Everything

```bash
# Backup database
mysqldump -u whmcs_user -p whmcs_database > backup_before_1.1.0_$(date +%Y%m%d).sql

# Backup addon files
cp -r /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting /path/to/backup/proxmox_bulk_vm_setting_1.0.1
```

### Step 2: Upload New Files

**Files that changed:**
- `proxmox_bulk_vm_setting.php` (main module file)
- `lib/ProductConfigManager.php` (dynamic table support)
- `CHANGELOG.md` (updated)
- `UPGRADE_TO_1.1.md` (this file - new)

**Upload these files:**

Via FTP/SFTP:
1. Upload `proxmox_bulk_vm_setting.php` to:
   ```
   /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/
   ```

2. Upload `lib/ProductConfigManager.php` to:
   ```
   /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/lib/
   ```

3. Upload documentation files (optional):
   ```
   /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/
   ```

Or via SSH:
```bash
# Assuming you have files in /tmp/proxmox_bulk_vm_setting/
cd /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/

# Backup old files
cp proxmox_bulk_vm_setting.php proxmox_bulk_vm_setting.php.bak
cp lib/ProductConfigManager.php lib/ProductConfigManager.php.bak

# Copy new files
cp /tmp/proxmox_bulk_vm_setting/proxmox_bulk_vm_setting.php .
cp /tmp/proxmox_bulk_vm_setting/lib/ProductConfigManager.php lib/

# Set permissions
chmod 644 proxmox_bulk_vm_setting.php lib/ProductConfigManager.php
```

### Step 3: Clear Caches

```bash
# Clear PHP OPcache
/opt/cpanel/ea-php81/root/usr/bin/php -r "opcache_reset();"

# Restart PHP-FPM (if using)
sudo systemctl restart ea-php81-php-fpm

# Or restart Apache
sudo systemctl restart httpd

# Clear WHMCS template cache
rm -rf /path/to/whmcs/templates_c/*
```

### Step 4: Configure Module Type

1. **Login to WHMCS Admin**

2. **Go to Setup → Addon Modules**

3. **Find "Proxmox Bulk VM Setting"**

4. **Click "Configure"**

5. **Select your module type:**
   - **Proxmox VE VPS Cloud (ProxmoxVeVpsCloud)** - If you're using the Cloud version (DEFAULT)
   - **Proxmox VE VPS (ProxmoxAddon)** - If you're using the non-Cloud/Addon version

6. **Click "Save Changes"**

### Step 5: Verify Upgrade

1. **Check version:**
   - Go to: **Setup → Addon Modules**
   - Find: **Proxmox Bulk VM Setting**
   - Version should show: **1.1.0**

2. **Check module indicator:**
   - Go to: **Addons → Proxmox Bulk VM Setting**
   - You should see at the top:
     ```
     ℹ Current Module: Proxmox VE VPS Cloud (ProxmoxVeVpsCloud)
     ```
     or
     ```
     ℹ Current Module: Proxmox VE VPS (ProxmoxAddon)
     ```

3. **Test functionality:**
   - Go to **Manage Groups**
   - View an existing group or create a test group
   - Verify settings load correctly
   - Check that the correct database table is being used

### Step 6: Verify Database Table

Check that the addon is using the correct table:

```sql
-- If you selected "Cloud":
SELECT COUNT(*) FROM ProxmoxVeVpsCloud_ProductConfiguration WHERE type = 'product';

-- If you selected "Addon":
SELECT COUNT(*) FROM ProxmoxAddon_ProductConfiguration WHERE type = 'product';

-- Should return number of configured products (> 0)
```

---

## Switching Between Modules

If you need to switch from one module to another:

### From Cloud to Addon:

1. **Verify Addon table exists:**
   ```sql
   SHOW TABLES LIKE 'ProxmoxAddon_ProductConfiguration';
   ```

2. **Change module setting:**
   - Setup → Addon Modules → Configure
   - Change to: **Proxmox VE VPS (ProxmoxAddon)**
   - Save

3. **Test with one group:**
   - Go to Addons → Proxmox Bulk VM Setting
   - Verify it shows "Current Module: Proxmox VE VPS (ProxmoxAddon)"
   - Test loading settings for a product

### From Addon to Cloud:

Same steps, but select **Proxmox VE VPS Cloud (ProxmoxVeVpsCloud)**

---

## Troubleshooting

### Issue: Module type doesn't change

**Solution:**
1. Clear all caches (see Step 3)
2. Clear browser cache (Ctrl+Shift+R)
3. Check that setting was saved:
   ```sql
   SELECT value FROM tbladdonmodules 
   WHERE module = 'proxmox_bulk_vm_setting' 
   AND setting = 'proxmox_module';
   ```

### Issue: Settings don't load

**Cause:** Wrong module type selected for your installation

**Solution:**
1. Check which table has your product configurations:
   ```sql
   SELECT COUNT(*) FROM ProxmoxVeVpsCloud_ProductConfiguration WHERE product_id = YOUR_PRODUCT_ID;
   SELECT COUNT(*) FROM ProxmoxAddon_ProductConfiguration WHERE product_id = YOUR_PRODUCT_ID;
   ```
2. Select the module type that corresponds to the table with data
3. Save and clear caches

### Issue: "Table doesn't exist" error

**Cause:** The table for the selected module type doesn't exist

**Solution:**
1. Verify which Proxmox module you actually have installed
2. Check tables:
   ```sql
   SHOW TABLES LIKE '%ProductConfiguration';
   ```
3. Select the module type that matches your installed Proxmox module

### Issue: Version still shows 1.0.1

**Cause:** Files weren't uploaded correctly or cache not cleared

**Solution:**
1. Verify files were uploaded:
   ```bash
   grep "version' => '1.1.0'" /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/proxmox_bulk_vm_setting.php
   ```
2. Should show the version line
3. Clear all caches and restart PHP

---

## Rollback Instructions

If you need to rollback to 1.0.1:

```bash
# Restore backed up files
cd /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/
cp proxmox_bulk_vm_setting.php.bak proxmox_bulk_vm_setting.php
cp lib/ProductConfigManager.php.bak lib/ProductConfigManager.php

# Clear caches
rm -rf /path/to/whmcs/templates_c/*
sudo systemctl restart ea-php81-php-fpm

# Or restore from backup
rm -rf /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/
cp -r /path/to/backup/proxmox_bulk_vm_setting_1.0.1 /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting
```

**Note:** No database changes were made, so no database rollback needed.

---

## Testing After Upgrade

### Test 1: Module Selection
- [ ] Can change module type in configuration
- [ ] UI shows correct module indicator
- [ ] Settings save correctly

### Test 2: Existing Groups
- [ ] All existing groups still visible
- [ ] Can edit existing groups
- [ ] Product IDs load correctly

### Test 3: Settings Load
- [ ] Settings load for products in selected module
- [ ] Correct table is being queried
- [ ] All editable settings appear

### Test 4: Apply Changes
- [ ] Preview works correctly
- [ ] Changes apply to database
- [ ] Change history logs correctly

### Test 5: CSV Export
- [ ] Export generates CSV file
- [ ] CSV contains correct data
- [ ] Product names resolve correctly

---

## What's Next?

After successful upgrade:

1. **Update documentation** - Note which module type you're using
2. **Test thoroughly** - Try all features with your module type
3. **Monitor** - Check change history for any issues
4. **Report feedback** - Let us know if anything doesn't work

---

## Version Comparison

| Feature | 1.0.1 | 1.1.0 |
|---------|-------|-------|
| Proxmox Cloud Support | ✅ | ✅ |
| Proxmox Addon Support | ❌ | ✅ |
| Module Selection | ❌ | ✅ |
| Dynamic Table Names | ❌ | ✅ |
| Module Indicator in UI | ❌ | ✅ |
| Object/Array Bug Fix | ✅ | ✅ |

---

## Support

If you encounter issues during upgrade:

1. Check this guide's Troubleshooting section
2. Review TROUBLESHOOTING.md
3. Check CHANGELOG.md for detailed changes
4. Verify you followed all steps

---

## Summary

✅ **Backup created**  
✅ **Files uploaded**  
✅ **Caches cleared**  
✅ **Module type configured**  
✅ **Upgrade verified**  
✅ **Testing completed**  

**Congratulations! You're now running version 1.1.0! 🎉**

Enjoy the new multi-module support!

