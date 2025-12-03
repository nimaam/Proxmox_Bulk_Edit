# 🎉 Version 1.1.0 Release Notes

**Release Date:** December 2, 2024  
**Version:** 1.1.0  
**Type:** Feature Release  

---

## 🌟 What's New

### **Major Feature: Multi-Module Support!**

Version 1.1.0 adds full support for **both Proxmox module types**:

✅ **Proxmox VE VPS Cloud** (ProxmoxVeVpsCloud)  
✅ **Proxmox VE VPS / Addon** (ProxmoxAddon)  

You can now **select which module you're using** in the addon configuration, and the addon will automatically work with the correct database table!

---

## 📸 What It Looks Like

### **Before (v1.0.1):**
- ❌ Only supported Cloud version
- ❌ Had to manually edit code to change modules
- ❌ No indication of which module was active

### **After (v1.1.0):**
- ✅ Supports both Cloud and Addon versions
- ✅ Easy dropdown selection in configuration
- ✅ Shows current module at top of every page
- ✅ No code editing required to switch modules

---

## 🔧 How It Works

### **Configuration Screen:**

When you go to **Setup → Addon Modules → Proxmox Bulk VM Setting → Configure**, you'll now see:

```
┌─────────────────────────────────────────────────┐
│ Proxmox Module Type                             │
│                                                 │
│ Select which Proxmox module you are using...   │
│                                                 │
│ ▼ Proxmox VE VPS Cloud (ProxmoxVeVpsCloud)    │
│   Proxmox VE VPS (ProxmoxAddon)                │
│                                                 │
│ [Save Changes]                                  │
└─────────────────────────────────────────────────┘
```

### **UI Indicator:**

On every page of the addon, you'll see:

```
┌─────────────────────────────────────────────────────────┐
│ ℹ Current Module: Proxmox VE VPS Cloud                 │
│   (ProxmoxVeVpsCloud)     [Change in module settings]  │
└─────────────────────────────────────────────────────────┘
```

### **Behind the Scenes:**

The addon automatically:
1. Reads your module selection from configuration
2. Determines the correct database table:
   - **Cloud** → `ProxmoxVeVpsCloud_ProductConfiguration`
   - **Addon** → `ProxmoxAddon_ProductConfiguration`
3. Uses the correct table for all operations

---

## 🚀 Key Benefits

### **For Cloud Users (ProxmoxVeVpsCloud):**
- ✅ Works exactly as before (backward compatible)
- ✅ Default selection (no configuration change needed)
- ✅ All existing groups and settings preserved

### **For Addon Users (ProxmoxAddon):**
- ✅ **NOW SUPPORTED!** No more manual code editing
- ✅ Just select "Addon" in configuration
- ✅ Everything works the same way

### **For Everyone:**
- ✅ Easy switching between modules if needed
- ✅ Clear indication of which module is active
- ✅ Future-proof for additional modules

---

## 📋 What's Changed

### **New Configuration Field:**
- Added `proxmox_module` dropdown in addon settings
- Options: Cloud or Addon
- Default: Cloud (for backward compatibility)

### **Dynamic Table Support:**
- `ProductConfigManager` now uses dynamic table names
- Automatically selects correct table based on configuration
- No hardcoded table names

### **UI Improvements:**
- Module indicator on every page
- Link to change module in settings
- Clear visual feedback

### **Version Bump:**
- Updated from 1.0.1 to 1.1.0
- New CHANGELOG entries
- Updated documentation

---

## 🔄 Upgrade Path

### **From 1.0.1 to 1.1.0:**

**For Existing Cloud Users:**
1. Upload new files
2. Clear caches
3. **No configuration needed** - will continue using Cloud by default

**For New Addon Users:**
1. Upload new files
2. Clear caches
3. Configure: Select "Proxmox VE VPS (ProxmoxAddon)"
4. Start using!

See **UPGRADE_TO_1.1.md** for detailed instructions.

---

## 📊 Compatibility

### **Supported:**
- ✅ WHMCS 8.0+
- ✅ PHP 8.1+
- ✅ Both Proxmox modules
- ✅ Upgrade from 1.0.0 or 1.0.1

### **Tested:**
- ✅ Fresh installation
- ✅ Upgrade from 1.0.1
- ✅ Switching between modules
- ✅ Existing groups preserved
- ✅ All features working

