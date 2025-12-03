# Proxmox Bulk VM Setting - WHMCS Addon

A powerful WHMCS addon module for bulk editing Proxmox VE VPS Cloud product configurations across multiple products simultaneously.

## Features

- **Group Management**: Organize products into named groups for easy bulk editing
- **Bulk Configuration Editor**: Edit multiple product settings at once
- **Preview Changes**: Review all changes before applying them to the database
- **Change History**: Track all configuration changes with full audit trail
- **CSV Export**: Backup current settings before making changes
- **Safe Editing**: Prevents modification of critical resource settings (CPU, RAM, Disk)
- **Search & Filter**: Quickly find settings in large configuration lists
- **Full Audit Trail**: Complete logging of who changed what and when

## Requirements

- WHMCS 8.0 or higher
- PHP 8.1 or higher
- ModuleGarden Proxmox VE VPS Cloud module installed
- MySQL/MariaDB database access

## Supported Modules

**Version 1.1.0+ supports BOTH modules:**
- ✅ **Proxmox VE VPS Cloud** (table: `ProxmoxVeVpsCloud_ProductConfiguration`)
- ✅ **Proxmox VE VPS / Addon** (table: `ProxmoxAddon_ProductConfiguration`)

**Select which module you're using in the addon configuration** (Setup → Addon Modules → Configure)

## Protected Settings

The following settings are **read-only** and cannot be edited through this addon to prevent resource allocation issues:

- `additionalDiskSize`
- `cores`
- `cpulimit`
- `cpuunits`
- `diskSize`
- `memory`
- `vcpus`

All other settings can be safely edited in bulk.

## Installation

1. **Upload Files**
   ```bash
   # Upload the entire folder to your WHMCS installation
   /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/
   ```

2. **Activate Module**
   - Log in to WHMCS Admin Area
   - Navigate to: **Setup → Addon Modules**
   - Find "Proxmox Bulk VM Setting" in the list
   - Click **Activate**
   - Set **Access Control** to allow specific admin roles

3. **Verify Installation**
   - Check that database tables were created:
     - `mod_proxmox_bulk_groups`
     - `mod_proxmox_bulk_change_log`
   - Navigate to: **Addons → Proxmox Bulk VM Setting**

## Configuration

### Selecting Your Proxmox Module

**IMPORTANT:** First, configure which Proxmox module you're using:

1. Navigate to: **Setup → Addon Modules**
2. Find **Proxmox Bulk VM Setting**
3. Click **Configure**
4. Select **Proxmox Module Type:**
   - **Proxmox VE VPS Cloud (ProxmoxVeVpsCloud)** - For the Cloud version
   - **Proxmox VE VPS (ProxmoxAddon)** - For the non-Cloud/Addon version
5. Click **Save Changes**

The addon will automatically use the correct database table based on your selection.

### Creating Product Groups

1. Navigate to: **Addons → Proxmox Bulk VM Setting**
2. Click **Manage Groups** button
3. Enter a **Group Name** (e.g., "Windows VDS")
4. Enter **Product IDs** as comma-separated values (e.g., "31,32,33,34,35,36,80")
   - Product IDs must exist in `tblproducts` table
   - Product IDs should be configured with Proxmox VE VPS Cloud module
5. Click **Create Group**

### Managing Groups

- **Edit Group**: Click "Edit" next to any group to modify name or product IDs
- **Delete Group**: Click "Delete" to remove a group (does not affect products)
- Groups are only organizational - deleting a group does not affect product configurations

## Usage

### Bulk Editing Workflow

1. **Select Group**
   - Navigate to: **Addons → Proxmox Bulk VM Setting**
   - Click on the group you want to edit

2. **Edit Settings**
   - The addon loads all settings from the first product in the group
   - Use the search box to quickly find specific settings
   - Edit values in the "Value" column
   - Uncheck "Apply" for settings you don't want to change
   - Click **Preview Changes**

3. **Review Changes**
   - Review the detailed before/after comparison
   - Verify which products will be updated
   - Verify which settings will change
   - Click **Confirm & Apply Changes** to proceed, or **Cancel** to go back

4. **Apply Changes**
   - Changes are applied to all products in the group
   - Change history is automatically logged
   - Success/error summary is displayed

### Exporting Current Settings

Before making changes, you can export current settings:

1. Select a group and click **Edit**
2. Click **Export Current Settings to CSV**
3. CSV file will download with all current settings for all products in the group
4. Use this as a backup before making changes

### Viewing Change History

1. Navigate to: **Addons → Proxmox Bulk VM Setting**
2. Click **Change History** button
3. View complete audit trail:
   - Date/Time of change
   - Admin username
   - Group name
   - Product ID affected
   - Setting changed
   - Old value → New value

## Database Schema

### mod_proxmox_bulk_groups

