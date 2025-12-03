<?php

namespace ProxmoxBulkVmSetting;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Manages Proxmox product configuration
 */
class ProductConfigManager
{
    private string $tableName;
    
    /**
     * Constructor - sets table name based on module configuration
     */
    public function __construct()
    {
        $this->tableName = $this->getTableName();
    }
    
    /**
     * Get the correct table name based on module configuration
     */
    private function getTableName(): string
    {
        // Get module configuration
        $moduleType = $this->getModuleType();
        
        if ($moduleType === 'addon') {
            return 'ProxmoxAddon_ProductConfiguration';
        }
        
        // Default to Cloud version
        return 'ProxmoxVeVpsCloud_ProductConfiguration';
    }
    
    /**
     * Get module type from WHMCS configuration
     */
    private function getModuleType(): string
    {
        try {
            $config = Capsule::table('tbladdonmodules')
                ->where('module', 'proxmox_bulk_vm_setting')
                ->where('setting', 'proxmox_module')
                ->value('value');
            
            return $config ?: 'cloud';
        } catch (\Exception $e) {
            return 'cloud'; // Default fallback
        }
    }
    
    /**
     * Check if the current table has a 'type' column
     * ProxmoxVeVpsCloud has it, ProxmoxAddon doesn't
     */
    private function hasTypeColumn(): bool
    {
        return $this->tableName === 'ProxmoxVeVpsCloud_ProductConfiguration';
    }
    
    /**
     * Get all settings for a product
     */
    public function getProductSettings(int $productId): array
    {
        $query = Capsule::table($this->tableName)
            ->where('product_id', $productId);
        
        // ProxmoxVeVpsCloud has 'type' column, ProxmoxAddon doesn't
        if ($this->hasTypeColumn()) {
            $query->where('type', 'product');
        }
        
        $results = $query->orderBy('setting', 'asc')
            ->get()
            ->toArray();
        
        // Decode JSON values like Proxmox module does
        $decoded = [];
        foreach ($results as $row) {
            $decoded[] = [
                'product_id' => is_object($row) ? $row->product_id : $row['product_id'],
                'type' => is_object($row) ? $row->type : $row['type'],
                'setting' => is_object($row) ? $row->setting : $row['setting'],
                'value' => is_object($row) ? $row->value : $row['value'],
                'decoded_value' => $this->decodeValue(is_object($row) ? $row->value : $row['value'])
            ];
        }
        
        return $decoded;
    }
    
    /**
     * Get a specific setting value for a product (returns raw value from DB)
     */
    public function getSettingValue(int $productId, string $settingName): string
    {
        $query = Capsule::table($this->tableName)
            ->where('product_id', $productId)
            ->where('setting', $settingName);
        
        // ProxmoxVeVpsCloud has 'type' column, ProxmoxAddon doesn't
        if ($this->hasTypeColumn()) {
            $query->where('type', 'product');
        }
        
        $result = $query->value('value');
        
        return $result ?? '';
    }
    
    /**
     * Update a setting for a product
     * Value should be provided as the display value, will be JSON encoded
     */
    public function updateSetting(int $productId, string $settingName, string $newValue, bool $alreadyEncoded = false): bool
    {
        // Encode value as JSON like Proxmox module does (unless already encoded)
        $encodedValue = $alreadyEncoded ? $newValue : $this->encodeValue($newValue);
        
        // Build existence check query
        $existsQuery = Capsule::table($this->tableName)
            ->where('product_id', $productId)
            ->where('setting', $settingName);
        
        // ProxmoxVeVpsCloud has 'type' column, ProxmoxAddon doesn't
        if ($this->hasTypeColumn()) {
            $existsQuery->where('type', 'product');
        }
        
        $exists = $existsQuery->exists();
        
        if ($exists) {
            // Update existing setting
            $updateQuery = Capsule::table($this->tableName)
                ->where('product_id', $productId)
                ->where('setting', $settingName);
            
            if ($this->hasTypeColumn()) {
                $updateQuery->where('type', 'product');
            }
            
            return $updateQuery->update(['value' => $encodedValue]) > 0;
        }
        
        // If setting doesn't exist, create it
        $insertData = [
            'product_id' => $productId,
            'setting' => $settingName,
            'value' => $encodedValue
        ];
        
        // Add 'type' column only for ProxmoxVeVpsCloud
        if ($this->hasTypeColumn()) {
            $insertData['type'] = 'product';
        }
        
        return Capsule::table($this->tableName)->insert($insertData);
    }
    
    /**
     * Decode a value from database (JSON decode)
     */
    private function decodeValue(string $value): string
    {
        $decoded = json_decode($value, true);
        
        // If it's an array, return JSON representation
        if (is_array($decoded)) {
            return json_encode($decoded, JSON_UNESCAPED_SLASHES);
        }
        
        // If it's a string, return it
        if (is_string($decoded)) {
            return $decoded;
        }
        
        // If it's null or something else, return original
        return $value;
    }
    
    /**
     * Encode a value for database storage (JSON encode)
     */
    private function encodeValue(string $value): string
    {
        // Check if it's already a JSON array/object
        $testDecode = json_decode($value, true);
        if (is_array($testDecode)) {
            // It's already JSON, encode it as a JSON string
            return json_encode($value);
        }
        
        // Otherwise, encode the string value
        return json_encode($value);
    }
    
    /**
     * Get product names from WHMCS tblproducts
     */
    public function getProductNames(array $productIds): array
    {
        $products = [];
        
        if (empty($productIds)) {
            return $products;
        }
        
        $results = Capsule::table('tblproducts')
            ->whereIn('id', $productIds)
            ->select('id', 'name')
            ->get();
        
        foreach ($results as $result) {
            $products[] = [
                'id' => is_object($result) ? $result->id : $result['id'],
                'name' => is_object($result) ? $result->name : $result['name']
            ];
        }
        
        return $products;
    }
    
    /**
     * Check if a product exists
     */
    public function productExists(int $productId): bool
    {
        return Capsule::table('tblproducts')
            ->where('id', $productId)
            ->exists();
    }
    
    /**
     * Get all settings for multiple products (for export)
     */
    public function getMultipleProductSettings(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }
        
        $query = Capsule::table($this->tableName)
            ->whereIn('product_id', $productIds);
        
        // ProxmoxVeVpsCloud has 'type' column, ProxmoxAddon doesn't
        if ($this->hasTypeColumn()) {
            $query->where('type', 'product');
        }
        
        $results = $query->orderBy('product_id', 'asc')
            ->orderBy('setting', 'asc')
            ->get();
        
        // Convert objects to arrays
        $settings = [];
        foreach ($results as $result) {
            $settings[] = (array)$result;
        }
        
        return $settings;
    }
}

