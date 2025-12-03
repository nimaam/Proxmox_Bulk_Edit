<?php

namespace ProxmoxBulkVmSetting;

/**
 * CSV export functionality
 */
class CsvExporter
{
    /**
     * Export group settings to CSV
     */
    public function exportGroupSettings(array $group, array $productIds): void
    {
        $configManager = new ProductConfigManager();
        
        // Get all settings for all products in group
        $allSettings = $configManager->getMultipleProductSettings($productIds);
        
        // Get product names
        $products = $configManager->getProductNames($productIds);
        $productNames = [];
        foreach ($products as $product) {
            $productNames[$product['id']] = $product['name'];
        }
        
        // Generate filename
        $filename = 'proxmox_bulk_export_' . $this->sanitizeFilename($group['name']) . '_' . date('Y-m-d_His') . '.csv';
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Write UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        
        // Write header
        fputcsv($output, ['Product ID', 'Product Name', 'Type', 'Setting', 'Value']);
        
        // Write data
        foreach ($allSettings as $setting) {
            $productId = is_object($setting) ? $setting->product_id : $setting['product_id'];
            $type = is_object($setting) ? $setting->type : $setting['type'];
            $settingName = is_object($setting) ? $setting->setting : $setting['setting'];
            $rawValue = is_object($setting) ? $setting->value : $setting['value'];
            
            // Decode value for readable CSV
            $decodedValue = isset($setting['decoded_value']) ? 
                $setting['decoded_value'] : 
                json_decode($rawValue, true);
            
            if (is_array($decodedValue)) {
                $decodedValue = json_encode($decodedValue, JSON_UNESCAPED_SLASHES);
            } elseif (!is_string($decodedValue)) {
                $decodedValue = (string)$decodedValue;
            }
            
            fputcsv($output, [
                $productId,
                $productNames[$productId] ?? 'Unknown',
                $type,
                $settingName,
                $decodedValue
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Sanitize filename
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove any character that isn't alphanumeric, dash, or underscore
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        return trim($filename, '_');
    }
}

