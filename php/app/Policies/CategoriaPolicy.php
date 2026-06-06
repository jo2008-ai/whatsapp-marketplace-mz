<?php

namespace App\Policies;

use App\Models\Categoria;
use App\Models\User;

class CategoriaPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->tenant_id !== null;
    }

    public function update(User $user, Categoria $categoria): bool
    {
        return $user->isAdmin() && $user->tenant_id === $categoria->tenant_id;
    }

    public function delete(User $user, Categoria $categoria): bool
    {
        return $user->isAdmin() && $user->tenant_id === $categoria->tenant_id;
    }
}
