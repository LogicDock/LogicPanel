<?php
/**
 * LogicPanel - Deployment Model
 * Tracks git deployment history
 */

namespace LogicPanel\Models;

use Illuminate\Database\Eloquent\Model;

class Deployment extends Model
{
    protected $table = 'deployments';

    protected $fillable = [
        'service_id',
        'commit_hash',
        'commit_message',
        'branch',
        'status',
        'log',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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

    /**
     * Get short commit hash
     */
    public function getShortHash(): string
    {
        return substr($this->commit_hash ?? '', 0, 7);
    }

    /**
     * Get deployment duration
     */
    public function getDuration(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return $this->completed_at->diffInSeconds($this->started_at);
        }
        return null;
    }
}
