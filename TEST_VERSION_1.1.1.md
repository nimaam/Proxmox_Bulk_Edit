# Test Plan for Version 1.1.1

## Critical Bug Fix Verification

This version fixes the "Updated 0 settings" bug. Follow these tests to verify.

---

## 🧪 **Test 1: Numeric Value Change**

### **Setup:**
- Product ID: 12 (or any from your group)
- Setting: `ipv4`
- Current value in DB: `"1"` (JSON-encoded)

### **Steps:**
1. Go to: Addons → Proxmox Bulk VM Setting
2. Select your group
3. Find `ipv4` setting (should show value: `1`)
4. Change to: `2`
5. Ensure "Apply" checkbox is checked
6. Click **Preview Changes**

### **Expected Preview:**

```
Product ID | Setting | Current Value | New Value | Database Format
12         | ipv4    | 1             | 2         | "1" → "2"
13         | ipv4    | 1             | 2         | "1" → "2"
...
```

**Key Points:**
- ✅ Current Value shows: `1` (decoded, no quotes)
- ✅ New Value shows: `2` (your input)
- ✅ Database Format shows: `"1" → "2"` (with quotes - JSON encoded)

### **Apply:**
7. Click **Confirm & Apply Changes**

### **Expected Result:**
```
✅ Changes Applied Successfully!
Updated 1 setting(s) across 8 product(s).
```

### **Verify in Database:**
```sql
SELECT product_id, value 
FROM ProxmoxAddon_ProductConfiguration 
WHERE setting = 'ipv4' 
AND product_id IN (12,13,14,15,16,17,18,19);
```

**Expected:** All values should be `"2"` (with quotes, JSON-encoded)

---

## 🧪 **Test 2: Toggle Value Change**

### **Setup:**
- Setting: `autostart`
- Current: `off`

### **Steps:**
1. Find `autostart` (should be dropdown)
2. Change from `off` to `on`
3. Preview

### **Expected Preview:**
```
Setting    | Current Value | New Value | Database Format
autostart  | off           | on        | "off" → "on"
```

### **Apply & Verify:**
```sql
SELECT value FROM ProxmoxAddon_ProductConfiguration 
WHERE product_id = 12 AND setting = 'autostart';
```

**Expected:** `"on"` (JSON-encoded)

---

## 🧪 **Test 3: Text Value Change**

### **Setup:**
- Setting: `userComment`
- Current: Any text

### **Steps:**
1. Find `userComment`
2. Change to: `TEST HOTFIX 1.1.1`
3. Preview

### **Expected Preview:**
```
Setting     | Current Value | New Value           | Database Format
userComment | [old text]    | TEST HOTFIX 1.1.1   | "[old]" → "TEST HOTFIX 1.1.1"
```

### **Apply & Verify:**
```sql
SELECT value FROM ProxmoxAddon_ProductConfiguration 
WHERE product_id = 12 AND setting = 'userComment';
```

**Expected:** `"TEST HOTFIX 1.1.1"` (JSON-encoded with quotes)

---

## 🧪 **Test 4: Multiple Settings at Once**

### **Steps:**
1. Change 3 settings:
   - `ipv4`: `1` → `2`
   - `autostart`: `off` → `on`
   - `userComment`: `old` → `NEW TEXT`
2. Preview

### **Expected Preview:**
Shows all 3 settings × 8 products = **24 total changes**

### **Apply:**
Should show: "Updated 3 setting(s) across 8 product(s)"

**Note:** This means 24 individual database updates, but displayed as 3 settings × 8 products.

---

## 🧪 **Test 5: Array Value Change**

### **Setup:**
- Setting: `tags` (array field)
- Current: `[]`

### **Steps:**
1. Find `tags` setting
2. Change to: `["web","production"]`
3. Preview

### **Expected Preview:**
```
Setting | Current Value | New Value              | Database Format
tags    | []            | ["web","production"]   | "[]" → "[\"web\",\"production\"]"
```

### **Apply & Verify:**
```sql
SELECT value FROM ProxmoxAddon_ProductConfiguration 
WHERE product_id = 12 AND setting = 'tags';
```

**Expected:** `"[\"web\",\"production\"]"` (double-encoded as per Proxmox format)

---

