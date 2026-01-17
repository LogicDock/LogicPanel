<?php
/**
 * LogicPanel - Reseller Package Model
 * Defines resource limits for resellers
 */

namespace LogicPanel\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerPackage extends Model
{
    protected $table = 'lp_reseller_packages';

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'max_users',
        'max_services_per_user',
        'max_total_disk_gb',
        'max_total_ram_gb',
        'can_create_packages',
        'can_oversell',
        'price_monthly',
        'price_yearly',
        'is_active'
    ];

    protected $casts = [
        'max_users' => 'integer',
        'max_services_per_user' => 'integer',
        'max_total_disk_gb' => 'integer',
        'max_total_ram_gb' => 'integer',
        'can_create_packages' => 'boolean',
        'can_oversell' => 'boolean',
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all resellers using this package
     */
    public function resellers()
    {
        return $this->hasMany(User::class, 'reseller_package_id');
    }

    /**
     * Get active packages only
     */
    public static function active()
    {
        return static::where('is_active', true)->get();
    }

    /**
     * Get total disk used by all resellers with this package (in GB)
     */
    public function getTotalDiskUsed(): float
    {
        $totalBytes = 0;
        foreach ($this->resellers as $reseller) {
            foreach ($reseller->children as $user) {
                foreach ($user->services as $service) {
                    $totalBytes += $service->disk_used ?? 0;
                }
            }
        }
        return round($totalBytes / (1024 * 1024 * 1024), 2);
    }

    /**
     * Check if package has available disk space
     */
    public function hasAvailableDisk(): bool
    {
        return $this->can_oversell || $this->getTotalDiskUsed() < $this->max_total_disk_gb;
    }
}
