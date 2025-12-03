# Quick Start Guide - Proxmox Bulk VM Setting

Get started in 5 minutes!

## Installation (5 Steps)

### 1. Upload Files
```bash
# Upload to your WHMCS server
/path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/
```

### 2. Activate Module
- WHMCS Admin → **Setup → Addon Modules**
- Find "Proxmox Bulk VM Setting"
- Click **Activate**

### 3. Set Access Control
- After activation, click **Configure**
- Select admin roles (recommend: Full Administrator only for testing)
- Click **Save Changes**

### 4. Verify Installation
- Go to **Addons → Proxmox Bulk VM Setting**
- You should see "No groups configured yet" message
- Database tables `mod_proxmox_bulk_groups` and `mod_proxmox_bulk_change_log` should exist

### 5. Create Your First Group
- Click **Manage Groups**
- Fill in:
  - **Group Name**: "Test Group"
  - **Product IDs**: "31" (use one product ID for testing)
- Click **Create Group**

## First Edit (3 Steps)

### 1. Select Group
- Go to **Addons → Proxmox Bulk VM Setting**
- Click on your "Test Group"

### 2. Export Current Settings (Backup)
- Click **Export Current Settings to CSV**
- Save the CSV file as backup

### 3. Make a Test Change
- Find a safe setting like `userComment`
- Change its value to "TEST CHANGE"
- Click **Preview Changes**
- Review the preview
- Click **Confirm & Apply Changes**
- Check **Change History** to verify

## Understanding the UI

### Field Types

The addon automatically detects field types:

**Toggle Fields (On/Off):**
- Most `permission*` settings
- Boolean options like `autostart`, `reboot`, `cloudInit`
- Dropdown with `on`/`off` options

**Array Fields (JSON):**
- Settings like `alternativeMode`, `bridges`, `tags`
- Format: `["value1","value2"]`
- Edit as JSON array string

**Text Areas:**
- Long text fields like `description`
- Multi-line editing

**Text Inputs:**
- All other settings
- Numbers, strings, paths, etc.

### Protected Settings

These settings **cannot** be edited (for safety):
- `additionalDiskSize`
- `cores`
- `cpulimit`
- `cpuunits`
- `diskSize`
- `memory`
- `vcpus`

## Common Tasks

### Add New Group
1. **Addons → Proxmox Bulk VM Setting**
2. **Manage Groups** button
3. Fill in name and product IDs (comma-separated)
4. **Create Group**

### Edit Multiple Products
1. Create a group with multiple product IDs (e.g., "31,32,33,34")
2. Select the group
3. Edit settings (loaded from first product)
4. Changes apply to ALL products in group

### View What Changed
1. **Addons → Proxmox Bulk VM Setting**
2. **Change History** button
3. See all changes with timestamps and admin usernames

### Export Settings Before Changes
1. Select a group
2. **Export Current Settings to CSV** button
3. Keep CSV as backup

## Safety Tips

✅ **DO:**
- Always export settings to CSV before making changes
- Use preview to verify changes before applying
- Test on one product first
- Check change history after applying
- Create database backups regularly

❌ **DON'T:**
- Skip the preview step
- Apply changes without reviewing
- Edit protected settings (they're blocked anyway)
- Delete change history (keep for audit trail)

## Common Settings to Bulk Edit

### Permissions
Bulk enable/disable client area features:
- `permissionBackup` - Allow clients to create backups
- `permissionReboot` - Allow clients to reboot VM
- `permissionSnapshot` - Allow clients to create snapshots
- `permissionFirewall` - Allow clients to manage firewall

### Backup Settings
Configure backup policies:
- `backupMaxFiles` - Maximum backup files
- `backupStorage` - Backup storage location
- `backupStoreDays` - Backup retention days

### Network Settings
Configure network options:
- `bridge` - Network bridge
- `rate` - Network rate limit
- `networkFirewall` - Enable/disable firewall

### Cloud-Init
Configure cloud-init settings:
- `cloudInit` - Enable/disable cloud-init
- `ciuser` - Default cloud-init username
- `cloudInitServicePassword` - Auto-set password

## Example Workflows

### Example 1: Enable Backups for All Windows VDS
1. Create group "Windows VDS" with IDs: 31,32,33,34,35
2. Select group → Edit
3. Find `permissionBackup` → Change to "on"
4. Find `backupMaxFiles` → Change to "3"
5. Find `backupStorage` → Set to "aks-nas01"
6. Preview → Apply

### Example 2: Disable Client Snapshots
1. Select your product group
2. Find `permissionSnapshot` → Change to "off"
3. Find `permissionSnapshotJob` → Change to "off"
4. Preview → Apply

### Example 3: Change Network Bridge
1. Export current settings first!
2. Select group
3. Find `bridge` → Change to "vmbr1"
4. Preview → Verify it affects correct products
5. Apply

## Troubleshooting

### "No changes detected" in Preview
- Check that "Apply" checkbox is checked for settings you want to change
- Verify the new value is different from current value

### Settings Not Updating
- Check that product IDs exist in `tblproducts`
- Verify `ProxmoxVeVpsCloud_ProductConfiguration` table exists
- Check change history to see if update was logged

### Values Look Strange
- Values are stored as JSON in database
- The addon handles encoding/decoding automatically
- Use CSV export to see current values in readable format

## Getting Help

**Check Installation:**
```sql
-- Verify tables exist
SHOW TABLES LIKE 'mod_proxmox_bulk_%';

-- Check if product has settings
SELECT COUNT(*) FROM ProxmoxVeVpsCloud_ProductConfiguration 
WHERE product_id = 31 AND type = 'product';
```

**Check Logs:**
- WHMCS Activity Log: **Utilities → Logs → Activity Log**
- Change History: **Addons → Proxmox Bulk VM Setting → Change History**

**Common Issues:**
- See [INSTALLATION.md](INSTALLATION.md) Troubleshooting section
- See [README.md](README.md) for detailed documentation

## Next Steps

1. ✅ Complete basic installation
2. ✅ Test with one product
3. Create your real product groups
4. Document your groups (what IDs are in each)
5. Train other admins
6. Set up regular database backups
7. Use it to maintain consistent configurations!

---

**Ready to go?** Head to **Addons → Proxmox Bulk VM Setting** and start editing! 🚀

