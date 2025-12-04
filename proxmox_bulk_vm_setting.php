<?php
/**
 * Proxmox Bulk VM Setting - WHMCS Addon Module
 *
 * Allows bulk editing of Proxmox VE VPS Cloud product configurations
 *
 * @copyright Copyright (c) 2024
 * @license MIT
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

// Autoload function for our classes
spl_autoload_register(function ($class) {
    if (strpos($class, 'ProxmoxBulkVmSetting\\') === 0) {
        $className = str_replace('ProxmoxBulkVmSetting\\', '', $class);
        $file = __DIR__ . '/lib/' . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

use ProxmoxBulkVmSetting\Database;
use ProxmoxBulkVmSetting\GroupManager;
use ProxmoxBulkVmSetting\ProductConfigManager;
use ProxmoxBulkVmSetting\ChangeLogger;
use ProxmoxBulkVmSetting\CsvExporter;

/**
 * Module configuration
 */
function proxmox_bulk_vm_setting_config()
{
    return [
        'name' => 'Proxmox Bulk VM Setting',
        'description' => 'Bulk edit Proxmox VE VPS product configurations across multiple products',
        'version' => '1.1.1',
        'author' => 'Custom Development',
        'fields' => [
            'proxmox_module' => [
                'FriendlyName' => 'Proxmox Module Type',
                'Type' => 'dropdown',
                'Options' => [
                    'cloud' => 'Proxmox VE VPS Cloud (ProxmoxVeVpsCloud)',
                    'addon' => 'Proxmox VE VPS (ProxmoxAddon)'
                ],
                'Default' => 'cloud',
                'Description' => 'Select which Proxmox module you are using. This determines which database table to use for product configurations.'
            ]
        ]
    ];
}

/**
 * Activate module - create database tables
 */
