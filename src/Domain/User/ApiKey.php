<?php

declare(strict_types=1);

namespace LogicPanel\Domain\User;

use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    protected $table = 'api_keys';

    protected $fillable = [
        'user_id',
        'name',
        'p_key',
        'permissions',
        'last_used_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_used_at' => 'datetime',
        'permissions' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
