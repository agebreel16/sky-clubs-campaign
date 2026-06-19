<?php

namespace App\Policies;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class AgentPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        if (! $user instanceof User) return false;
        return $user->is_active;
    }

    public function view(Authenticatable $user, Agent $agent): bool
    {
        if (! $user instanceof User) return false;
        return $user->is_active;
    }

    public function create(Authenticatable $user): bool
    {
        if (! $user instanceof User) return false;
        return in_array($user->role, ['super_admin', 'admin', 'supervisor', 'data_entry']);
    }

    public function update(Authenticatable $user, Agent $agent): bool
    {
        if (! $user instanceof User) return false;
        return in_array($user->role, ['super_admin', 'admin', 'supervisor', 'data_entry']);
    }

    public function delete(Authenticatable $user, Agent $agent): bool
    {
        if (! $user instanceof User) return false;
        return in_array($user->role, ['super_admin', 'admin']);
    }

    public function restore(Authenticatable $user, Agent $agent): bool
    {
        if (! $user instanceof User) return false;
        return in_array($user->role, ['super_admin', 'admin']);
    }

    public function forceDelete(Authenticatable $user, Agent $agent): bool
    {
        return false;
    }
}
