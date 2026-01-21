<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

class FirestoreAdminUser implements Authenticatable
{
    private array $attributes;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->attributes['id'] ?? 'admin';
    }

    public function getAuthPassword()
    {
        return $this->attributes['password_hash'] ?? null;
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {
    }

    public function getRememberTokenName()
    {
        return '';
    }

    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function getRoleNames(): array
    {
        $roles = $this->attributes['roles'] ?? [];
        return is_array($roles) ? $roles : [];
    }

    public function hasRole($role): bool
    {
        $roles = $this->getRoleNames();
        return in_array($role, $roles, true);
    }

    public function hasAnyRole($roles): bool
    {
        $current = $this->getRoleNames();
        $roles = is_array($roles) ? $roles : [$roles];
        foreach ($roles as $role) {
            if (in_array($role, $current, true)) {
                return true;
            }
        }
        return false;
    }

    public function can($ability, $arguments = []): bool
    {
        if ($this->hasAnyRole(['admin', 'super_admin', 'demo_admin'])) {
            return true;
        }
        return false;
    }
}
