<?php

declare(strict_types=1);

namespace LogicPanel\Domain\Package;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $table = 'packages';

    protected $fillable = [
        'name',
        'description',
        'cpu_limit', // Cores (0.5, 1.0, etc.)
        'memory_limit', // Integer (MB)
        'storage_limit', // Integer (MB)
        'bandwidth_limit', // Integer (MB)
        'email_limit',
        'ftp_limit',
        'db_limit',
        'max_subdomains',
        'max_parked_domains',
        'max_addon_domains',
        'max_services',
        'max_databases',
        'price',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function users()
    {
        return $this->hasMany(\LogicPanel\Domain\User\User::class);
    }
}
