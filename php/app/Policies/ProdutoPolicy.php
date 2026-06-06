<?php

namespace App\Policies;

use App\Models\Produto;
use App\Models\User;

class ProdutoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Produto $produto): bool
    {
        return $user->tenant_id === $produto->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->tenant_id !== null;
    }

    public function update(User $user, Produto $produto): bool
    {
        return $user->isAdmin() && $user->tenant_id === $produto->tenant_id;
    }

    public function delete(User $user, Produto $produto): bool
    {
        return $user->isAdmin() && $user->tenant_id === $produto->tenant_id;
    }
}
