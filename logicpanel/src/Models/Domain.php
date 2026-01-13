<?php
/**
 * LogicPanel - Domain Model
 */

namespace LogicPanel\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $table = 'domains';

    protected $fillable = [
        'service_id',
        'domain',
        'is_primary',
        'ssl_enabled',
        'ssl_expires_at'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'ssl_enabled' => 'boolean',
        'ssl_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get parent service
     */
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
