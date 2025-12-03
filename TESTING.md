# Testing Guide - Proxmox Bulk VM Setting

Complete testing checklist for the addon before production use.

## Prerequisites

- [ ] WHMCS staging environment available
- [ ] Database backup created
- [ ] At least 2-3 test products configured with Proxmox module
- [ ] Admin access to WHMCS and database

## Installation Testing

### Test 1.1: Module Appears in Addon List
**Steps:**
1. Upload module files to `/modules/addons/proxmox_bulk_vm_setting/`
2. Go to Setup → Addon Modules
3. Look for "Proxmox Bulk VM Setting"

**Expected:** Module appears in the list with version 1.0.0

**Result:** [ ] Pass [ ] Fail

---

### Test 1.2: Activation Creates Database Tables
**Steps:**
1. Before activation, verify tables don't exist:
   ```sql
   SHOW TABLES LIKE 'mod_proxmox_bulk_%';
   ```
2. Click **Activate**
3. Check for tables again

**Expected:** 
- Success message displayed
- Tables created: `mod_proxmox_bulk_groups`, `mod_proxmox_bulk_change_log`

**Result:** [ ] Pass [ ] Fail

---

### Test 1.3: Access Control Works
**Steps:**
1. Set access to "Full Administrator" only
2. Save changes
3. Login as different admin role (if available)
4. Try to access Addons → Proxmox Bulk VM Setting

**Expected:** 
- Full Administrator can access
- Other roles cannot access

**Result:** [ ] Pass [ ] Fail

---

## Group Management Testing

### Test 2.1: Create Group with Valid Data
**Steps:**
1. Go to Manage Groups
2. Enter:
   - Name: "Test Group 1"
   - Product IDs: "31,32,33"
3. Click Create Group

**Expected:** Success message, group appears in list

**Result:** [ ] Pass [ ] Fail

---

### Test 2.2: Validate Product ID Format
**Steps:**
1. Try to create group with invalid IDs:
   - "abc,def"
   - "31,32,abc"
   - "-1,0"

**Expected:** Error message for invalid format

**Result:** [ ] Pass [ ] Fail

---

### Test 2.3: Edit Existing Group
**Steps:**
1. Click Edit on Test Group 1
2. Change name to "Updated Test Group"
3. Add product ID: "31,32,33,34"
4. Save

**Expected:** Changes saved, group list updated

**Result:** [ ] Pass [ ] Fail

---

### Test 2.4: Delete Group
**Steps:**
1. Create a temporary group
2. Click Delete with confirmation
3. Check group list

**Expected:** 
- Confirmation dialog appears
- Group removed from list
- No error

**Result:** [ ] Pass [ ] Fail

---

## Settings Loading Testing

### Test 3.1: Load Settings from First Product
**Steps:**
1. Select a group with product IDs "31,32,33"
2. Click the group to edit

**Expected:**
- Settings load from product 31
- Settings are in alphabetical order
- Forbidden settings NOT visible:
  - additionalDiskSize
  - cores
  - cpulimit
  - cpuunits
  - diskSize
  - memory
  - vcpus

**Result:** [ ] Pass [ ] Fail

---

### Test 3.2: Field Types Display Correctly
**Steps:**
1. View edit page
2. Find these settings and check their input type:
   - `autostart` → Should be dropdown (on/off)
   - `backupMaxFiles` → Should be text input
   - `description` → Should be textarea
   - `alternativeMode` → Should be text input with array note

**Expected:** Field types match setting patterns

**Result:** [ ] Pass [ ] Fail

---

### Test 3.3: Search Functionality
**Steps:**
1. On edit page, type "backup" in search box
2. Verify only backup-related settings visible
3. Clear search
4. All settings visible again

**Expected:** Search filters settings correctly

**Result:** [ ] Pass [ ] Fail

---

### Test 3.4: Select All Checkbox
**Steps:**
1. Click "Select All" checkbox
2. Verify all "Apply" checkboxes checked
3. Click "Select All" again
4. Verify all "Apply" checkboxes unchecked

**Expected:** Select All toggles all checkboxes

**Result:** [ ] Pass [ ] Fail

---

## CSV Export Testing

