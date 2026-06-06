<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendedor;

class VendedorPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->tenant_id !== null;
    }

    public function update(User $user, Vendedor $vendedor): bool
    {
        return $user->isAdmin() && $user->tenant_id === $vendedor->tenant_id;
    }

    public function delete(User $user, Vendedor $vendedor): bool
    {
        return $user->isAdmin() && $user->tenant_id === $vendedor->tenant_id;
    }
}
