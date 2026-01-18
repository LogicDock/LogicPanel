<?php
/**
 * LogicPanel - Service Model
 * Represents a user's application/container (multi-language support)
 */

namespace LogicPanel\Models;

use Illuminate\Database\Eloquent\Model;
use LogicPanel\Services\LanguageService;

class Service extends Model
{
    protected $table = 'lp_services';

    protected $fillable = [
        'user_id',
        'package_id',
        'name',
        'container_id',
        'container_name',
        'status',
        'language',
        'language_version',
        'node_version', // deprecated, use language_version
        'port',
        'github_repo',
        'github_branch',
        'github_pat',
        'install_cmd',
        'build_cmd',
        'start_cmd',
        'env_vars',
        'disk_used',
        'bandwidth_used',
        'disk_limit_mb',
        'ram_limit_mb',
        'cpu_limit',
        'whmcs_service_id',
        'plan',
        'suspended_at',
        'suspended_reason',
        'expires_at'
    ];

    protected $casts = [
        'env_vars' => 'array',
        'disk_limit_mb' => 'integer',
        'ram_limit_mb' => 'integer',
        'cpu_limit' => 'float',
        'disk_used' => 'integer',
        'bandwidth_used' => 'integer',
        'suspended_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get service owner
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get service domains
     */
    public function domains()
    {
        return $this->hasMany(Domain::class, 'service_id');
    }

    /**
     * Get primary domain
     */
    public function primaryDomain()
    {
        return $this->hasOne(Domain::class, 'service_id')->where('is_primary', true);
    }

    /**
     * Get service databases
     */
    public function databases()
    {
        return $this->hasMany(Database::class, 'service_id');
    }

    /**
     * Get service backups
     */
    public function backups()
    {
        return $this->hasMany(Backup::class, 'service_id');
    }

    /**
     * Get deployment history
     */
    public function deployments()
    {
        return $this->hasMany(Deployment::class, 'service_id');
    }

    /**
     * Get package
     */
    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    /**
     * Check if service is running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if service is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended' || $this->suspended_at !== null;
    }

    // =========================================
    // Multi-Language Support Methods
    // =========================================

    /**
     * Get language configuration
     */
    public function getLanguageConfig(): ?array
    {
        return LanguageService::getLanguageConfig($this->language ?? 'nodejs');
    }

    /**
     * Get base Docker image for this service's language
     */
    public function getBaseImage(): string
    {
        return LanguageService::getBaseImage(
            $this->language ?? 'nodejs',
            $this->language_version ?? $this->node_version
        ) ?? 'node:20-alpine';
    }

    /**
     * Get default build commands for language
     */
    public function getBuildCommands(): array
    {
        return LanguageService::getBuildCommands($this->language ?? 'nodejs');
    }

    /**
     * Get container resource limits as Docker options
     */
    public function getResourceLimits(): array
    {
        return [
            'Memory' => ($this->ram_limit_mb ?? 512) * 1024 * 1024, // Convert to bytes
            'NanoCPUs' => (int) (($this->cpu_limit ?? 1.0) * 1e9),
            // Note: disk limit requires storage-opt which needs overlay2 driver
        ];
    }

    /**
     * Check if disk quota exceeded
     */
    public function isDiskQuotaExceeded(): bool
    {
        if (!$this->disk_limit_mb)
            return false;
        return ($this->disk_used ?? 0) >= ($this->disk_limit_mb * 1024 * 1024);
    }

    /**
     * Get disk usage percentage
     */
    public function getDiskUsagePercent(): float
    {
        if (!$this->disk_limit_mb || $this->disk_limit_mb == 0)
            return 0;
        return round(($this->disk_used ?? 0) / ($this->disk_limit_mb * 1024 * 1024) * 100, 2);
    }
}