### Test 4.1: Export Current Settings
**Steps:**
1. Select a group
2. Click "Export Current Settings to CSV"
3. Open downloaded CSV

**Expected:**
- CSV downloads with correct filename format: `proxmox_bulk_export_GROUPNAME_DATE.csv`
- CSV contains columns: Product ID, Product Name, Type, Setting, Value
- Values are decoded (readable, not double-JSON-encoded)

**Result:** [ ] Pass [ ] Fail

---

### Test 4.2: Export for Multiple Products
**Steps:**
1. Create group with 3+ products
2. Export CSV
3. Check CSV content

**Expected:** 
- All products included
- Settings for each product present
- Product names resolved correctly

**Result:** [ ] Pass [ ] Fail

---

## Preview Functionality Testing

### Test 5.1: Preview Shows Changes
**Steps:**
1. Select group
2. Change `userComment` to "TEST CHANGE"
3. Keep "Apply" checked
4. Click Preview Changes

**Expected:**
- Preview page shows:
  - Group name
  - Number of changes
  - Table with: Product ID, Product Name, Setting, Current Value, New Value
  - Current value ≠ New value

**Result:** [ ] Pass [ ] Fail

---

### Test 5.2: Preview Shows No Changes When Unchecked
**Steps:**
1. Edit settings
2. Uncheck all "Apply" checkboxes
3. Click Preview Changes

**Expected:** "No settings selected for update" message

**Result:** [ ] Pass [ ] Fail

---

### Test 5.3: Preview Detects No Actual Changes
**Steps:**
1. Don't change any values
2. Click Preview Changes

**Expected:** "No changes detected. All values are already up to date." message

**Result:** [ ] Pass [ ] Fail

---

### Test 5.4: Cancel from Preview
**Steps:**
1. Make changes
2. Preview
3. Click Cancel button

**Expected:** Returns to edit page with no changes applied

**Result:** [ ] Pass [ ] Fail

---

## Apply Changes Testing

### Test 6.1: Apply Single Setting Change
**Steps:**
1. Export current settings (backup)
2. Change `userComment` from current value to "TEST APPLIED"
3. Preview
4. Click "Confirm & Apply Changes"
5. Check database:
   ```sql
   SELECT value FROM ProxmoxVeVpsCloud_ProductConfiguration 
   WHERE product_id = 31 AND setting = 'userComment';
   ```

**Expected:**
- Success message displayed
- Database value updated
- Value is JSON-encoded: `"TEST APPLIED"`

**Result:** [ ] Pass [ ] Fail

---

### Test 6.2: Apply Multiple Settings to Multiple Products
**Steps:**
1. Group with 2+ products (e.g., 31, 32)
2. Change 3 settings:
   - `userComment` → "BULK TEST"
   - `autostart` → "on"
   - `backupMaxFiles` → "5"
3. Preview → Apply
4. Verify in database for both products

**Expected:**
- All 3 settings updated for all products
- Success message shows correct count (3 settings × 2 products = 6 updates)

**Result:** [ ] Pass [ ] Fail

---

### Test 6.3: JSON Encoding Correct
**Steps:**
1. Change a setting with value "test123"
2. Apply
3. Check raw database value:
   ```sql
   SELECT value FROM ProxmoxVeVpsCloud_ProductConfiguration 
   WHERE setting = 'userComment' AND product_id = 31;
   ```

**Expected:** Value is `"test123"` (JSON-encoded string, with quotes)

**Result:** [ ] Pass [ ] Fail

---

### Test 6.4: Array Field Handling
**Steps:**
1. Find array field like `alternativeMode`
2. Current value: `["Disk Space","IP Addresses"]`
3. Change to: `["Disk Space"]`
4. Apply
5. Check database

**Expected:** Array saved correctly as JSON-encoded

**Result:** [ ] Pass [ ] Fail

---

## Change History Testing

### Test 7.1: Changes Logged Correctly
**Steps:**
1. Apply a change (e.g., update `userComment`)
2. Go to Change History
3. Find your change

**Expected:**
- Change appears in history
- Shows: Date/Time, Admin username, Group name, Product ID, Setting name, Old value, New value
- Admin username is correct

**Result:** [ ] Pass [ ] Fail

---

