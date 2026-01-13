<?php
/**
 * LogicPanel - Database Model
 * Represents user-created databases (MariaDB/PostgreSQL/MongoDB)
 */

namespace LogicPanel\Models;

use Illuminate\Database\Eloquent\Model;

class Database extends Model
{
    protected $table = 'databases';

    protected $fillable = [
        'service_id',
        'container_id',
        'container_name',
        'type',
        'db_name',
        'db_user',
        'db_password',
        'root_password',
        'port',
        'status'
    ];

    protected $hidden = [
        'db_password',
        'root_password'
    ];

    protected $casts = [
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
     * Get masked password
     */
    public function getMaskedPassword(): string
    {
        return str_repeat('*', 8);
    }

    /**
     * Get connection string
     */
    public function getConnectionString(): string
    {
        switch ($this->type) {
            case 'mariadb':
                return "mysql://{$this->db_user}:{$this->db_password}@{$this->container_name}:3306/{$this->db_name}";
            case 'postgresql':
                return "postgresql://{$this->db_user}:{$this->db_password}@{$this->container_name}:5432/{$this->db_name}";
            case 'mongodb':
                return "mongodb://{$this->db_user}:{$this->db_password}@{$this->container_name}:27017/{$this->db_name}";
            default:
                return '';
        }
    }
}