### **Backward Compatible:**
- ✅ Existing installations work without changes
- ✅ All existing groups preserved
- ✅ Change history maintained
- ✅ No database migrations required

---

## 🐛 Bug Fixes

### **Carried over from 1.0.1:**
- ✅ Object/array type handling fixed
- ✅ Proper type casting for database results
- ✅ All query methods return arrays consistently

### **New in 1.1.0:**
- ✅ No new bugs introduced
- ✅ All existing functionality preserved
- ✅ Additional testing completed

---

## 📚 Documentation Updates

### **New Files:**
- `UPGRADE_TO_1.1.md` - Detailed upgrade guide
- `VERSION_1.1.0_RELEASE_NOTES.md` - This file

### **Updated Files:**
- `README.md` - Added multi-module section
- `CHANGELOG.md` - Version 1.1.0 entries
- `SUMMARY.md` - Updated feature list

---

## 🎯 Use Cases

### **Use Case 1: Cloud User (Default)**
```
Scenario: Using Proxmox VE VPS Cloud
Action: Nothing! Works automatically
Result: Uses ProxmoxVeVpsCloud_ProductConfiguration table
```

### **Use Case 2: Addon User**
```
Scenario: Using Proxmox VE VPS (non-Cloud)
Action: Configure → Select "Addon" → Save
Result: Uses ProxmoxAddon_ProductConfiguration table
```

### **Use Case 3: Mixed Environment**
```
Scenario: Have both modules (different servers)
Action: Can switch between them as needed
Result: Select appropriate module for current products
```

---

## ⚡ Performance

### **Impact:**
- ✅ **Negligible** performance impact
- ✅ One extra database query on initialization (cached)
- ✅ All other operations same speed as before

### **Optimization:**
- Table name determined once per request
- Cached in object instance
- No performance degradation

---

## 🔒 Security

### **No Changes:**
- ✅ All existing security measures maintained
- ✅ Input validation unchanged
- ✅ XSS/SQL injection prevention unchanged
- ✅ Admin authentication required

### **Additional:**
- ✅ Configuration setting validated
- ✅ Table name sanitized
- ✅ No user input affects table selection

---

## 🧪 Testing

### **Test Coverage:**
- ✅ Module selection and saving
- ✅ Table name switching
- ✅ Cloud module operations
- ✅ Addon module operations
- ✅ Switching between modules
- ✅ Existing group preservation
- ✅ Settings load/save
- ✅ Preview and apply
- ✅ Change history
- ✅ CSV export

### **Tested Scenarios:**
- ✅ Fresh installation (Cloud)
- ✅ Fresh installation (Addon)
- ✅ Upgrade from 1.0.1 (Cloud)
- ✅ Upgrade from 1.0.1 then switch to Addon
- ✅ Multiple groups
- ✅ Multiple products
- ✅ All features functional

---

## 📞 Support

### **Need Help?**

1. **Upgrade Issues:** See `UPGRADE_TO_1.1.md`
2. **Configuration Help:** See `README.md` Configuration section
3. **General Issues:** See `TROUBLESHOOTING.md`
4. **Testing:** See `TESTING.md`

### **Reporting Issues:**

If you find a bug:
1. Check which module type you're using
2. Verify correct table exists in database
3. Check configuration is saved
4. Clear all caches
5. Provide details: WHMCS version, PHP version, module type

---

## 🎁 Thank You!

Thank you for using Proxmox Bulk VM Setting!

This release brings the most requested feature - support for both Proxmox module types. We hope it makes your WHMCS management even easier!

### **What's Next?**

Version 1.2.0 will include:
- Import groups from CSV
- Setting templates
- Bulk copy settings between products
- And more!

Stay tuned! 🚀

---

## 📝 Quick Start

### **New Installation:**
1. Upload files
2. Activate in WHMCS
3. Configure module type
4. Create groups
5. Start bulk editing!

### **Upgrade from 1.0.1:**
1. Backup
2. Upload new files
3. Clear caches
4. Verify module type (Cloud is default)
5. Test!

**That's it!** 🎉

---

**Version:** 1.1.0  
**Released:** December 2, 2024  
**License:** MIT  
**Compatibility:** WHMCS 8.0+, PHP 8.1+  

Happy bulk editing! 🚀

