<?php

namespace ProxmoxBulkVmSetting;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Manages product groups
 */
class GroupManager
{
    /**
     * Get all groups
     */
    public function getAllGroups(): array
    {
        $results = Capsule::table('mod_proxmox_bulk_groups')
            ->orderBy('name', 'asc')
            ->get();
        
        // Convert objects to arrays
        $groups = [];
        foreach ($results as $result) {
            $groups[] = (array)$result;
        }
        
        return $groups;
    }
    
    /**
     * Get group by ID
     */
    public function getGroupById(int $groupId): ?array
    {
        $result = Capsule::table('mod_proxmox_bulk_groups')
            ->where('id', $groupId)
            ->first();
        
        // Convert object to array if exists
        return $result ? (array)$result : null;
    }
    
    /**
     * Create a new group
     */
    public function createGroup(string $name, string $productIds): int
    {
        // Validate and normalize product IDs
        $productIds = $this->normalizeProductIds($productIds);
        
        return Capsule::table('mod_proxmox_bulk_groups')->insertGetId([
            'name' => $name,
            'product_ids' => $productIds,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Update an existing group
     */
    public function updateGroup(int $groupId, string $name, string $productIds): bool
    {
        // Validate and normalize product IDs
        $productIds = $this->normalizeProductIds($productIds);
        
        return Capsule::table('mod_proxmox_bulk_groups')
            ->where('id', $groupId)
            ->update([
                'name' => $name,
                'product_ids' => $productIds,
                'updated_at' => date('Y-m-d H:i:s')
            ]) > 0;
    }
    
    /**
     * Delete a group
     */
    public function deleteGroup(int $groupId): bool
    {
        return Capsule::table('mod_proxmox_bulk_groups')
            ->where('id', $groupId)
            ->delete() > 0;
    }
    
    /**
     * Normalize product IDs string (remove spaces, ensure valid format)
     */
    private function normalizeProductIds(string $productIds): string
    {
        $ids = array_map('trim', explode(',', $productIds));
        $ids = array_filter($ids, function($id) {
            return is_numeric($id) && (int)$id > 0;
        });
        
        if (empty($ids)) {
            throw new \Exception('No valid product IDs provided');
        }
        
        return implode(',', $ids);
    }
}

