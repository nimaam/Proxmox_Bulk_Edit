<?php

namespace ProxmoxBulkVmSetting;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Logs all configuration changes
 */
class ChangeLogger
{
    /**
     * Log a configuration change
     */
    public function logChange(
        int $groupId,
        int $productId,
        string $settingName,
        string $oldValue,
        string $newValue
    ): int {
        $adminId = $this->getCurrentAdminId();
        
        return Capsule::table('mod_proxmox_bulk_change_log')->insertGetId([
            'admin_id' => $adminId,
            'group_id' => $groupId,
            'product_id' => $productId,
            'setting_name' => $settingName,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get change history with pagination
     */
    public function getChangeHistory(int $limit = 50, int $offset = 0): array
    {
        $results = Capsule::table('mod_proxmox_bulk_change_log as log')
            ->leftJoin('tbladmins as admin', 'log.admin_id', '=', 'admin.id')
            ->leftJoin('mod_proxmox_bulk_groups as grp', 'log.group_id', '=', 'grp.id')
            ->select(
                'log.*',
                'admin.username as admin_username',
                'grp.name as group_name'
            )
            ->orderBy('log.created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();
        
        // Convert objects to arrays
        $history = [];
        foreach ($results as $result) {
            $history[] = (array)$result;
        }
        
        return $history;
    }
    
    /**
     * Get total count of change log entries
     */
    public function getTotalChangeCount(): int
    {
        return Capsule::table('mod_proxmox_bulk_change_log')->count();
    }
    
    /**
     * Get changes for a specific group
     */
    public function getGroupChanges(int $groupId, int $limit = 50): array
    {
        $results = Capsule::table('mod_proxmox_bulk_change_log as log')
            ->leftJoin('tbladmins as admin', 'log.admin_id', '=', 'admin.id')
            ->where('log.group_id', $groupId)
            ->select(
                'log.*',
                'admin.username as admin_username'
            )
            ->orderBy('log.created_at', 'desc')
            ->limit($limit)
            ->get();
        
        // Convert objects to arrays
        $changes = [];
        foreach ($results as $result) {
            $changes[] = (array)$result;
        }
        
        return $changes;
    }
    
    /**
     * Get changes for a specific product
     */
    public function getProductChanges(int $productId, int $limit = 50): array
    {
        $results = Capsule::table('mod_proxmox_bulk_change_log as log')
            ->leftJoin('tbladmins as admin', 'log.admin_id', '=', 'admin.id')
            ->leftJoin('mod_proxmox_bulk_groups as grp', 'log.group_id', '=', 'grp.id')
            ->where('log.product_id', $productId)
            ->select(
                'log.*',
                'admin.username as admin_username',
                'grp.name as group_name'
            )
            ->orderBy('log.created_at', 'desc')
            ->limit($limit)
            ->get();
        
        // Convert objects to arrays
        $changes = [];
        foreach ($results as $result) {
            $changes[] = (array)$result;
        }
        
        return $changes;
    }
    
    /**
     * Get current admin ID from session
     */
    private function getCurrentAdminId(): int
    {
        // WHMCS stores admin ID in session
        if (isset($_SESSION['adminid'])) {
            return (int)$_SESSION['adminid'];
        }
        
        // Fallback to checking the global admin array
        global $whmcs;
        if (isset($whmcs->get_admin()['id'])) {
            return (int)$whmcs->get_admin()['id'];
        }
        
        // Last resort - try to get from tbladmins with current session
        $admin = Capsule::table('tbladmins')
            ->where('disabled', 0)
            ->first();
        
        return $admin ? (int)$admin->id : 0;
    }
}

