<?php

namespace ProxmoxBulkVmSetting;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Database management class
 */
class Database
{
    /**
     * Create required database tables
     */
    public static function createTables(): void
    {
        // Table for storing product groups
        if (!Capsule::schema()->hasTable('mod_proxmox_bulk_groups')) {
            Capsule::schema()->create('mod_proxmox_bulk_groups', function ($table) {
                $table->increments('id');
                $table->string('name', 255);
                $table->text('product_ids');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
                
                $table->index('name');
            });
        }
        
        // Table for storing change history
        if (!Capsule::schema()->hasTable('mod_proxmox_bulk_change_log')) {
            Capsule::schema()->create('mod_proxmox_bulk_change_log', function ($table) {
                $table->increments('id');
                $table->integer('admin_id')->unsigned();
                $table->integer('group_id')->unsigned()->nullable();
                $table->integer('product_id')->unsigned();
                $table->string('setting_name', 255);
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->timestamp('created_at')->useCurrent();
                
                $table->index('admin_id');
                $table->index('group_id');
                $table->index('product_id');
                $table->index('created_at');
            });
        }
    }
    
    /**
     * Drop tables (for uninstall - not currently exposed)
     */
    public static function dropTables(): void
    {
        Capsule::schema()->dropIfExists('mod_proxmox_bulk_change_log');
        Capsule::schema()->dropIfExists('mod_proxmox_bulk_groups');
    }
    
    /**
     * Get database connection
     */
    public static function getConnection()
    {
        return Capsule::connection();
    }
}

