<?php

use App\Context\TenantContext;

if (!function_exists('tenant_id')) {
    function tenant_id(): ?int
    {
        $context = app(TenantContext::class);

        return $context->hasTenant() ? $context->tenantId() : null;
    }
}

if (!function_exists('tenant')) {
    function tenant(?string $key = null, mixed $default = null): mixed
    {
        $context = app(TenantContext::class);

        if (!$context->hasTenant()) {
            return null;
        }

        $tenant = $context->tenant();

        if (is_null($key)) {
            return $tenant;
        }

        return data_get($tenant, $key, $default);
    }
}

if (!function_exists('cache_prefix')) {
    function cache_prefix(): string
    {
        $id = tenant_id();

        if (is_null($id)) {
            return '';
        }

        return "tenant_{$id}_";
    }
}

if (!function_exists('tenant_timezone')) {
    function tenant_timezone(): string
    {
        return tenant('timezone') ?? config('app.timezone');
    }
}

if (!function_exists('tenant_moeda')) {
    function tenant_moeda(): string
    {
        return tenant('moeda') ?? 'MZN';
    }
}

if (!function_exists('tenant_idioma')) {
    function tenant_idioma(): string
    {
        return tenant('idioma') ?? 'pt';
    }
}
