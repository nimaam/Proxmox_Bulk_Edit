# Module Differences: ProxmoxAddon vs ProxmoxVeVpsCloud

This document outlines the key differences between the two supported Proxmox modules.

## Database Structure

### ProxmoxAddon_ProductConfiguration
```sql
Columns: product_id, setting, value
Type column: NO
```

### ProxmoxVeVpsCloud_ProductConfiguration
```sql
Columns: product_id, type, setting, value
Type column: YES (values: 'product', 'configoption', etc.)
```

---

## Settings Comparison

### Common Settings (Present in Both)

Both modules share these core settings:
- `acpi`, `additionalDisk*`, `aes`, `agent`, `autostart`
- `backup*`, `bridge`, `cores`, `cpu`, `cpulimit`, `cpuunits`
- `disk*`, `firewall*`, `memory`, `network*`
- `permission*`, `sockets`, `vcpus`
- And many more...

### Unique to ProxmoxAddon

Settings that ONLY exist in ProxmoxAddon:
- `agentPassword` - Agent password configuration
- `buttonSyle` - UI button style (tiles/list)
- `calculateSocketsAndCores` - Auto-calculation setting
- `detailsView` - View type (standard/advanced)
- `permissionArchive` - Archive permission
- `permissionBios` - BIOS permission
- `permissionCustomTemplates` - Custom templates permission
- `permissionPassword` - Password permission
- `permissionUpgrade` - Upgrade permission
- `permissionUsername` - Username permission
- `server*` - Server resource limits:
  - `serverCores`, `serverCpulimit`, `serverCpuunit`
  - `serverDiskSize`, `serverMemory`, `serverSockets`
  - `serverIpv4`, `serverIpv6`, `serverVcpus`
  - `serverVirtualInterfaces`
- `storageSize` - Storage size value
- `storageUnit` - Storage unit (gb/tb)

### Unique to ProxmoxVeVpsCloud

Settings that ONLY exist in ProxmoxVeVpsCloud:
- `additionalDiskSize` - Additional disk size configuration
- `agentFreezeFsOnBackup` - Freeze filesystem on backup
- `agentGuestTrim` - Guest trim configuration
- `agentServiceHostname` - Service hostname
- `agentTemplateUser` - Template user
- `alternativeMode` - Alternative mode array
- `app` - Application configuration
- `app_group_name` - Application group name
- `autoAssignPrivateIp` - Auto-assign private IP
- `autoCreatePrivateNetwork` - Auto-create private network
- `backupProtectedFiles` - Protected backup files
- `backupScheduleEmailTemplateId` - Email template ID
- `backupScheduleTemplate` - Schedule template
- `clientAreaSectionsOrder` - Client area order
- `clientNameForContainer` - Container name
- `cloudInitService*` - Cloud-init service settings
- `cloudInitTemplateUser` - Cloud-init template user
- `loadBalancerOnUpgrade` - Load balancer upgrade setting
- `managedView` - Managed view option
- `oneUserPerVps` - One user per VPS
- `permissionAllVmsBackups` - All VMs backup permission
- `permissionBackupSchedule` - Backup schedule permission
- `permissionChangeHostname` - Change hostname permission
- `permissionDownloadBackupFile` - Download backup permission
- `permissionGraph` - Graph permission
- `permissionInformation` - Information array permission
- `permissionResourcesNotification` - Resources notification permission
- `permissionRestoreBackupFile` - Restore backup permission
- `permissionServerMonitoring` - Server monitoring permission
- `permissionSnapshotJobPeriod` - Snapshot job period
- `permissionTaskHistory` - Task history permission
- `permissionVmPowerTasks` - VM power tasks permission
- `privateBridges` - Private bridges array
- `privateNetworkDhcp` - Private network DHCP
- `productType` - Product type (vps/cloud)
- `protectedBackupsCalcMethod` - Protected backups calculation
- `reassignPrivateNetwork` - Reassign private network
- `reassignPublicNetwork` - Reassign public network
- `rebootVmAfterChangePackage` - Reboot after package change
- `reinstallEmailTemplateId` - Reinstall email template
- `resourcesNotificationEmailTemplateId` - Resources notification email
- `serverNameservers` - Server nameservers
- `serviceCreatedSuccessfullyTemplateId` - Success email template
- `serviceCreationFailedTemplateId` - Failed email template
- `toDoList` - To-do list feature
- `tpm_storage` - TPM storage location
- `tpm_version` - TPM version
- `upgradeNotificationTemplateId` - Upgrade notification email
- `welcomeEmailTemplateId` - Welcome email template

---

## Value Format Differences

### Both Modules Use:
- JSON encoding for values
- `"on"` / `"off"` for booleans
- `["value1","value2"]` for arrays
- `""` for empty values

### No Significant Format Differences
Both modules use the same JSON encoding pattern.

---

## Forbidden Settings (Should Not Be Edited)

### Common Across Both Modules:
- `cores` - CPU cores allocation
- `cpulimit` - CPU limit
- `cpuunits` - CPU units/weight
- `memory` - RAM allocation
- `vcpus` - Virtual CPUs

