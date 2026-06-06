<?php

namespace App\Policies;

use App\Models\Encomenda;
use App\Models\User;

class EncomendaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Encomenda $encomenda): bool
    {
        return $user->tenant_id === $encomenda->tenant_id;
    }

    public function update(User $user, Encomenda $encomenda): bool
    {
        return $user->isAdmin() && $user->tenant_id === $encomenda->tenant_id;
    }
}
