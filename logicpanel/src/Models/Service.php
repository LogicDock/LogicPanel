<?php
/**
 * LogicPanel - Service Model
 * Represents a user's Node.js application/container
 */

namespace LogicPanel\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'services';

    protected $fillable = [
        'user_id',
        'name',
        'container_id',
        'container_name',
        'status',
        'node_version',
        'port',
        'github_repo',
        'github_branch',
        'github_pat',
        'install_cmd',
        'build_cmd',
        'start_cmd',
        'env_vars',
        'whmcs_service_id',
        'plan',
        'suspended_at',
        'expires_at'
    ];

    protected $casts = [
        'env_vars' => 'array',
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
}