### Module-Specific Forbidden:

**ProxmoxAddon:**
- `storageSize` - Storage size (use diskSize instead)

**ProxmoxVeVpsCloud:**
- `additionalDiskSize` - Additional disk size
- `diskSize` - Main disk size

---

## UI Rendering Recommendations

### ProxmoxAddon-Specific Fields

**Server Resource Limits** (Read-only or special handling):
```
serverCores, serverCpulimit, serverCpuunit, serverDiskSize,
serverMemory, serverSockets, serverIpv4, serverIpv6, serverVcpus
```
Format: "min-max" (e.g., "1-20", "512-4096")

**Storage Configuration**:
- `storageSize` - Numeric value
- `storageUnit` - Dropdown (gb/tb)

**View Options**:
- `buttonSyle` - Dropdown (tiles/list)
- `detailsView` - Dropdown (standard/advanced)

### ProxmoxVeVpsCloud-Specific Fields

**Email Templates** (Numeric IDs):
```
backupScheduleEmailTemplateId, reinstallEmailTemplateId,
resourcesNotificationEmailTemplateId, serviceCreatedSuccessfullyTemplateId,
serviceCreationFailedTemplateId, upgradeNotificationTemplateId, welcomeEmailTemplateId
```

**Product Type**:
- `productType` - Dropdown (vps/cloud)

**Alternative Mode**:
- `alternativeMode` - JSON array

---

## Module Selection in UI

The addon now shows which module is active:

### For ProxmoxAddon:
```
ℹ Current Module: Proxmox VE VPS (ProxmoxAddon)
Table: ProxmoxAddon_ProductConfiguration
```

### For ProxmoxVeVpsCloud:
```
ℹ Current Module: Proxmox VE VPS Cloud (ProxmoxVeVpsCloud)
Table: ProxmoxVeVpsCloud_ProductConfiguration
```

---

## Implementation Notes

### Database Query Differences

**ProxmoxAddon:**
```sql
SELECT * FROM ProxmoxAddon_ProductConfiguration 
WHERE product_id = ?
```

**ProxmoxVeVpsCloud:**
```sql
SELECT * FROM ProxmoxVeVpsCloud_ProductConfiguration 
WHERE product_id = ? AND type = 'product'
```

### Code Implementation

The addon automatically handles these differences:
- Checks if `type` column exists
- Adds `WHERE type = 'product'` only for Cloud version
- Inserts `type` column only for Cloud version
- All other logic remains the same

---

## Compatibility Matrix

| Feature | ProxmoxAddon | ProxmoxVeVpsCloud |
|---------|--------------|-------------------|
| Basic Settings | ✅ | ✅ |
| Permission Settings | ✅ | ✅ |
| Backup Settings | ✅ | ✅ |
| Firewall Settings | ✅ | ✅ |
| Network Settings | ✅ | ✅ |
| Cloud-Init | ✅ | ✅ |
| Load Balancer | ✅ | ✅ |
| TPM Support | ✅ | ✅ |
| Server Resource Limits | ✅ | ❌ |
| Email Templates | ❌ | ✅ |
| Product Types | ❌ | ✅ |
| Alternative Modes | ❌ | ✅ |
| App Center Integration | ❌ | ✅ |

---

## Migration Considerations

### From ProxmoxAddon to ProxmoxVeVpsCloud

Not recommended - different feature sets. Would require:
1. Export all settings from ProxmoxAddon
2. Map settings to ProxmoxVeVpsCloud equivalents
3. Handle unique settings (server limits → configurable options)
4. Add `type` column data
5. Rebuild product configurations

### From ProxmoxVeVpsCloud to ProxmoxAddon

Not recommended - loss of Cloud-specific features:
1. Export compatible settings only
2. Drop Cloud-specific settings (email templates, app integration)
3. Remove `type` column
4. Reconfigure products in ProxmoxAddon

### Best Practice

**Choose one module and stick with it.** Switching modules requires complete reconfiguration of products.

---

## Testing Checklist

When testing with either module:

### ProxmoxAddon:
- [ ] Settings load without `type` filter
- [ ] Updates work without `type` column
- [ ] Server resource limits visible
- [ ] Storage size/unit fields work
- [ ] All permissions load correctly

### ProxmoxVeVpsCloud:
- [ ] Settings load with `type = 'product'` filter
- [ ] Updates include `type` column
- [ ] Email template fields visible
- [ ] Alternative mode array works
- [ ] Product type dropdown works
- [ ] App integration settings visible

---

## Summary

Both modules are now fully supported with automatic detection of:
- Table structure differences
- Column presence (`type` column)
- Module-specific settings

The addon handles all differences transparently - you just select which module you're using in the configuration, and everything works automatically!

---

**Last Updated:** December 2, 2024  
**Addon Version:** 1.1.0+  
**Supported Modules:** Both ProxmoxAddon and ProxmoxVeVpsCloud