### Test 7.2: Multiple Changes Logged
**Steps:**
1. Apply changes to 3 settings across 2 products (6 total changes)
2. View Change History

**Expected:** All 6 changes logged individually

**Result:** [ ] Pass [ ] Fail

---

### Test 7.3: Pagination Works
**Steps:**
1. (If you have 50+ changes) Navigate to page 2
2. Click Previous/Next buttons

**Expected:** Pagination controls work correctly

**Result:** [ ] Pass [ ] Fail

---

## Edge Cases Testing

### Test 8.1: Empty Value
**Steps:**
1. Change a setting to empty string ""
2. Apply
3. Check database

**Expected:** 
- Value updated to `""` (JSON-encoded empty string)
- No error

**Result:** [ ] Pass [ ] Fail

---

### Test 8.2: Special Characters in Value
**Steps:**
1. Change `description` to include special chars:
   ```
   Client: {$client_name}
   Email: test@example.com
   Symbols: !@#$%^&*()
   ```
2. Apply
3. Check database and re-load edit page

**Expected:**
- Value saved correctly
- Special characters preserved
- JSON encoding handles escaping

**Result:** [ ] Pass [ ] Fail

---

### Test 8.3: Very Long Value
**Steps:**
1. Change `description` to 1000+ character string
2. Apply

**Expected:** No error, value saved

**Result:** [ ] Pass [ ] Fail

---

### Test 8.4: Product Doesn't Exist
**Steps:**
1. Create group with non-existent product ID (e.g., 99999)
2. Try to edit

**Expected:** 
- Either: Settings don't load (0 settings)
- Or: Error message about product not found

**Result:** [ ] Pass [ ] Fail

---

### Test 8.5: Product Not Configured with Proxmox Module
**Steps:**
1. Create a regular WHMCS product (not Proxmox)
2. Add its ID to a group
3. Try to edit

**Expected:** 
- No settings load (0 settings)
- Or clear message

**Result:** [ ] Pass [ ] Fail

---

## Forbidden Settings Testing

### Test 9.1: Forbidden Settings Not Displayed
**Steps:**
1. Select any group
2. Search for each forbidden setting:
   - additionalDiskSize
   - cores
   - cpulimit
   - cpuunits
   - diskSize
   - memory
   - vcpus
3. Verify not in list

**Expected:** None of these settings appear in edit list

**Result:** [ ] Pass [ ] Fail

---

### Test 9.2: Cannot Bypass with Direct POST
**Steps:**
1. (Technical test) Try to manually POST with forbidden setting
2. Use browser dev tools to add hidden input:
   ```html
   <input type="hidden" name="apply_cores" value="1">
   <input type="hidden" name="value_cores" value="8">
   ```
3. Submit form

**Expected:** 
- Setting not applied (verify in database)
- Or error message

**Result:** [ ] Pass [ ] Fail

---

## UI/UX Testing

### Test 10.1: Responsive Design
**Steps:**
1. View addon on desktop (1920x1080)
2. View on smaller screen (1366x768)
3. Test all pages: Home, Groups, Edit, Preview, History

**Expected:** UI is usable on different screen sizes

**Result:** [ ] Pass [ ] Fail

---

### Test 10.2: Loading States
**Steps:**
1. Select group with many settings
2. Observe page load time

**Expected:** 
- Page loads in reasonable time (< 5 seconds)
- No timeout errors

**Result:** [ ] Pass [ ] Fail

---

### Test 10.3: Error Messages Clear
**Steps:**
1. Intentionally cause various errors:
   - Invalid product IDs in group
   - Empty group name
   - Delete non-existent group
2. Read error messages

**Expected:** Error messages are clear and actionable

**Result:** [ ] Pass [ ] Fail

---

## Performance Testing

### Test 11.1: Large Group (50+ Products)
**Steps:**
1. Create group with 50 product IDs
2. Load edit page
3. Make changes
4. Apply

**Expected:** 
- Page loads within 10 seconds
- Apply completes without timeout
- All products updated

**Result:** [ ] Pass [ ] Fail

---

### Test 11.2: Many Settings (200+ Settings)
**Steps:**
1. Select product with all possible settings
2. Load edit page
3. Search functionality
4. Scroll through list

