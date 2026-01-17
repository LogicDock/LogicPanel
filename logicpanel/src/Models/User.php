<?php
/**
 * LogicPanel - User Model
 * Supports 3-level hierarchy: Admin -> Reseller -> User
 */

namespace LogicPanel\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'lp_users';

    protected $fillable = [
        'username',
        'email',
        'password',
        'name',
        'role',
        'parent_id',
        'reseller_package_id',
        'theme',
        'whmcs_user_id',
        'is_active',
        'last_login'
    ];

    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get user's services
     */
    public function services()
    {
        return $this->hasMany(Service::class, 'user_id');
    }

    /**
     * Get parent user (for users under resellers)
     */
    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Get child users (for resellers)
     */
    public function children()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    /**
     * Get reseller package (for resellers only)
     */
    public function resellerPackage()
    {
        return $this->belongsTo(ResellerPackage::class, 'reseller_package_id');
    }

    // =========================================
    // Role Check Methods
    // =========================================

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is reseller
     */
    public function isReseller(): bool
    {
        return $this->role === 'reseller';
    }

    /**
     * Check if user is regular user
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    // =========================================
    // Permission Methods
    // =========================================

    /**
     * Check if this user can manage another user
     * Admin: can manage everyone
     * Reseller: can manage their own users only
     */
    public function canManageUser(User $targetUser): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isReseller()) {
            return $targetUser->parent_id === $this->id;
        }

        return false;
    }

    /**
     * Check if user can create new users
     */
    public function canCreateUsers(): bool
    {
        return $this->isAdmin() || $this->isReseller();
    }

    /**
     * Check if user can create packages
     * Admin: can create any package
     * Reseller: can create user packages for their users only
     */
    public function canCreatePackages(): bool
    {
        return $this->isAdmin() || $this->isReseller();
    }

    /**
     * Check if user can access admin panel
     */
    public function canAccessAdmin(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can access reseller panel
     */
    public function canAccessReseller(): bool
    {
        return $this->isAdmin() || $this->isReseller();
    }

    /**
     * Get max users this user can create (for resellers)
     */
    public function getMaxUsers(): int
    {
        if ($this->isAdmin()) {
            return PHP_INT_MAX;
        }

        if ($this->isReseller() && $this->resellerPackage) {
            return $this->resellerPackage->max_users;
        }

        return 0;
    }

    /**
     * Get count of users created by this user
     */
    public function getCreatedUsersCount(): int
    {
        return $this->children()->count();
    }

    /**
     * Check if reseller can create more users
     */
    public function canCreateMoreUsers(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->getCreatedUsersCount() < $this->getMaxUsers();
    }

    // =========================================
    // Authentication Methods
    // =========================================

    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * Set password (hashed with bcrypt, cost 12)
     */
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = password_hash($value, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}