## 🧪 **Test 6: No Change Detection**

### **Steps:**
1. Find `ipv4` (current value: `1`)
2. Change to: `1` (same as current)
3. Preview

### **Expected:**
```
ℹ No changes detected. All values are already up to date.
```

This proves the comparison is working correctly!

---

## 🧪 **Test 7: Partial Selection**

### **Steps:**
1. Change 3 settings
2. Uncheck "Apply" for 2 of them
3. Preview

### **Expected:**
Only 1 setting × 8 products shown in preview

### **Apply:**
Should update only the 1 checked setting

---

## 📊 **Expected vs Actual**

### **Before Fix (v1.1.0):**
```
User enters: 2
Preview shows: "1" (old value repeated)
Comparison: "1" !== 2 → FALSE (wrong comparison)
Result: Updated 0 setting(s) ❌
```

### **After Fix (v1.1.1):**
```
User enters: 2
Encoded immediately: "2"
Preview shows: 
  - Display: 1 → 2 (human readable)
  - Database: "1" → "2" (exact format)
Comparison: "1" !== "2" → TRUE ✅
Result: Updated 1 setting(s) across 8 product(s) ✅
Database: "2" (exact match to preview) ✅
```

---

## ✅ **Success Criteria**

All tests must pass:

- [ ] Preview shows different old/new values
- [ ] Preview shows "Database Format" column
- [ ] Database Format column shows JSON-encoded values
- [ ] Apply shows "Updated X setting(s)" where X > 0
- [ ] Database values actually change
- [ ] Values in database are JSON-encoded (with quotes)
- [ ] Change history logs the changes
- [ ] Can change numeric values (ipv4: 1→2)
- [ ] Can change toggle values (autostart: off→on)
- [ ] Can change text values (userComment)
- [ ] Can change array values (tags)

---

## 🔍 **Debugging Checklist**

If any test fails:

1. **Check version:**
   ```bash
   grep "version.*1.1.1" /path/to/proxmox_bulk_vm_setting.php
   ```

2. **Check the fix is there:**
   ```bash
   grep "encodedNewValue = json_encode" /path/to/proxmox_bulk_vm_setting.php
   ```
   Should find the line

3. **Clear ALL caches:**
   ```bash
   /opt/cpanel/ea-php81/root/usr/bin/php -r "opcache_reset();"
   sudo systemctl restart ea-php81-php-fpm
   rm -rf /path/to/whmcs/templates_c/*
   ```

4. **Check database:**
   ```sql
   -- See current format
   SELECT setting, value, LENGTH(value) 
   FROM ProxmoxAddon_ProductConfiguration 
   WHERE product_id = 12 
   LIMIT 5;
   ```

5. **Check permissions:**
   ```sql
   -- Try manual update
   UPDATE ProxmoxAddon_ProductConfiguration 
   SET value = '"999"' 
   WHERE product_id = 12 AND setting = 'ipv4';
   
   -- Check if it worked
   SELECT value FROM ProxmoxAddon_ProductConfiguration 
   WHERE product_id = 12 AND setting = 'ipv4';
   
   -- Revert
   UPDATE ProxmoxAddon_ProductConfiguration 
   SET value = '"1"' 
   WHERE product_id = 12 AND setting = 'ipv4';
   ```

---

## 📝 **What to Report**

If tests still fail, provide:

1. **Screenshot of preview page** showing:
   - Old Value column
   - New Value column
   - Database Format column

2. **Version check:**
   ```
   Setup → Addon Modules → Version number shown
   ```

3. **Database query result:**
   ```sql
   SELECT value FROM ProxmoxAddon_ProductConfiguration 
   WHERE product_id = 12 AND setting = 'ipv4';
   ```

4. **Change history:**
   - Go to Change History
   - Any entries for your test changes?

---

## 🎯 **Expected Database Format**

After changing `ipv4` from `1` to `2`, database should have:

```sql
value = "2"
```

**NOT:**
- ~~`2`~~ (missing quotes)
- ~~`""2""`~~ (double-encoded)
- ~~`"\"2\""`~~ (triple-encoded)

Just: `"2"` (single JSON-encoded string)

---

**Good luck with testing! This fix should resolve the "0 updates" issue completely.** ✅