function proxmox_bulk_vm_setting_activate()
{
    try {
        // Create tables directly without using external dependencies
        $pdo = \Illuminate\Database\Capsule\Manager::connection()->getPdo();
        
        // Create groups table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `mod_proxmox_bulk_groups` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `product_ids` text NOT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        // Create change log table
        $pdo->exec("
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
        ");
        
        return [
            'status' => 'success',
            'description' => 'Proxmox Bulk VM Setting addon activated successfully. Database tables created.'
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Failed to activate: ' . $e->getMessage()
        ];
    }
}

/**
 * Deactivate module
 */
function proxmox_bulk_vm_setting_deactivate()
{
    return [
        'status' => 'success',
        'description' => 'Proxmox Bulk VM Setting addon deactivated. Database tables preserved.'
    ];
}

/**
 * Upgrade module
 */
function proxmox_bulk_vm_setting_upgrade($vars)
{
    $currentVersion = $vars['version'];
    // Future version upgrades can be handled here
    return [];
}

/**
 * Main admin area output
 */
function proxmox_bulk_vm_setting_output($vars)
{
    $action = $_REQUEST['action'] ?? 'home';
    
    echo '<link rel="stylesheet" href="' . $vars['modulelink'] . '&action=css" />';
    
    try {
        switch ($action) {
            case 'groups':
                renderGroupsPage($vars);
                break;
            
            case 'save_group':
                saveGroup($vars);
                break;
            
            case 'delete_group':
                deleteGroup($vars);
                break;
            
            case 'edit':
                renderEditPage($vars);
                break;
            
            case 'preview':
                renderPreviewPage($vars);
                break;
            
            case 'apply':
                applyChanges($vars);
                break;
            
            case 'export':
                exportToCsv($vars);
                break;
            
            case 'history':
                renderHistoryPage($vars);
                break;
            
            case 'css':
                outputCss();
                break;
            
            case 'home':
            default:
                renderHomePage($vars);
                break;
        }
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

/**
 * Render home page - group selection
 */
function renderHomePage($vars)
{
    $groupManager = new GroupManager();
    $groups = $groupManager->getAllGroups();
    
    echo '<div class="proxmox-bulk-container">';
    echo '<h2>Proxmox Bulk VM Setting</h2>';
    
    // Show current module type
    $moduleType = getModuleType($vars);
    $moduleLabel = $moduleType === 'addon' ? 'Proxmox VE VPS (ProxmoxAddon)' : 'Proxmox VE VPS Cloud (ProxmoxVeVpsCloud)';
    echo '<div class="alert alert-info" style="margin-bottom: 15px;">';
    echo '<i class="fas fa-info-circle"></i> <strong>Current Module:</strong> ' . $moduleLabel . ' ';
    echo '<small><a href="configaddonmods.php">Change in module settings</a></small>';
    echo '</div>';
    
    // Navigation
    echo '<div class="btn-group" style="margin-bottom: 20px;">';
    echo '<a href="' . $vars['modulelink'] . '" class="btn btn-primary">Bulk Editor</a>';
    echo '<a href="' . $vars['modulelink'] . '&action=groups" class="btn btn-default">Manage Groups</a>';
    echo '<a href="' . $vars['modulelink'] . '&action=history" class="btn btn-default">Change History</a>';
    echo '</div>';
    
    if (empty($groups)) {
        echo '<div class="alert alert-info">';
        echo 'No groups configured yet. ';
        echo '<a href="' . $vars['modulelink'] . '&action=groups">Create your first group</a> to get started.';
        echo '</div>';
        echo '</div>';
        return;
    }
    
    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading"><h3 class="panel-title">Select a Group to Edit</h3></div>';
    echo '<div class="panel-body">';
    
    echo '<div class="list-group">';
    foreach ($groups as $group) {
        $productIds = explode(',', $group['product_ids']);
        $productCount = count($productIds);
        
        echo '<a href="' . $vars['modulelink'] . '&action=edit&group_id=' . $group['id'] . '" class="list-group-item">';
        echo '<h4 class="list-group-item-heading">' . htmlspecialchars($group['name']) . '</h4>';
        echo '<p class="list-group-item-text">';
        echo '<strong>Products:</strong> ' . $productCount . ' product(s) - IDs: ' . htmlspecialchars($group['product_ids']);
        echo '</p>';
        echo '</a>';
    }
    echo '</div>';
    
    echo '</div></div>';
    echo '</div>';
}

/**
 * Render groups management page
 */
function renderGroupsPage($vars)
{
    $groupManager = new GroupManager();
    $groups = $groupManager->getAllGroups();
    $editGroup = null;
    
    if (isset($_GET['edit_id'])) {
        $editGroup = $groupManager->getGroupById((int)$_GET['edit_id']);
    }
    
    echo '<div class="proxmox-bulk-container">';
    echo '<h2>Manage Groups</h2>';
    
    // Navigation
    echo '<div class="btn-group" style="margin-bottom: 20px;">';
    echo '<a href="' . $vars['modulelink'] . '" class="btn btn-default">Bulk Editor</a>';
    echo '<a href="' . $vars['modulelink'] . '&action=groups" class="btn btn-primary">Manage Groups</a>';
    echo '<a href="' . $vars['modulelink'] . '&action=history" class="btn btn-default">Change History</a>';
    echo '</div>';
    
    // Add/Edit Group Form
    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading"><h3 class="panel-title">' . ($editGroup ? 'Edit Group' : 'Add New Group') . '</h3></div>';
    echo '<div class="panel-body">';
    
    echo '<form method="post" action="' . $vars['modulelink'] . '&action=save_group" class="form-horizontal">';
    
    if ($editGroup) {
        echo '<input type="hidden" name="group_id" value="' . $editGroup['id'] . '" />';
    }
    
    echo '<div class="form-group">';
    echo '<label class="col-sm-2 control-label">Group Name</label>';
    echo '<div class="col-sm-10">';
    echo '<input type="text" name="group_name" class="form-control" required ';
    echo 'value="' . htmlspecialchars($editGroup['name'] ?? '') . '" ';
    echo 'placeholder="e.g., Windows VDS" />';
    echo '<p class="help-block">A descriptive name for this group of products</p>';
    echo '</div></div>';
    
    echo '<div class="form-group">';
    echo '<label class="col-sm-2 control-label">Product IDs</label>';
    echo '<div class="col-sm-10">';
    echo '<input type="text" name="product_ids" class="form-control" required ';
    echo 'value="' . htmlspecialchars($editGroup['product_ids'] ?? '') . '" ';
    echo 'placeholder="e.g., 31,32,33,34,35,36,80" />';
    echo '<p class="help-block">Comma-separated list of product IDs from tblproducts</p>';
    echo '</div></div>';
    
    echo '<div class="form-group">';
    echo '<div class="col-sm-offset-2 col-sm-10">';
    echo '<button type="submit" class="btn btn-primary">';
    echo '<i class="fas fa-save"></i> ' . ($editGroup ? 'Update Group' : 'Create Group');
    echo '</button>';
    if ($editGroup) {
        echo ' <a href="' . $vars['modulelink'] . '&action=groups" class="btn btn-default">Cancel</a>';
    }
    echo '</div></div>';
    
    echo '</form>';
    echo '</div></div>';
    
    // Existing Groups
    if (!empty($groups)) {
        echo '<div class="panel panel-default">';
        echo '<div class="panel-heading"><h3 class="panel-title">Existing Groups</h3></div>';
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped">';
        echo '<thead><tr>';
        echo '<th>Group Name</th>';
        echo '<th>Product IDs</th>';
        echo '<th>Product Count</th>';
        echo '<th>Actions</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($groups as $group) {
            $productIds = explode(',', $group['product_ids']);
            $productCount = count($productIds);
            
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($group['name']) . '</strong></td>';
            echo '<td><code>' . htmlspecialchars($group['product_ids']) . '</code></td>';
            echo '<td>' . $productCount . '</td>';
            echo '<td>';
            echo '<a href="' . $vars['modulelink'] . '&action=groups&edit_id=' . $group['id'] . '" class="btn btn-sm btn-info">';
            echo '<i class="fas fa-edit"></i> Edit</a> ';
            echo '<a href="' . $vars['modulelink'] . '&action=delete_group&group_id=' . $group['id'] . '" ';
            echo 'class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure you want to delete this group?\');">';
            echo '<i class="fas fa-trash"></i> Delete</a>';
            echo '</td></tr>';
        }
        
        echo '</tbody></table>';
        echo '</div></div>';
    }
    
    echo '</div>';
}

/**
 * Save group (create or update)
 */
function saveGroup($vars)
{
    $groupManager = new GroupManager();
    $groupId = $_POST['group_id'] ?? null;
    $groupName = trim($_POST['group_name'] ?? '');
    $productIds = trim($_POST['product_ids'] ?? '');
    
    if (empty($groupName) || empty($productIds)) {
        echo '<div class="alert alert-danger">Group name and product IDs are required.</div>';
        renderGroupsPage($vars);
        return;
    }
    
    // Validate product IDs format
    $productIdsArray = array_map('trim', explode(',', $productIds));
    foreach ($productIdsArray as $pid) {
        if (!is_numeric($pid) || (int)$pid <= 0) {
            echo '<div class="alert alert-danger">Invalid product ID format. Use comma-separated numbers only.</div>';
            renderGroupsPage($vars);
            return;
        }
    }
    
    try {
        if ($groupId) {
            $groupManager->updateGroup((int)$groupId, $groupName, $productIds);
            echo '<div class="alert alert-success">Group updated successfully!</div>';
        } else {
            $groupManager->createGroup($groupName, $productIds);
            echo '<div class="alert alert-success">Group created successfully!</div>';
        }
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    renderGroupsPage($vars);
}

/**
 * Delete group
 */
function deleteGroup($vars)
{
    $groupId = (int)$_GET['group_id'];
    $groupManager = new GroupManager();
    
    try {
        $groupManager->deleteGroup($groupId);
        echo '<div class="alert alert-success">Group deleted successfully!</div>';
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    renderGroupsPage($vars);
}

/**
 * Render edit page for a group
 */
function renderEditPage($vars)
{
    $groupId = (int)$_GET['group_id'];
    $groupManager = new GroupManager();
    $configManager = new ProductConfigManager();
    
    $group = $groupManager->getGroupById($groupId);
    if (!$group) {
        echo '<div class="alert alert-danger">Group not found.</div>';
        renderHomePage($vars);
        return;
    }
    
    $productIds = array_map('trim', explode(',', $group['product_ids']));
    $firstProductId = (int)$productIds[0];
    
    // Get product names from WHMCS
    $products = $configManager->getProductNames($productIds);
    
    // Get settings for first product
    $settings = $configManager->getProductSettings($firstProductId);
    
    // Filter out forbidden settings (resource allocation - should not be edited)
    $moduleType = getModuleType($vars);
    
    // Common forbidden settings for both modules
    $forbiddenSettings = [
        'cores',
        'cpulimit',
        'cpuunits',
        'memory',
        'vcpus'
    ];
    
    // Module-specific forbidden settings
    if ($moduleType === 'addon') {
        // ProxmoxAddon uses storageSize instead of diskSize
        $forbiddenSettings[] = 'storageSize';
    } else {
        // ProxmoxVeVpsCloud has additional disk sizes
        $forbiddenSettings[] = 'additionalDiskSize';
        $forbiddenSettings[] = 'diskSize';
    }
    
    $editableSettings = array_filter($settings, function($setting) use ($forbiddenSettings) {
        return !in_array($setting['setting'], $forbiddenSettings, true);
    });
    
    // Get field types for better UI
    $fieldTypes = getFieldTypes();
    
    echo '<div class="proxmox-bulk-container">';
    echo '<h2>Bulk Edit: ' . htmlspecialchars($group['name']) . '</h2>';
    
    // Navigation
    echo '<div class="btn-group" style="margin-bottom: 20px;">';
    echo '<a href="' . $vars['modulelink'] . '" class="btn btn-default">Back to Groups</a>';
    echo '<a href="' . $vars['modulelink'] . '&action=export&group_id=' . $groupId . '" class="btn btn-success">';
    echo '<i class="fas fa-download"></i> Export Current Settings to CSV</a>';
    echo '</div>';
    
    // Group Info
    echo '<div class="alert alert-info">';
    echo '<strong>Products in this group (' . count($productIds) . '):</strong><br/>';
    foreach ($products as $product) {
        echo 'ID ' . $product['id'] . ': ' . htmlspecialchars($product['name']) . '<br/>';
    }
    echo '</div>';
    
    echo '<div class="alert alert-warning">';
    echo '<i class="fas fa-info-circle"></i> ';
    echo 'You are editing settings from Product ID <strong>' . $firstProductId . '</strong>. ';
    echo 'Changes will be applied to <strong>ALL ' . count($productIds) . ' products</strong> in this group.';
    echo '</div>';
    
    // Edit Form
    echo '<form method="post" action="' . $vars['modulelink'] . '&action=preview" id="bulkEditForm">';
    echo '<input type="hidden" name="group_id" value="' . $groupId . '" />';
    echo '<input type="hidden" name="first_product_id" value="' . $firstProductId . '" />';
    
    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading">';
    echo '<h3 class="panel-title">Editable Settings (' . count($editableSettings) . ' settings)</h3>';
    echo '<input type="text" id="searchSettings" class="form-control" placeholder="Search settings..." style="margin-top: 10px;" />';
    echo '</div>';
    
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped table-hover" id="settingsTable">';
    echo '<thead><tr>';
    echo '<th width="5%"><input type="checkbox" id="selectAll" checked /> Apply</th>';
    echo '<th width="30%">Setting</th>';
    echo '<th width="65%">Value</th>';
    echo '</tr></thead><tbody>';
    
    foreach ($editableSettings as $setting) {
        $settingName = htmlspecialchars($setting['setting']);
        $settingValue = htmlspecialchars($setting['decoded_value']);
        $fieldType = $fieldTypes[$setting['setting']] ?? 'text';
        
        echo '<tr class="setting-row" data-setting="' . strtolower($settingName) . '">';
        echo '<td><input type="checkbox" name="apply_' . $settingName . '" value="1" checked /></td>';
        echo '<td><code>' . $settingName . '</code>';
        
        // Add field type indicator
        if ($fieldType !== 'text') {
            echo '<br/><small class="text-muted">' . ucfirst($fieldType) . '</small>';
        }
        
        echo '</td>';
        echo '<td>';
        
        // Render appropriate input based on field type
        renderFieldInput($settingName, $settingValue, $fieldType);
        
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>';
    
    echo '<div class="panel-footer">';
    echo '<button type="submit" class="btn btn-primary btn-lg">';
    echo '<i class="fas fa-eye"></i> Preview Changes';
    echo '</button>';
    echo '</div>';
    
    echo '</div></form>';
    
    // JavaScript for search
    echo '<script>
    document.getElementById("searchSettings").addEventListener("keyup", function() {
        var filter = this.value.toLowerCase();
        var rows = document.querySelectorAll(".setting-row");
        rows.forEach(function(row) {
            var setting = row.getAttribute("data-setting");
            if (setting.indexOf(filter) > -1) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    });
    
    document.getElementById("selectAll").addEventListener("change", function() {
        var checkboxes = document.querySelectorAll("input[name^=\'apply_\']");
        checkboxes.forEach(function(cb) {
            cb.checked = this.checked;
        }, this);
    });
    </script>';
    
    echo '</div>';
}

/**
 * Render preview page
 */
function renderPreviewPage($vars)
{
    $groupId = (int)$_POST['group_id'];
    $firstProductId = (int)$_POST['first_product_id'];
    
    $groupManager = new GroupManager();
    $configManager = new ProductConfigManager();
    
    $group = $groupManager->getGroupById($groupId);
    if (!$group) {
        echo '<div class="alert alert-danger">Group not found.</div>';
        renderHomePage($vars);
        return;
    }
    
    $productIds = array_map('trim', explode(',', $group['product_ids']));
    $products = $configManager->getProductNames($productIds);
    
    // Build changes array - encode values for database comparison
    $changes = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'apply_') === 0) {
            $settingName = substr($key, 6);
            $newValue = $_POST['value_' . $settingName] ?? '';
            // Encode the value to match database format
            $encodedNewValue = json_encode($newValue);
            $changes[$settingName] = $encodedNewValue;
        }
    }
    
    if (empty($changes)) {
        echo '<div class="alert alert-warning">No settings selected for update.</div>';
        renderEditPage($vars);
        return;
    }
    
    // Get current values for all products
    $preview = [];
    foreach ($productIds as $productId) {
        $currentSettings = $configManager->getProductSettings((int)$productId);
        $currentSettingsMap = [];
        foreach ($currentSettings as $s) {
            // Use raw value for comparison (exact database format)
            $currentSettingsMap[$s['setting']] = $s['value'];
        }
        
        foreach ($changes as $settingName => $encodedNewValue) {
            $oldEncodedValue = $currentSettingsMap[$settingName] ?? '';
            
            // Compare encoded values (exact database format)
            if ($oldEncodedValue !== $encodedNewValue) {
                // Decode for display in preview
                $oldDecoded = json_decode($oldEncodedValue, true);
                $newDecoded = json_decode($encodedNewValue, true);
                
                // Handle arrays
                if (is_array($oldDecoded)) {
                    $oldDecoded = json_encode($oldDecoded, JSON_UNESCAPED_SLASHES);
                }
                if (is_array($newDecoded)) {
                    $newDecoded = json_encode($newDecoded, JSON_UNESCAPED_SLASHES);
                }
                
                $preview[] = [
                    'product_id' => $productId,
                    'setting' => $settingName,
                    'old_value' => $oldDecoded ?? $oldEncodedValue,
                    'new_value' => $newDecoded ?? $encodedNewValue,
                    'old_encoded' => $oldEncodedValue,
                    'new_encoded' => $encodedNewValue
                ];
            }
        }
    }
    
    echo '<div class="proxmox-bulk-container">';
    echo '<h2>Preview Changes: ' . htmlspecialchars($group['name']) . '</h2>';
    
    echo '<div class="alert alert-warning">';
    echo '<i class="fas fa-exclamation-triangle"></i> ';
    echo '<strong>Review carefully before applying!</strong> ';
    echo 'The following changes will be made to the database.';
    echo '</div>';
    
    if (empty($preview)) {
        echo '<div class="alert alert-info">No changes detected. All values are already up to date.</div>';
        echo '<a href="' . $vars['modulelink'] . '&action=edit&group_id=' . $groupId . '" class="btn btn-default">Back to Editor</a>';
        echo '</div>';
        return;
    }
    
    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading">';
    echo '<h3 class="panel-title">Changes Summary: ' . count($preview) . ' update(s) will be made</h3>';
    echo '</div>';
    
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped table-condensed">';
    echo '<thead><tr>';
    echo '<th>Product ID</th>';
    echo '<th>Product Name</th>';
    echo '<th>Setting</th>';
    echo '<th>Current Value</th>';
    echo '<th>New Value</th>';
    echo '<th>Database Format</th>';
    echo '</tr></thead><tbody>';
    
    foreach ($preview as $change) {
        $productName = '';
        foreach ($products as $p) {
            if ($p['id'] == $change['product_id']) {
                $productName = $p['name'];
                break;
            }
        }
        
        echo '<tr>';
        echo '<td>' . $change['product_id'] . '</td>';
        echo '<td>' . htmlspecialchars($productName) . '</td>';
        echo '<td><code>' . htmlspecialchars($change['setting']) . '</code></td>';
        echo '<td><span class="label label-default">' . htmlspecialchars($change['old_value']) . '</span></td>';
        echo '<td><span class="label label-success">' . htmlspecialchars($change['new_value']) . '</span></td>';
        echo '<td><small style="color: #666;">' . htmlspecialchars($change['old_encoded']) . ' → ' . htmlspecialchars($change['new_encoded']) . '</small></td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div></div>';
    
    // Confirmation form
    echo '<form method="post" action="' . $vars['modulelink'] . '&action=apply">';
    echo '<input type="hidden" name="group_id" value="' . $groupId . '" />';
    // Use base64 encoding to prevent HTML entity conversion of quotes and backslashes
    // Base64 strings are safe for HTML attributes and don't require quote escaping
    echo '<input type="hidden" name="preview_data" value="' . htmlspecialchars(base64_encode(json_encode($preview)), ENT_QUOTES, 'UTF-8') . '" />';
    echo '<input type="hidden" name="changes_data" value="' . htmlspecialchars(base64_encode(json_encode($changes)), ENT_QUOTES, 'UTF-8') . '" />';
    
    echo '<div class="form-group">';
    echo '<button type="submit" class="btn btn-success btn-lg">';
    echo '<i class="fas fa-check"></i> Confirm & Apply Changes';
    echo '</button> ';
    echo '<a href="' . $vars['modulelink'] . '&action=edit&group_id=' . $groupId . '" class="btn btn-default btn-lg">Cancel</a>';
    echo '</div>';
    
    echo '</form>';
    echo '</div>';
}

/**
 * Apply changes to database
 */
function applyChanges($vars)
{
    $groupId = (int)($_POST['group_id'] ?? 0);

    // Decode base64-encoded JSON data from hidden inputs
    // This prevents quotes and backslashes from being converted to HTML entities
    $previewRaw = $_POST['preview_data'] ?? '';
    $changesRaw = $_POST['changes_data'] ?? '';

    // Decode HTML entities first (for safety), then decode base64
    $previewB64 = html_entity_decode($previewRaw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $changesB64 = html_entity_decode($changesRaw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Decode from base64 to get the original JSON string (with actual quotes and backslashes)
    $previewJson = base64_decode($previewB64, true);
    $changesJson = base64_decode($changesB64, true);
    
    // If base64 decode failed, try direct decode (backward compatibility with old format)
    if ($previewJson === false) {
        $previewJson = $previewRaw;
    }
    if ($changesJson === false) {
        $changesJson = $changesRaw;
    }

    $previewData = json_decode($previewJson, true) ?: [];
    $changesData = json_decode($changesJson, true) ?: [];
    
    $groupManager = new GroupManager();
    $configManager = new ProductConfigManager();
    $logger = new ChangeLogger();
    
    $group = $groupManager->getGroupById($groupId);
    if (!$group) {
        echo '<div class="alert alert-danger">Group not found.</div>';
        renderHomePage($vars);
        return;
    }
    
    $productIds = array_map('trim', explode(',', (string)$group['product_ids']));
    
    echo '<div class="proxmox-bulk-container">';
    echo '<h2>Applying Changes: ' . htmlspecialchars($group['name']) . '</h2>';
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    try {
        foreach ($productIds as $productId) {
            foreach ($changesData as $settingName => $encodedNewValue) {
                try {
                    $oldValue = $configManager->getSettingValue((int)$productId, $settingName);
                    
                    // Pass encoded value directly (already in correct database format)
                    $configManager->updateSetting((int)$productId, $settingName, $encodedNewValue, true);
                    
                    // Log the change (store encoded new value)
                    $logger->logChange(
                        $groupId,
                        (int)$productId,
                        $settingName,
                        $oldValue,
                        $encodedNewValue
                    );
                    
                    $successCount++;
                } catch (Exception $e) {
                    $errorCount++;
                    $errors[] = "Product {$productId}, Setting {$settingName}: " . $e->getMessage();
                }
            }
        }
        
        echo '<div class="alert alert-success">';
        echo '<i class="fas fa-check-circle"></i> ';
        echo '<strong>Changes Applied Successfully!</strong><br/>';
        echo 'Updated ' . $successCount . ' setting(s) across ' . count($productIds) . ' product(s).';
        echo '</div>';
        
        if ($errorCount > 0) {
            echo '<div class="alert alert-warning">';
            echo '<strong>Some errors occurred (' . $errorCount . '):</strong><ul>';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul></div>';
        }
        
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">';
        echo '<i class="fas fa-exclamation-circle"></i> ';
        echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
    
    echo '<div class="btn-group">';
    echo '<a href="' . $vars['modulelink'] . '" class="btn btn-primary">Back to Home</a> ';
    echo '<a href="' . $vars['modulelink'] . '&action=edit&group_id=' . $groupId . '" class="btn btn-default">Edit Again</a> ';
    echo '<a href="' . $vars['modulelink'] . '&action=history" class="btn btn-info">View Change History</a>';
    echo '</div>';
    
    echo '</div>';
}

/**
 * Export current settings to CSV
 */
function exportToCsv($vars)
{
    $groupId = (int)$_GET['group_id'];
    $groupManager = new GroupManager();
    $exporter = new CsvExporter();
    
    $group = $groupManager->getGroupById($groupId);
    if (!$group) {
        die('Group not found');
    }
    
    $productIds = array_map('trim', explode(',', $group['product_ids']));
    
    $exporter->exportGroupSettings($group, $productIds);
}

/**
 * Render change history page
 */
function renderHistoryPage($vars)
{
    $logger = new ChangeLogger();
    $page = (int)($_GET['page'] ?? 1);
    $limit = 50;
    $offset = ($page - 1) * $limit;
    
    $history = $logger->getChangeHistory($limit, $offset);
    $totalCount = $logger->getTotalChangeCount();
    $totalPages = ceil($totalCount / $limit);
    
    echo '<div class="proxmox-bulk-container">';
    echo '<h2>Change History</h2>';
    
    // Navigation
    echo '<div class="btn-group" style="margin-bottom: 20px;">';
    echo '<a href="' . $vars['modulelink'] . '" class="btn btn-default">Bulk Editor</a>';
    echo '<a href="' . $vars['modulelink'] . '&action=groups" class="btn btn-default">Manage Groups</a>';
    echo '<a href="' . $vars['modulelink'] . '&action=history" class="btn btn-primary">Change History</a>';
    echo '</div>';
    
    if (empty($history)) {
        echo '<div class="alert alert-info">No changes recorded yet.</div>';
        echo '</div>';
        return;
    }
    
    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading">';
    echo '<h3 class="panel-title">Recent Changes (Total: ' . $totalCount . ')</h3>';
    echo '</div>';
    
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped table-condensed">';
    echo '<thead><tr>';
    echo '<th>Date/Time</th>';
    echo '<th>Admin</th>';
    echo '<th>Group</th>';
    echo '<th>Product ID</th>';
    echo '<th>Setting</th>';
    echo '<th>Old Value</th>';
    echo '<th>New Value</th>';
    echo '</tr></thead><tbody>';
    
    foreach ($history as $entry) {
        echo '<tr>';
        echo '<td>' . date('Y-m-d H:i:s', strtotime($entry['created_at'])) . '</td>';
        echo '<td>' . htmlspecialchars($entry['admin_username'] ?? 'Unknown') . '</td>';
        echo '<td>' . htmlspecialchars($entry['group_name'] ?? 'N/A') . '</td>';
        echo '<td>' . $entry['product_id'] . '</td>';
        echo '<td><code>' . htmlspecialchars($entry['setting_name']) . '</code></td>';
        echo '<td><small>' . htmlspecialchars(substr($entry['old_value'], 0, 50)) . '</small></td>';
        echo '<td><small>' . htmlspecialchars(substr($entry['new_value'], 0, 50)) . '</small></td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>';
    
    // Pagination
    if ($totalPages > 1) {
        echo '<div class="panel-footer">';
        echo '<ul class="pagination" style="margin: 0;">';
        
        if ($page > 1) {
            echo '<li><a href="' . $vars['modulelink'] . '&action=history&page=' . ($page - 1) . '">Previous</a></li>';
        }
        
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $page) ? ' class="active"' : '';
            echo '<li' . $active . '><a href="' . $vars['modulelink'] . '&action=history&page=' . $i . '">' . $i . '</a></li>';
        }
        
        if ($page < $totalPages) {
            echo '<li><a href="' . $vars['modulelink'] . '&action=history&page=' . ($page + 1) . '">Next</a></li>';
        }
        
        echo '</ul>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
}

/**
 * Get field types for settings (based on Proxmox module patterns)
 */
function getFieldTypes(): array
{
    return [
        // Boolean/Toggle fields (on/off)
        'acpi' => 'toggle',
        'additionalDisk' => 'toggle',
        'additionalDiskDiscard' => 'toggle',
        'additionalDiskIoThread' => 'toggle',
        'additionalDiskReplicate' => 'toggle',
        'additionalDiskSpeed' => 'toggle',
        'additionalDiskSsd' => 'toggle',
        'aes' => 'toggle',
        'agent' => 'toggle',
        'agentConfigureNetwork' => 'toggle',
        'agentFreezeFsOnBackup' => 'toggle',
        'agentGuestTrim' => 'toggle',
        'agentServiceHostname' => 'toggle',
        'agentServicePassword' => 'toggle',
        'agentTemplateUser' => 'toggle',
        'autoAssignPrivateIp' => 'toggle',
        'autoCreatePrivateNetwork' => 'toggle',
        'autostart' => 'toggle',
        'cloudInit' => 'toggle',
        'cloudInitServiceNameservers' => 'toggle',
        'cloudInitServicePassword' => 'toggle',
        'cloudInitServiceUsername' => 'toggle',
        'cloneOnTheSameStorage' => 'toggle',
        'deleteBackups' => 'toggle',
        'deleteBackupsOnPackageChange' => 'toggle',
        'destroyUnreferencedDisks' => 'toggle',
        'discard' => 'toggle',
        'diskSpeed' => 'toggle',
        'firewalOptionDhcp' => 'toggle',
        'firewalOptionEnable' => 'toggle',
        'firewalOptionIpfilter' => 'toggle',
        'firewalOptionMacfilter' => 'toggle',
        'firewalOptionNdp' => 'toggle',
        'firewalOptionRadv' => 'toggle',
        'freeze' => 'toggle',
        'generateRandomPassword' => 'toggle',
        'ioThread' => 'toggle',
        'ipsetIpFilter' => 'toggle',
        'kvm' => 'toggle',
        'loadBalancer' => 'toggle',
        'loadBalancerMigrationWithLocalDisks' => 'toggle',
        'loadBalancerShutdownOnUpgrade' => 'toggle',
        'loadBalancerStopOnUpgrade' => 'toggle',
        'managedView' => 'toggle',
        'networkFirewall' => 'toggle',
        'numa' => 'toggle',
        'onboot' => 'toggle',
        'oneNetworkDevice' => 'toggle',
        'oneUserPerVps' => 'toggle',
        'osTemplatesInAllNodes' => 'toggle',
        'permissionAdditionalDiskBackup' => 'toggle',
        'permissionAllVmsBackups' => 'toggle',
        'permissionBackup' => 'toggle',
        'permissionBackupJob' => 'toggle',
        'permissionBackupSchedule' => 'toggle',
        'permissionChangeHostname' => 'toggle',
        'permissionDisk' => 'toggle',
        'permissionDownloadBackupFile' => 'toggle',
        'permissionFirewall' => 'toggle',
        'permissionFirewallOption' => 'toggle',
        'permissionGraph' => 'toggle',
        'permissionIsoImage' => 'toggle',
        'permissionNetwork' => 'toggle',
        'permissionNetworkReconfigure' => 'toggle',
        'permissionNovnc' => 'toggle',
        'permissionOsTemplate' => 'toggle',
        'permissionReboot' => 'toggle',
        'permissionReinstall' => 'toggle',
        'permissionResourcesNotification' => 'toggle',
        'permissionRestoreBackupFile' => 'toggle',
        'permissionServerMonitoring' => 'toggle',
        'permissionShutdown' => 'toggle',
        'permissionSnapshot' => 'toggle',
        'permissionSnapshotJob' => 'toggle',
        'permissionSpice' => 'toggle',
        'permissionSshkeys' => 'toggle',
        'permissionStart' => 'toggle',
        'permissionStop' => 'toggle',
        'permissionTaskHistory' => 'toggle',
        'permissionVmPowerTasks' => 'toggle',
        'permissionXtermjs' => 'toggle',
        'privateNetwork' => 'toggle',
        'privateNetworkDhcp' => 'toggle',
        'privateNetworkFirewall' => 'toggle',
        'reassignPrivateNetwork' => 'toggle',
        'reassignPublicNetwork' => 'toggle',
        'reboot' => 'toggle',
        'rebootVmAfterChangePackage' => 'toggle',
        'replicate' => 'toggle',
        'serverNameservers' => 'toggle',
        'snapshotRouting' => 'toggle',
        'ssd' => 'toggle',
        'start' => 'toggle',
        'suspendOnBandwidthOverage' => 'toggle',
        'tablet' => 'toggle',
        'toDoList' => 'toggle',
        'tpm' => 'toggle',
        'useServiceIdAsVmId' => 'toggle',
        'backupRouting' => 'toggle',
        'backupVmBeforeReinstall' => 'toggle',
        'calculateSocketsAndCores' => 'toggle',
        'orderPublicIp' => 'toggle',
        
        // Array fields (JSON arrays)
        'additionalDiskFormat' => 'array',
        'additionalDiskType' => 'array',
        'alternativeMode' => 'array',
        'bridges' => 'array',
        'clientAreaSectionsOrder' => 'array',
        'firewallGroups' => 'array',
        'firewallInterfaces' => 'array',
        'hotplug' => 'array',
        'permissionBackupCompress' => 'array',
        'permissionFirewalOptions' => 'array',
        'permissionInformation' => 'array',
        'permissionSnapshotJobPeriod' => 'array',
        'privateBridges' => 'array',
        'serverGroup' => 'array',
        'tags' => 'array',
        'archive' => 'array',
        'availableServers' => 'array',
        'cloudInitScript' => 'array',
        'locations' => 'array',
        'permissionIsoImages' => 'array',
        'permissionOsTemplates' => 'array',
        'permissionOstype' => 'array',
        'permissionSecondaryIsoImages' => 'array',
        
        // Textarea fields (long text)
        'description' => 'textarea',
        'args' => 'textarea',
        'randomPasswordAvailableCharacters' => 'textarea',
        'userComment' => 'textarea',
        
        // Dropdown/Select fields (specific options)
        'buttonSyle' => 'dropdown',
        'detailsView' => 'dropdown',
        'storageUnit' => 'dropdown',
        'memoryUnit' => 'dropdown',
        'productType' => 'dropdown',
        
        // All others default to 'text'
    ];
}

/**
 * Render field input based on type
 */
function renderFieldInput(string $name, string $value, string $type): void
{
    switch ($type) {
        case 'toggle':
            // On/Off dropdown
            $selected_on = ($value === 'on') ? 'selected' : '';
            $selected_off = ($value === 'off') ? 'selected' : '';
            echo '<select name="value_' . $name . '" class="form-control input-sm">';
            echo '<option value="on" ' . $selected_on . '>on</option>';
            echo '<option value="off" ' . $selected_off . '>off</option>';
            echo '</select>';
            break;
            
        case 'dropdown':
            // Dropdown with predefined options based on setting name
            renderDropdownField($name, $value);
            break;
            
        case 'array':
            // JSON array input with helper text
            echo '<input type="text" name="value_' . $name . '" class="form-control input-sm" value="' . $value . '" />';
            echo '<small class="help-block">JSON array format: ["value1","value2"] or []</small>';
            break;
            
        case 'textarea':
            // Textarea for long text
            echo '<textarea name="value_' . $name . '" class="form-control input-sm" rows="3">' . $value . '</textarea>';
            break;
            
        case 'text':
        default:
            // Regular text input
            echo '<input type="text" name="value_' . $name . '" class="form-control input-sm" value="' . $value . '" />';
            break;
    }
}

/**
 * Render dropdown fields with specific options
 */
function renderDropdownField(string $name, string $value): void
{
    $options = [];
    
    // Define options for specific dropdowns
    switch ($name) {
        case 'buttonSyle':
            $options = ['tiles' => 'Tiles', 'list' => 'List'];
            break;
        case 'detailsView':
            $options = ['standard' => 'Standard', 'advanced' => 'Advanced'];
            break;
        case 'storageUnit':
        case 'memoryUnit':
            $options = ['mb' => 'MB', 'gb' => 'GB', 'tb' => 'TB'];
            break;
        case 'productType':
            $options = ['vps' => 'VPS', 'cloud' => 'Cloud'];
            break;
        default:
            // Fallback to text input if no options defined
            echo '<input type="text" name="value_' . $name . '" class="form-control input-sm" value="' . htmlspecialchars($value) . '" />';
            return;
    }
    
    // Render dropdown
    echo '<select name="value_' . $name . '" class="form-control input-sm">';
    foreach ($options as $optValue => $optLabel) {
        $selected = ($value === $optValue) ? 'selected' : '';
        echo '<option value="' . htmlspecialchars($optValue) . '" ' . $selected . '>' . htmlspecialchars($optLabel) . '</option>';
    }
    echo '</select>';
}

/**
 * Get current module type from configuration
 */
if (!function_exists('getModuleType')) {
    function getModuleType($vars): string
    {
        return $vars['proxmox_module'] ?? 'cloud';
    }
}

/**
 * Output CSS
 */
function outputCss()
{
    header('Content-Type: text/css');
    echo '
    .proxmox-bulk-container {
        padding: 20px;
    }
    
    .proxmox-bulk-container h2 {
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #ddd;
    }
    
    .setting-row:hover {
        background-color: #f5f5f5;
    }
    
    code {
        font-size: 13px;
    }
    
    .table-condensed > tbody > tr > td {
        padding: 5px;
        font-size: 12px;
    }
    
    .help-block {
        margin: 2px 0 0 0;
        font-size: 11px;
        color: #999;
    }
    ';
    exit;
}

