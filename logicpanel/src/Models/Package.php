<?php
/**
 * LogicPanel - Package Model
 * Resource packages like WHM packages
 */

namespace LogicPanel\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $table = 'packages';

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'memory_limit',
        'cpu_limit',
        'disk_limit',
        'bandwidth_limit',
        'io_limit',
        'max_domains',
        'max_databases',
        'max_backups',
        'max_deployments_per_day',
        'allow_ssh',
        'allow_git_deploy',
        'allow_custom_node_version',
        'allowed_node_versions',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'memory_limit' => 'integer',
        'cpu_limit' => 'float',
        'disk_limit' => 'integer',
        'bandwidth_limit' => 'integer',
        'io_limit' => 'integer',
        'max_domains' => 'integer',
        'max_databases' => 'integer',
        'max_backups' => 'integer',
        'max_deployments_per_day' => 'integer',
        'allow_ssh' => 'boolean',
        'allow_git_deploy' => 'boolean',
        'allow_custom_node_version' => 'boolean',
        'allowed_node_versions' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    /**
     * Get services using this package
     */
    public function services()
    {
        return $this->hasMany(Service::class, 'package_id');
    }

    /**
     * Get human-readable memory limit
     */
    public function getMemoryDisplayAttribute(): string
    {
        if ($this->memory_limit >= 1024) {
            return round($this->memory_limit / 1024, 1) . ' GB';
        }
        return $this->memory_limit . ' MB';
    }

    /**
     * Get human-readable disk limit
     */
    public function getDiskDisplayAttribute(): string
    {
        if ($this->disk_limit >= 1024) {
            return round($this->disk_limit / 1024, 1) . ' GB';
        }
        return $this->disk_limit . ' MB';
    }

    /**
     * Get human-readable bandwidth limit
     */
    public function getBandwidthDisplayAttribute(): string
    {
        if ($this->bandwidth_limit == 0) {
            return 'Unlimited';
        }
        if ($this->bandwidth_limit >= 1024) {
            return round($this->bandwidth_limit / 1024, 1) . ' GB';
        }
        return $this->bandwidth_limit . ' MB';
    }

    /**
     * Get human-readable CPU limit
     */
    public function getCpuDisplayAttribute(): string
    {
        if ($this->cpu_limit >= 1) {
            return $this->cpu_limit . ' Core' . ($this->cpu_limit > 1 ? 's' : '');
        }
        return ($this->cpu_limit * 100) . '% Core';
    }

    /**
     * Get Docker resource config for container
     */
    public function getDockerResourceConfig(): array
    {
        return [
            'Memory' => $this->memory_limit * 1024 * 1024, // Convert MB to bytes
            'MemorySwap' => $this->memory_limit * 2 * 1024 * 1024, // 2x memory for swap
            'NanoCpus' => (int) ($this->cpu_limit * 1000000000), // CPU in nanoseconds
            'BlkioWeight' => $this->io_limit > 0 ? min($this->io_limit * 10, 1000) : 500,
        ];
    }

    /**
     * Get active packages ordered by sort_order
     */
    public static function getActivePackages()
    {
        return self::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Format package for WHMCS API response
     */
    public function toWhmcsFormat(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'resources' => [
                'memory' => $this->memory_limit,
                'memory_display' => $this->memory_display,
                'cpu' => $this->cpu_limit,
                'cpu_display' => $this->cpu_display,
                'disk' => $this->disk_limit,
                'disk_display' => $this->disk_display,
                'bandwidth' => $this->bandwidth_limit,
                'bandwidth_display' => $this->bandwidth_display,
            ],
            'limits' => [
                'domains' => $this->max_domains,
                'databases' => $this->max_databases,
                'backups' => $this->max_backups,
                'deployments_per_day' => $this->max_deployments_per_day,
            ],
            'features' => [
                'ssh' => $this->allow_ssh,
                'git_deploy' => $this->allow_git_deploy,
                'custom_node_version' => $this->allow_custom_node_version,
            ]
        ];
    }
}