Stores product group definitions:

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| name | VARCHAR(255) | Group name |
| product_ids | TEXT | Comma-separated product IDs |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

### mod_proxmox_bulk_change_log

Stores all configuration changes:

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| admin_id | INT | Admin user who made the change |
| group_id | INT | Group ID (nullable) |
| product_id | INT | Product ID affected |
| setting_name | VARCHAR(255) | Setting that was changed |
| old_value | TEXT | Previous value |
| new_value | TEXT | New value |
| created_at | TIMESTAMP | Change timestamp |

## Best Practices

### Before Making Changes

1. **Create a database backup**
   ```bash
   mysqldump -u username -p whmcs_database > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Export current settings to CSV** using the export button

3. **Test on staging environment first** if possible

### Organizing Groups

- Create logical groups based on product type (e.g., "Windows VDS", "Linux VPS", "Storage Plans")
- Keep similar products in the same group for consistent configuration
- Document what each group contains for future reference

### Making Changes Safely

- **Always preview changes** before applying
- **Start with small groups** to test changes
- **Verify settings** match your intended values
- **Check change history** after applying to confirm success

### Understanding Setting Values

Many Proxmox settings use specific formats:

- **Boolean values**: `"on"`, `"off"` (with quotes in database)
- **Numeric values**: `"100"`, `"2048"` (as strings)
- **Arrays**: `["value1","value2"]` (JSON format)
- **Empty values**: `""` (empty string)

Refer to Proxmox VE VPS Cloud module documentation for valid values.

## Troubleshooting

### Module Not Appearing

- Verify files are in correct location: `/modules/addons/proxmox_bulk_vm_setting/`
- Check file permissions (should be readable by web server)
- Clear WHMCS cache: **Utilities → System → Clear Cache**

### Database Tables Not Created

- Check database user has CREATE TABLE permission
- Manually create tables using SQL in `/docs/install.sql` (if provided)
- Check WHMCS error logs for detailed error messages

### Settings Not Updating

- Verify product IDs exist in `tblproducts` table
- Verify `ProxmoxVeVpsCloud_ProductConfiguration` table exists
- Check change history to see if update was logged
- Verify you're not trying to edit protected settings

### Preview Shows No Changes

- Verify you checked "Apply" checkbox for settings you want to change
- Verify the new value is different from the current value
- Check that settings exist for all products in the group

## Security Considerations

- **Access Control**: Only grant access to trusted administrators
- **Change Logging**: All changes are logged with admin ID for accountability
- **Protected Settings**: Critical resource settings cannot be modified
- **Preview Required**: Changes cannot be applied without preview step
- **Database Backup**: Always maintain regular database backups

## Support & Development

### Extending the Module

The module is structured for easy extension:

- **Add new module support**: Modify `ProductConfigManager::TABLE_NAME`
- **Add new protected settings**: Update `$forbiddenSettings` array in `renderEditPage()`
- **Customize UI**: Modify templates in main module file
- **Add validation**: Extend `ProductConfigManager::updateSetting()`

### File Structure

```
proxmox_bulk_vm_setting/
├── proxmox_bulk_vm_setting.php  # Main module file
├── lib/
│   ├── Database.php              # Database schema management
│   ├── GroupManager.php          # Group CRUD operations
│   ├── ProductConfigManager.php  # Proxmox config operations
│   ├── ChangeLogger.php          # Audit trail management
│   └── CsvExporter.php           # CSV export functionality
├── README.md                     # This file
└── INSTALLATION.md               # Detailed installation guide
```

## Version History

### Version 1.1.0 (Current)
- Added support for both Proxmox modules (Cloud and Addon)
- Module selection in addon configuration
- Dynamic database table support
- UI shows current module indicator
- Automatic table name detection

### Version 1.0.1
- Fixed object/array type handling
- Improved compatibility

### Version 1.0.0
- Initial release
- Support for Proxmox VE VPS Cloud module
- Group management
- Bulk editing with preview
- Change history logging
- CSV export functionality

### Planned Features (Future Versions)
- Support for Proxmox VE VPS module (non-Cloud)
- Module selection in settings
- Bulk import from CSV
- Setting templates
- Scheduled bulk updates
- Advanced filtering and search
- Setting validation rules
- Multi-language support

## License

Copyright (c) 2024. All rights reserved.

This software is provided "as is" without warranty of any kind. Use at your own risk.

## Important Notes

⚠️ **WARNING**: This addon directly modifies your WHMCS database. Always:
- Test on staging environment first
- Create database backups before making changes
- Verify changes in preview before applying
- Monitor change history after updates

✅ **SAFE**: The addon prevents modification of resource allocation settings (CPU, RAM, disk) to avoid provisioning issues.

📝 **RECOMMENDED**: Keep change history for audit purposes and troubleshooting.

## Contact

For questions, issues, or feature requests, contact your system administrator or the module developer.

