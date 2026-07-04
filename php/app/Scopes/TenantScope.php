<?php

namespace App\Scopes;

use App\Context\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\App;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = App::make(TenantContext::class);

        if ($context->hasTenant()) {
            $builder->where('tenant_id', $context->tenantId());
        }
    }

    public function extend(Builder $builder, string $relation): void
    {
        $builder->macro('withoutTenantScope', function (Builder $builder) {
            return $builder->withoutGlobalScope(TenantScope::class);
        });

        $builder->macro('withTenant', function (Builder $builder, int $tenantId) {
            return $builder->withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId);
        });
    }
}
