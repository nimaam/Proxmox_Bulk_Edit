# Changelog - Proxmox Bulk VM Setting

All notable changes to this project will be documented in this file.

## [1.1.1] - 2024-12-02

### Fixed
- **CRITICAL:** Fixed preview comparison bug causing "0 settings updated"
- User input now immediately encoded to database format for comparison
- Preview now compares encoded values (exact database format)
- Changes properly detected when values are different
- Example: Changing ipv4 from "1" to "2" now works correctly

### Added
- New "Database Format" column in preview showing exact encoded values
- Shows both human-readable and database format in preview
- Better visibility of what will actually be written

### Technical
- User input encoded via json_encode() immediately after form submission
- Comparison uses encoded values ($oldEncodedValue vs $encodedNewValue)
- Preview displays decoded values for readability
- Database writes use exact encoded format from preview
- Ensures preview matches exact database write operation

## [1.1.0] - 2024-12-02

### Added
- **Support for both Proxmox modules!**
  - Proxmox VE VPS Cloud (ProxmoxVeVpsCloud_ProductConfiguration)
  - Proxmox VE VPS / Addon (ProxmoxAddon_ProductConfiguration)
- Module selection dropdown in addon configuration
- Automatic table name detection based on selected module
- Current module indicator in UI
- Dynamic database table switching
- Automatic detection of `type` column presence
- Module-specific forbidden settings lists
- ProxmoxAddon-specific field types and settings
- Dropdown fields for: buttonSyle, detailsView, storageUnit, memoryUnit, productType
- MODULE_DIFFERENCES.md - Comprehensive comparison document

### Changed
- Updated version to 1.1.0
- ProductConfigManager now uses dynamic table names
- Module type stored in WHMCS addon configuration
- UI shows which module is currently selected
- Forbidden settings adapt to module type
- Field type detection includes Addon-specific settings
- Database queries conditionally include `type` column

### Fixed
- ProxmoxAddon queries work without `type` column
- ProxmoxVeVpsCloud queries include `type = 'product'` filter
- Insert/Update operations respect table structure differences
- Module-specific settings properly rendered

### Technical
- Added `proxmox_module` configuration field
- Added `getModuleType()` helper function
- Added `hasTypeColumn()` method for structure detection
- Added `renderDropdownField()` for dropdown options
- ProductConfigManager constructor determines table name
- Conditional query building based on table structure
- Module-aware forbidden settings
- Enhanced field type mapping

## [1.0.1] - 2024-12-02

### Fixed
- Fixed "Cannot use object of type stdClass as array" error
- Properly convert Laravel/Illuminate database query results from objects to arrays
- Updated all methods in GroupManager, ChangeLogger, and ProductConfigManager
- Improved compatibility with different WHMCS/PHP configurations

### Changed
- Enhanced object-to-array conversion in all database query methods
- Added explicit type casting for better reliability

## [1.0.0] - 2024-12-02

### Added
- Initial release
- Group management for organizing products
- Bulk editing of Proxmox VE VPS Cloud product configurations
- Smart field type detection (toggle, textarea, array, text)
- JSON encoding/decoding compatible with Proxmox module
- Preview changes before applying
- Complete change history with audit trail
- CSV export for backup
- Protected settings (cores, memory, disk, etc.)
- Search and filter functionality
- Comprehensive documentation

### Security
- Protected settings cannot be edited
- Input validation for product IDs
- XSS prevention with htmlspecialchars
- SQL injection prevention with Query Builder
- Admin authentication required

### Documentation
- README.md - Complete feature documentation
- INSTALLATION.md - Step-by-step installation guide
- QUICKSTART.md - 5-minute quick start guide
- TESTING.md - 50+ comprehensive test cases
- SUMMARY.md - Technical overview
- TROUBLESHOOTING.md - Common issues and solutions
- INSTALL_CHECKLIST.txt - Printable checklist
- LICENSE.txt - MIT License

---

## Version History

- **1.1.0** (Current) - Added support for both Proxmox modules with module selection
- **1.0.1** - Bug fixes for object/array handling
- **1.0.0** - Initial release

---

## Upgrade Instructions

### From 1.0.1 to 1.1.0

1. **Backup current installation**
   ```bash
   cp -r /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting /path/to/backup/
   ```

2. **Upload new files**
   - Replace all files with new version
   - Both `proxmox_bulk_vm_setting.php` and `lib/ProductConfigManager.php` have been updated

3. **Configure module type**
   - Go to: **Setup → Addon Modules**
   - Find: **Proxmox Bulk VM Setting**
   - Click: **Configure**
   - Select: **Proxmox Module Type** (Cloud or Addon)
   - Click: **Save Changes**

4. **Verify it's working**
   - Go to: **Addons → Proxmox Bulk VM Setting**
   - You should see "Current Module: [your selection]" at the top
   - Test creating/editing a group

**Important:** If you were using the Cloud version (default), no action needed - it will continue working as before. If you want to use the Addon (ProxmoxVE) version, change the setting in Configure.

### From 1.0.0 to 1.0.1

1. **Backup your current installation** (optional but recommended)
   ```bash
   cp -r /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting /path/to/backup/
   ```

2. **Replace files**
   - Delete old files:
     ```bash
     rm -rf /path/to/whmcs/modules/addons/proxmox_bulk_vm_setting/lib/*.php
     ```
   - Upload new files from the updated package

3. **Clear WHMCS cache** (optional)
   ```bash
   rm -rf /path/to/whmcs/templates_c/*
   ```

4. **Test the addon**
   - Go to Addons → Proxmox Bulk VM Setting
   - Try creating/editing a group
   - Should work without errors now

**No database changes required** - this is a code-only update.

---

## Known Issues

### Version 1.1.0
- None currently known

### Version 1.0.1
- None currently known

### Version 1.0.0
- ~~Object/array type mismatch when using certain PHP/WHMCS configurations~~ (Fixed in 1.0.1)

---

## Support

For issues, questions, or feature requests:
1. Check TROUBLESHOOTING.md
2. Review TESTING.md for test cases
3. See README.md for detailed documentation

---

## Future Roadmap

### Planned for v1.2.0
- Import groups from CSV
- Setting templates
- Bulk copy settings between products

### Planned for v1.3.0
- Bulk import from CSV
- Field validation against Proxmox API
- Multi-language support
- Advanced filtering
- Setting comparison between products

### Planned for v2.0.0
- Scheduled bulk updates
- Per-setting rollback
- Conflict detection
- API access for automation

---

## Contributors

- Initial Development - December 2024

---

## License

MIT License - See LICENSE.txt for details