**Expected:** UI remains responsive

**Result:** [ ] Pass [ ] Fail

---

## Security Testing

### Test 12.1: SQL Injection
**Steps:**
1. Try to create group with name: `'; DROP TABLE mod_proxmox_bulk_groups; --`
2. Try product IDs: `1' OR '1'='1`

**Expected:** 
- Input sanitized
- No SQL error
- No data corruption

**Result:** [ ] Pass [ ] Fail

---

### Test 12.2: XSS Prevention
**Steps:**
1. Try group name: `<script>alert('XSS')</script>`
2. Try setting value: `<img src=x onerror=alert('XSS')>`
3. View pages

**Expected:** 
- Scripts not executed
- HTML entities escaped

**Result:** [ ] Pass [ ] Fail

---

### Test 12.3: Access Control
**Steps:**
1. Deactivate module
2. Try to access directly: `/admin/addonmodules.php?module=proxmox_bulk_vm_setting`

**Expected:** Access denied or module not available

**Result:** [ ] Pass [ ] Fail

---

## Compatibility Testing

### Test 13.1: PHP 8.1 Compatibility
**Steps:**
1. Run on PHP 8.1 server
2. Test all features
3. Check error logs

**Expected:** No PHP warnings or errors

**Result:** [ ] Pass [ ] Fail

---

### Test 13.2: WHMCS 8.x Compatibility
**Steps:**
1. Run on WHMCS 8.0+ installation
2. Test all features

**Expected:** All features work correctly

**Result:** [ ] Pass [ ] Fail

---

### Test 13.3: Database Compatibility
**Steps:**
1. Test on MySQL 5.7
2. Test on MySQL 8.0
3. Test on MariaDB 10.x

**Expected:** Works on all versions

**Result:** [ ] Pass [ ] Fail

---

## Data Integrity Testing

### Test 14.1: No Data Loss on Update
**Steps:**
1. Export all settings for a product
2. Apply changes
3. Export again
4. Compare: only changed settings differ

**Expected:** Unchanged settings remain unchanged

**Result:** [ ] Pass [ ] Fail

---

### Test 14.2: Concurrent Edits
**Steps:**
1. Admin 1: Start editing group A
2. Admin 2: Start editing group A
3. Admin 1: Apply changes
4. Admin 2: Apply different changes
5. Check final state

**Expected:** 
- Both changes applied (last write wins)
- No corruption
- Both logged in history

**Result:** [ ] Pass [ ] Fail

---

### Test 14.3: Rollback Test
**Steps:**
1. Export settings
2. Apply bad changes
3. Use CSV to identify old values
4. Manually restore old values

**Expected:** Can restore from CSV backup

**Result:** [ ] Pass [ ] Fail

---

## Final Checklist

Before going to production:

- [ ] All critical tests (Test 1.1, 2.1, 3.1, 5.1, 6.1, 6.2, 7.1, 9.1) pass
- [ ] No PHP errors in logs
- [ ] Database backup tested and working
- [ ] CSV export works correctly
- [ ] Change history logging works
- [ ] Forbidden settings cannot be edited
- [ ] JSON encoding/decoding correct
- [ ] Admin access control configured
- [ ] Documentation reviewed
- [ ] At least one successful bulk edit in staging
- [ ] Rollback procedure tested

## Test Results Summary

**Date Tested:** _______________  
**Tested By:** _______________  
**Environment:** _______________  

**Total Tests:** 50  
**Passed:** _____  
**Failed:** _____  
**Skipped:** _____  

**Critical Issues Found:** _____  
**Minor Issues Found:** _____  

**Ready for Production?** [ ] Yes [ ] No

**Notes:**
_______________________________________________
_______________________________________________
_______________________________________________

---

## Production Deployment Checklist

After all tests pass:

1. [ ] Create production database backup
2. [ ] Upload module to production
3. [ ] Activate and configure access control
4. [ ] Create initial groups with production product IDs
5. [ ] Test with ONE product first
6. [ ] Document which groups control which products
7. [ ] Train other administrators
8. [ ] Set up monitoring/logging
9. [ ] Schedule regular database backups
10. [ ] Keep staging environment for future testing

**Good luck with your deployment! 🚀**

